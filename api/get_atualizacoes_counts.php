<?php
require_once('../../../config.php');
require_once('../locallib.php');

class get_messages_service extends \local_suap\service{

    function do_call() {
        global $DB, $USER;

        $USER = $DB->get_record('user', ['username' => $_GET['username']]);
        return $this->get_atualizacoes_counts($USER->id);
    }

    function get_atualizacoes_counts($useridto) {
        return [
            "unread_conversations_count" => \core_message_external::get_unread_conversations_count($useridto),
            "unread_popup_notification_count" => \message_popup_external::get_unread_popup_notification_count($useridto),
        ];
    }

}

(new get_messages_service())->call();
