<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_disciplinas_service extends \local_suap\service {
    function do_call() {
        return \local_suap\get_disciplinas(\local_suap\aget($_GET, 'student', false), \local_suap\aget($_GET, 'username', false));
    }
}

(new get_disciplinas_service())->call();
