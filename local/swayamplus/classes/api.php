<?php
namespace local_swayamplus;

class api {

    public static function get_access_token() {
        $cache = \cache::make('local_swayamplus', 'tokens');
        $token = $cache->get('bearer_token');

        if (!$token) {
            $client_id = get_config('local_swayamplus', 'client_id');
            $secret = get_config('local_swayamplus', 'client_secret');
            $base_url = get_config('local_swayamplus', 'swayam_url');

            $curl = new \curl();
            $curl->setHeader('Authorization: Basic ' . base64_encode($client_id . ':' . $secret));

            $data = [
                'grant_type' => 'client_credentials',
                'scope' => 'partner.enrollments:read partner.completions:write'
            ];

            $response = $curl->post($base_url . '/oidc/token', $data);
            $json = json_decode($response);

            if (empty($json) || empty($json->access_token)) {
                throw new \moodle_exception('cannotfetchtoken', 'local_swayamplus');
            }

            $token = $json->access_token;
            // Cache token for slightly less than 10 mins (e.g., 550 seconds)
            $cache->set('bearer_token', $token, 550);
        }
        return $token;
    }

    public static function report_progress($enrollment_id, $percent) {
        $payload = ['percent' => (int)$percent];
        $result = self::attempt_push('progress', $enrollment_id, $payload);
        if (!$result['success']) {
            self::enqueue_retry('progress', $enrollment_id, $payload, $result);
        }
    }

    public static function report_completion($enrollment_id) {
        $payload = [];
        $result = self::attempt_push('completion', $enrollment_id, $payload);
        if (!$result['success']) {
            self::enqueue_retry('completion', $enrollment_id, $payload, $result);
        }
    }

    /**
     * Attempt a single push to the Swayam Plus API. Does not throw on
     * failure - callers decide whether to enqueue a retry.
     *
     * @param string $type 'progress' or 'completion'
     * @param string $enrollmentid Swayam enrollment ID
     * @param array $payload request body, pre-json-encode
     * @return array ['success' => bool, 'httpcode' => int, 'error' => string|null]
     */
    private static function attempt_push(string $type, string $enrollmentid, array $payload): array {
        $base_url = get_config('local_swayamplus', 'swayam_url');
        $path = $type === 'progress'
            ? "/api/v1/partner/enrollments/{$enrollmentid}/progress"
            : "/api/v1/partner/enrollments/{$enrollmentid}/completion";

        try {
            $token = self::get_access_token();
        } catch (\Throwable $e) {
            return ['success' => false, 'httpcode' => 0, 'error' => 'token_error: ' . $e->getMessage()];
        }

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token);
        $curl->setHeader('Content-Type: application/json');

        $body = json_encode(!empty($payload) ? $payload : new \stdClass());
        $response = $curl->post($base_url . $path, $body);
        $info = $curl->get_info();
        $httpcode = isset($info['http_code']) ? (int)$info['http_code'] : 0;

        if ($httpcode === 401) {
            // Token may have been rejected despite our cache TTL (e.g. revoked
            // server-side). Clear it so the next attempt fetches a fresh one
            // rather than repeating the same failure every retry.
            \cache::make('local_swayamplus', 'tokens')->delete('bearer_token');
        }

        if ($httpcode >= 200 && $httpcode < 300) {
            return ['success' => true, 'httpcode' => $httpcode, 'error' => null];
        }

        return [
            'success' => false,
            'httpcode' => $httpcode,
            'error' => "HTTP {$httpcode}: " . substr((string)$response, 0, 500),
        ];
    }

    /**
     * Record a failed push for later retry.
     */
    private static function enqueue_retry(string $type, string $enrollmentid, array $payload, array $result): void {
        global $DB;
        $now = time();

        $record = new \stdClass();
        $record->pushtype = $type;
        $record->enrollmentid = $enrollmentid;
        $record->payload = json_encode($payload);
        $record->attempts = 1;
        $record->lastresponsecode = $result['httpcode'];
        $record->lasterror = $result['error'];
        $record->nextattempttime = $now + self::backoff_delay(1);
        $record->timecreated = $now;
        $record->timemodified = $now;

        $DB->insert_record('local_swayamplus_pushqueue', $record);
    }

    /**
     * Exponential backoff: 1 min, 2, 4, 8... capped at 6 hours so a long
     * outage doesn't stretch retries out to the point they're effectively
     * abandoned, while still not hammering a down API every 5 minutes.
     */
    public static function backoff_delay(int $attempts): int {
        $base = 60;
        $cap = 6 * 3600;
        return (int)min($cap, $base * (2 ** min($attempts, 10)));
    }

    /**
     * Process all due rows in the retry queue. Called by the
     * retry_queue scheduled task. Processes at most 200 rows per run so a
     * large backlog can't blow past cron's execution time budget - any
     * remainder is picked up on the next run 5 minutes later.
     *
     * @param callable|null $log optional callback for progress lines, e.g. mtrace
     */
    public static function process_retry_queue(?callable $log = null): void {
        global $DB;
        $log = $log ?? function ($msg) {
        };
        $now = time();

        $rows = $DB->get_records_select(
            'local_swayamplus_pushqueue',
            'nextattempttime <= :now',
            ['now' => $now],
            'nextattempttime ASC',
            '*',
            0,
            200
        );

        if (empty($rows)) {
            $log('No due retries.');
            return;
        }

        foreach ($rows as $row) {
            $payload = json_decode($row->payload, true) ?: [];
            $result = self::attempt_push($row->pushtype, $row->enrollmentid, $payload);

            if ($result['success']) {
                $log("Retry succeeded: {$row->pushtype} for enrollment {$row->enrollmentid} (attempt " . ($row->attempts + 1) . ').');
                $DB->delete_records('local_swayamplus_pushqueue', ['id' => $row->id]);
                continue;
            }

            $attempts = $row->attempts + 1;
            $update = new \stdClass();
            $update->id = $row->id;
            $update->attempts = $attempts;
            $update->lastresponsecode = $result['httpcode'];
            $update->lasterror = $result['error'];
            $update->nextattempttime = $now + self::backoff_delay($attempts);
            $update->timemodified = $now;
            $DB->update_record('local_swayamplus_pushqueue', $update);

            $log("Retry failed: {$row->pushtype} for enrollment {$row->enrollmentid} (attempt {$attempts}): {$result['error']}");
        }
    }
}
