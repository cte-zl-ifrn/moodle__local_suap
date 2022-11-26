<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_semestres_service extends \suapsync\service {
    function do_call() {
        return \suapsync\get_semestres(\suapsync\aget($_GET, 'student', false), \suapsync\aget($_GET, 'username', false));
    }

}

(new get_semestres_service())->call();
