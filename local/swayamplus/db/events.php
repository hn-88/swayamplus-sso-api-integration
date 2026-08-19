<?php
// File: local/swayamplus/db/events.php
defined('MOODLE_INTERNAL') || die();

$observers = [
    // Fired right after a user logs in successfully (used to capture the pending enrollmentId)
    [
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\local_swayamplus\observer::user_loggedin',
    ],
    // Fired when a module/activity completion status changes (used to push 0-100% progress)
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\local_swayamplus\observer::course_module_completion_updated',
    ],
    // Fired when the final overall course completion criteria is met (used to push confirmation)
    [
        'eventname' => '\core\event\course_completed',
        'callback'  => '\local_swayamplus\observer::course_completed',
    ],
];
