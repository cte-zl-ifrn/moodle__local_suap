<?php
require_once('../../config.php');
require_once(__DIR__.'/locallib.php');


class sync_up_enrolments_service extends service {

    function call() {
        global $CFG, $DB;
    
        try { 
            $this->authenticate();

            $notas = $DB->get_record_sql('
                select   gi.idnumber codigo_nota, u.username matricula_aluno, gg.finalgrade nota_aluno
                from     {grade_grades} gg
                            inner join {grade_grades} gi on (gg.itemid=gi.id)
                            inner join {grade_grades} c on (gi.courseid = c.id)
                            inner join {user} u on (gg.userid=u.id)
                where    gi.idnumber is not null and gi.idnumber != ''
                and    c.idnumber = ?;
            ', [$_GET['codigo_diario']]);

            echo json_encode($notas);
        } catch (Exception $ex) {
            http_response_code(500);
            if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
                echo json_encode(["error" => ["message" => "Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos."]]);
            } else {
                echo json_encode(["error" => ["message" => $ex->getMessage()]]);
            }
        }
    }

}


$service = new sync_up_enrolments_service();
$service->call();
    