<?php
namespace local_suap;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../../config.php');
require_once("../locallib.php");
require_once("servicelib.php");


class sync_up_enrolments_service extends service {

    function call() {
        global $CFG, $DB;
        // $this->authenticate();

        $notas = $DB->get_record_sql('
            select   gi.idnumber codigo_nota, u.username matricula_aluno, gg.finalgrade nota_aluno
            from     {grade_grades} gg
                        inner join {grade_grades} gi on (gg.itemid=gi.id)
                        inner join {grade_grades} c on (gi.courseid = c.id)
                        inner join {user} u on (gg.userid=u.id)
            where    gi.idnumber is not null and gi.idnumber != ''
            and    c.idnumber = ?;
        ', [$_GET['codigo_diario']]);
    }

}


$service = new sync_up_enrolments_service();
$service->call();
    