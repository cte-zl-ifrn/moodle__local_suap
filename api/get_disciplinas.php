<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_disciplinas_service extends \suapsync\service {
    function do_call() {
        return \suapsync\get_disciplinas(\suapsync\aget($_GET, 'student', false), \suapsync\aget($_GET, 'username', false));
    }

}

(new get_disciplinas_service())->call();