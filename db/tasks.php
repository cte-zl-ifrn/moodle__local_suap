<?php
namespace local_suap\task;

class sync_up_enrolments_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('sync_up_enrolments_task', 'local_suap');
    }

    public function execute() {
        global $DB;
        require_once("../api/sync_up_enrolments.php");

        // echo "do nothing";
        
        // $items = $DB->get_records_sql("SELECT * FROM {suap_enrolment_to_sync} WHERE processed = 0 ORDER BY id ASC");
       
        // foreach ($items as $item) {
        //     try {
        //         $service = new sync_up_enrolments_service();
        //         $service->process($item->json, true);
        //         $item->processed = 1; // sucesso
        //         $DB->update_record('{suap_enrolment_to_sync}', $item);   
        //     } catch (\Throwable $e) {
        //         $item->processed = 2; // falha
        //         // $item->attempts = $item->attempts + 1;
        //         $DB->update_record('{suap_enrolment_to_sync}', $item);   
        //     }
        // }
    }
}

$tasks = [
    [
        'classname' => 'local_suap\task\sync_up_enrolments_task',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];