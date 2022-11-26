<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_diarios_service extends \suapsync\service {
    function do_call() {
        return \suapsync\get_diarios(
            \suapsync\aget($_GET, 'student', false),
            \suapsync\aget($_GET, 'username', null),
            \suapsync\aget($_GET, 'disciplina', null),
            \suapsync\aget($_GET, 'situacao', null),
            \suapsync\aget($_GET, 'semestre', null),
            \suapsync\aget($_GET, 'q', null)
        );
    }
}
(new get_diarios_service())->call();    