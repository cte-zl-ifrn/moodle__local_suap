<?php
namespace local_suap;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../../config.php');
require_once("../locallib.php");
require_once("servicelib.php");

// Link de acesso (exemplo): http://ava/local/suap/api/sync_down_grades.php?codigo_diario=20231.1.15806.1E.TEC.1386

class sync_up_enrolments_service extends service {
    
    function call() {
        global $CFG, $DB;
        
        try { 
            $this->authenticate();
            
            $notas = $DB->get_records_sql("
                select   gi.id id, gi.idnumber codigo_nota, u.username matricula_aluno, gg.finalgrade nota_aluno
                from     mdl_grade_grades gg
                            inner join mdl_grade_items gi on (gg.itemid=gi.id)
                            inner join mdl_course c on (gi.courseid = c.id)
                            inner join mdl_user u on (gg.userid=u.id)
                where    gi.idnumber is not null and gi.idnumber != ''
                and    c.idnumber = ?;
            ", [$_GET['codigo_diario']]);

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
