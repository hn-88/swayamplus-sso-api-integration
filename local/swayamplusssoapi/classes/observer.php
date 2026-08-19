<?php
namespace local_swayamplus;

class observer {
    
    // Triggered right after Moodle successfully logs the user in
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION, $DB;
        
        if (!empty($SESSION->swayam_pending_enrollment)) {
            $enrollment_id = $SESSION->swayam_pending_enrollment;
            $userid = $event->userid;
            
            // Note: A robust implementation would also parse the $wantsurl from the session 
            // to extract the Moodle Course ID ($courseid) they are landing on to map exactly.
            // ... (extraction logic here) ...
            
            $record = new \stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid; 
            $record->swayam_enrollment_id = $enrollment_id;
            
            if (!$DB->record_exists('local_swayamplus_enrol', ['userid' => $userid, 'courseid' => $courseid])) {
                $DB->insert_record('local_swayamplus_enrol', $record);
            }
            
            unset($SESSION->swayam_pending_enrollment);
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
}
// Note: Register these events in local/swayamplus/db/events.php.
