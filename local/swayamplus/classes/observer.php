<?php
namespace local_swayamplus;
class observer {

    // Triggered right after Moodle successfully logs the user in
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION, $DB;

        if (!empty($SESSION->swayam_pending_enrollment)) {
            $enrollment_id = $SESSION->swayam_pending_enrollment;
            $userid = $event->userid;

            $courseid = null;
            if (!empty($SESSION->swayam_pending_wanturl)) {
                $courseid = self::extract_courseid_from_url($SESSION->swayam_pending_wanturl);
            }

            // Always clear session state, even on failure, so a stale
            // enrollmentId/url pair can't get matched to a later unrelated login.
            unset($SESSION->swayam_pending_enrollment);
            unset($SESSION->swayam_pending_wanturl);

            if (empty($courseid)) {
                debugging(
                    'local_swayamplus: could not determine courseid for SWAYAM enrollment ' . $enrollment_id,
                    DEBUG_DEVELOPER
                );
                return;
            }

            $record = new \stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->swayam_enrollment_id = $enrollment_id;

            if (!$DB->record_exists('local_swayamplus_enrol', ['userid' => $userid, 'courseid' => $courseid])) {
                $DB->insert_record('local_swayamplus_enrol', $record);
            }
        }
    }

    // Triggered when a learner progresses
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        global $DB;
        $enrollment = $DB->get_record('local_swayamplus_enrol', [
            'userid' => $event->relateduserid,
            'courseid' => $event->courseid
        ]);

        if ($enrollment) {
            // Calculate progress % based on Moodle completion criteria
            $progress_percent = self::calculate_course_progress($event->courseid, $event->relateduserid);

            // Push via API (See API class below)
            \local_swayamplus\api::report_progress($enrollment->swayam_enrollment_id, $progress_percent);
        }
    }

    // Triggered when the final course completion criteria is met
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;
        $enrollment = $DB->get_record('local_swayamplus_enrol', [
            'userid' => $event->relateduserid,
            'courseid' => $event->courseid
        ]);

        if ($enrollment) {
            \local_swayamplus\api::report_completion($enrollment->swayam_enrollment_id);
        }
    }

    private static function calculate_course_progress($courseid, $userid) {
        // Implement Moodle's completion API logic here to return an integer 0-100
        $completion = new \completion_info(get_course($courseid));
        // ... calculation ...
        return 50; // Example whole integer return
    }

    /**
     * Extract a Moodle courseid from a stashed target URL.
     * Handles a direct course link (/course/view.php?id=X) and an activity
     * link (/mod/xxx/view.php?id=X, where X is a course_modules.id, not a
     * courseid) by resolving the latter via the course_modules table.
     *
     * @param string $urlstring absolute or relative Moodle URL
     * @return int|null courseid, or null if it can't be determined
     */
    private static function extract_courseid_from_url($urlstring) {
        global $DB;

        try {
            $url = new \moodle_url($urlstring);
        } catch (\moodle_exception $e) {
            return null;
        }

        $path = $url->get_path();

        // Direct course page: /course/view.php?id=123
        if (strpos($path, '/course/view.php') !== false) {
            $courseid = $url->get_param('id');
            return !empty($courseid) ? (int)$courseid : null;
        }

        // Activity page: /mod/<type>/view.php?id=<cmid> — cmid is a
        // course_modules.id, not a courseid, so it needs a DB lookup.
        if (strpos($path, '/mod/') !== false) {
            $cmid = $url->get_param('id');
            if (!empty($cmid)) {
                $courseid = $DB->get_field('course_modules', 'course', ['id' => (int)$cmid]);
                return !empty($courseid) ? (int)$courseid : null;
            }
        }

        return null;
    }
}
// Note: Register these events in local/swayamplus/db/events.php.
