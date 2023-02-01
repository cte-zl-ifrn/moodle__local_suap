<?php
require_once('../../../config.php');
require_once('../locallib.php');

class get_diarios_service extends \local_suap\service {
    function do_call() {
        return \local_suap\get_diarios(
            \local_suap\aget($_GET, 'username', null),
            \local_suap\aget($_GET, 'semestre', null),
            \local_suap\aget($_GET, 'situacao', null),
            \local_suap\aget($_GET, 'ordenacao', null),
            \local_suap\aget($_GET, 'disciplina', null),
            \local_suap\aget($_GET, 'curso', null),
            \local_suap\aget($_GET, 'arquetipo', 'student'),
            \local_suap\aget($_GET, 'q', null),
            \local_suap\aget($_GET, 'page', 1),
            \local_suap\aget($_GET, 'page_size', 9),
        );
    }
}

(new get_diarios_service())->call();
