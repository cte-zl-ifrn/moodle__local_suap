<?php
namespace local_suapsync\task;

class send_enrolments_to_portal extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('send_enrolments_to_portal', 'local_suapsync');
    }

    public function execute() {
        echo "do nothing";
    }
}

$tasks = [
    [
        'classname' => 'local_suapsync\task\send_enrolments_to_portal',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
