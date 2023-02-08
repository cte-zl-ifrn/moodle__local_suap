<?php
require_once('../../../config.php');
require_once('../locallib.php');

class get_favourite_course extends \local_suap\service{

    function do_call() {
        global $DB;

        // $user = $DB->get_record('user', ['username' => $_GET['username']]);
        // return \local_suap\get_conversation_counts($user->id);
    }


}

(new get_favourite_course())->call();
