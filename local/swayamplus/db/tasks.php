<?php
// File: local/swayamplus/db/tasks.php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_swayamplus\task\sync_roster',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2', // Runs at 2:00 AM nightly
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ],
];
