<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_diarios_service extends \suap\service {
    function do_call() {
        return \suap\get_diarios(
            \suap\aget($_GET, 'student', false),
            \suap\aget($_GET, 'username', null),
            \suap\aget($_GET, 'disciplina', null),
            \suap\aget($_GET, 'situacao', null),
            \suap\aget($_GET, 'semestre', null),
            \suap\aget($_GET, 'q', null)
        );
    }
}
(new get_diarios_service())->call();    