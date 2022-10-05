<?php
namespace suapsync;

/**
 * SUAP Integration
 *
 * This module provides extensive analytics on a platform of choice
 * Currently support Google Analytics and Piwik
 *
 * @package     local_suapsync
 * @category    upgrade
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function get_last_sort_order($tablename) {
    global $DB;
    $l = $DB->get_record_sql('SELECT coalesce(max(sortorder), 0) + 1 as sortorder from {' . $tablename . '}');
    return $l->sortorder;
}


function get_or_create($tablename, $keys, $values) {
    global $DB;
    $record = $DB->get_record($tablename, $keys);
    if (!$record) {
        $record = (object)array_merge($keys, $values);
        $record->id = $DB->insert_record($tablename, $record);
    }
    return $record;
}


function create_or_update($tablename, $keys, $inserts, $updates=[], $insert_only=[]) {
    global $DB;
    $record = $DB->get_record($tablename, $keys);
    if ($record) {
        foreach (array_merge($keys, $inserts, $updates) as $attr => $value) {
            $record->{$attr} = $value;
        }
        $DB->update_record($tablename, $record);
    } else {
        $record = (object)array_merge($keys, $inserts, $insert_only);
        $record->id = $DB->insert_record($tablename, $record);
    }
    return $record;
}


function dienow($message, $code) {
    http_response_code($code);
    die(json_encode(["message"=>$message, "code"=>$code]));
}

function config($name) {
    return get_config('local_suapsync', $name);
}

class service {

    function authenticate() {
        $sync_up_auth_token = config('auth_token');

        $headers = getallheaders();
        if (!array_key_exists('Authentication', $headers)) {
            dienow("Bad Request - Authentication not informed", 400);
        }

        if ("Token $sync_up_auth_token" != $headers['Authentication']) {
            dienow("Unauthorized", 401);
        }
    }



}
