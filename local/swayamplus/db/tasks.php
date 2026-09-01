<?php
// File: local/swayamplus/db/tasks.php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    // Nightly full reconciliation sweep against the Swayam roster API.
    // NOTE: schedule assumed as 2:00 AM - adjust to match whatever your
    // actual existing db/tasks.php already has for this task.
    [
        'classname' => 'local_swayamplus\task\sync_roster',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
    // Lightweight retry queue for progress/completion pushes that failed
    // (e.g. Swayam API was temporarily unreachable). Runs every 5 minutes
    // so a live outage recovers quickly rather than waiting for the next
    // nightly sync.
    [
        'classname' => 'local_swayamplus\task\retry_queue',
        'blocking'  => 0,
        'minute'    => '*/5',
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
