<?php
namespace local_suap\task;

require_once(\dirname(\dirname(\dirname(\dirname(__DIR__)))) . '/config.php');


defined('MOODLE_INTERNAL') || die();

class sync_up_enrolments_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('sync_up_enrolments_task', 'local_suap');
    }

    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . "/local/suap/api/sync_up_enrolments.php");

        // echo "do nothing";

        $items = $DB->get_records_sql("SELECT * FROM {suap_enrolment_to_sync} WHERE processed = 0 ORDER BY id ASC");
       
        foreach ($items as $item) {
            echo "iteri $item->id\n";
            try {
                $service = new \local_suap\sync_up_enrolments_service();
                $service->process($item->json, true);
                $item->processed = 1; // sucesso
                $DB->update_record('suap_enrolment_to_sync', $item);
            } catch (\Throwable $e) {
                $item->processed = 2; // falha
                $DB->update_record('suap_enrolment_to_sync', $item);   
            }
        }
    }
}