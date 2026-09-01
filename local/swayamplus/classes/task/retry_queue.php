<?php
// File: local/swayamplus/classes/task/retry_queue.php
// Runs frequently (every 5 min) and flushes any failed progress/completion
// pushes to Swayam, retrying with exponential backoff. Deliberately kept
// separate from sync_roster (nightly, heavier, paginated API sweep) so a
// live outage doesn't have to wait until the next nightly run to recover.
namespace local_swayamplus\task;

use core\task\scheduled_task;

class retry_queue extends scheduled_task {

    public function get_name() {
        return get_string('taskretryqueue', 'local_swayamplus');
    }

    public function execute() {
        mtrace('Starting Swayam Plus push retry queue...');
        \local_swayamplus\api::process_retry_queue(function ($msg) {
            mtrace('  - ' . $msg);
        });
        mtrace('Swayam Plus push retry queue completed.');
    }
}
