<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_semestres_service extends \local_suap\service {
    function do_call() {
        return \local_suap\get_semestres(\local_suap\aget($_GET, 'student', false), \local_suap\aget($_GET, 'username', false));
    }

}

(new get_semestres_service())->call();
