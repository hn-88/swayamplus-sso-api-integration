<?php
// File: local/swayamplus/classes/task/sync_roster.php
// run as nightly scheduled task - finds users who have already linked their accounts 
// via SSO and ensures their local Moodle enrollment status matches Swayam 
// (e.g., suspending their local access if Swayam reports TERMINATED).
namespace local_swayamplus\task;

use core\task\scheduled_task;

class sync_roster extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     */
    public function get_name() {
        // Fallback text if the language string isn't defined yet in lang/en/local_swayamplus.php
        return get_string('tasksyncroster', 'local_swayamplus') ?: 'Swayam Plus Nightly Roster Sync';
    }

    /**
     * Execute the nightly paged sweep against the Swayam API.
     */
    public function execute() {
        global $CFG, $DB;
        
        require_once($CFG->libdir . '/filelib.php');

        mtrace("Starting Swayam Plus roster sync...");

        $base_url = get_config('local_swayamplus', 'swayam_url');
        if (empty($base_url)) {
            mtrace("Error: Swayam base URL is not configured. Aborting.");
            return;
        }

        $page = 1;
        $limit = 100; // Max allowed by the Swayam API
        $has_more = true;

        while ($has_more) {
            mtrace("Fetching page {$page}...");
            
            $curl = new \curl();
            // Fetch token via the API class created in Step 4
            $token = \local_swayamplus\api::get_access_token();
            $curl->setHeader('Authorization: Bearer ' . $token);
            
            // Build the paginated URL
            $url = $base_url . "/api/v1/partner/enrollments?limit={$limit}&page={$page}";
            $response = $curl->get($url);
            $data = json_decode($response);
            
            if (empty($data->enrollments) || !is_array($data->enrollments)) {
                mtrace("No enrollments found or malformed response on page {$page}.");
                break;
            }

            $count = count($data->enrollments);
            mtrace("Received {$count} enrollments on page {$page}.");

            foreach ($data->enrollments as $enrol_data) {
                $this->process_enrollment($enrol_data);
            }

            // Stop fetching if the page returns fewer rows than the limit
            if ($count < $limit) {
                $has_more = false;
            } else {
                $page++;
            }
        }

        mtrace("Swayam Plus roster sync completed.");
    }

    /**
     * Reconcile a single enrollment row with Moodle's local database.
     */
    private function process_enrollment($enrol_data) {
        global $DB;

        $enrollment_id = $enrol_data->enrollmentId;
        $swayam_status = $enrol_data->status;

        // 1. Check if we've seen this user via SSO launch yet
        $local_mapping = $DB->get_record('local_swayamplus_enrol', ['swayam_enrollment_id' => $enrollment_id]);

        if (!$local_mapping) {
            // The learner hasn't logged in via the SSO launch yet. We don't have their `sub` 
            // so we cannot reliably map them to a Moodle user right now. Skip them until they click "Go to course".
            return;
        }

        $userid = $local_mapping->userid;
        $courseid = $local_mapping->courseid;

        // 2. Fetch the "Manual" enrol instance for the mapped course to manage their access
        $enrol_plugin = enrol_get_plugin('manual');
        $instances = enrol_get_instances($courseid, true);
        $manual_instance = null;
        
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $manual_instance = $instance;
                break;
            }
        }

        if (!$manual_instance) {
            mtrace("  - Warning: Course {$courseid} has no 'manual' enrolment instance. Skipping.");
            return;
        }

        $context = \context_course::instance($courseid);
        $is_enrolled = is_enrolled($context, $userid);

        // 3. Reconcile statuses
        if (in_array($swayam_status, ['ACCESS_PROVISIONED', 'IN_PROGRESS', 'COMPLETED'])) {
            // Ensure they are enrolled locally and active
            if (!$is_enrolled) {
                mtrace("  - Reconciling: Enrolling user {$userid} into course {$courseid} (Swayam ID: {$enrollment_id})");
                $enrol_plugin->enrol_user($manual_instance, $userid);
            } else {
                // If they are enrolled but their enrollment was previously suspended, reactivate them
                $user_enrolment = $DB->get_record('user_enrolments', [
                    'enrolid' => $manual_instance->id,
                    'userid' => $userid
                ]);
                if ($user_enrolment && $user_enrolment->status == ENROL_USER_SUSPENDED) {
                    mtrace("  - Reconciling: Restoring access for user {$userid} in course {$courseid}");
                    $enrol_plugin->update_user_enrol($manual_instance, $userid, ENROL_USER_ACTIVE);
                }
            }

        } elseif ($swayam_status === 'TERMINATED') {
            // The PDF notes TERMINATED means "their 6-month access window lapsed".
            // Suspend their local Moodle enrollment so they can no longer access the course materials.
            if ($is_enrolled) {
                mtrace("  - Terminating: Suspending user {$userid} in course {$courseid} (Swayam ID: {$enrollment_id})");
                $enrol_plugin->update_user_enrol($manual_instance, $userid, ENROL_USER_SUSPENDED);
            }
        }
    }
}
