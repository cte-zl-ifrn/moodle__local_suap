<?php

namespace local_suap;

require_once('../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");

function suap_sync_down_attendances() {
    global $CFG;

    try { 
        suap_sync_authenticate();

        dienow("Não implementado.", 501);
    } catch (Exception $ex) {
        http_response_code(500);
        if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
            echo json_encode(["error" => ["message" => "Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos."]]);
        } else {
            echo json_encode(["error" => ["message" => $ex->getMessage()]]);
        }
    }
}


suap_sync_down_attendances();
