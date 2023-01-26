<?php
require_once('../../../config.php');
require_once('../locallib.php');


class get_disciplinas_service extends \suap\service {
    function do_call() {
        return \suap\get_disciplinas(\suap\aget($_GET, 'student', false), \suap\aget($_GET, 'username', false));
    }
}

(new get_disciplinas_service())->call();
