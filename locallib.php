<?php
/**
 * SUAP Integration
 *
 * This module provides extensive analytics on a platform of choice
 * Currently support Google Analytics and Piwik
 *
 * @package     local_suap
 * @category    upgrade
 * @copyright   2020 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_suap;

require_once("$CFG->dirroot/course/externallib.php");
require_once("$CFG->dirroot/enrol/externallib.php");

define("REGEX_CODIGO_DIARIO", '/^(\d\d\d\d\d)\.(\d*)\.(\d*)\.(.*)\.(.*\..*)$/');
define("REGEX_CODIGO_DIARIO_ELEMENTS_COUNT", 6);
define("REGEX_CODIGO_DIARIO_SEMESTRE", 1);
define("REGEX_CODIGO_DIARIO_PERIODO", 2);
define("REGEX_CODIGO_DIARIO_CURSO", 3);
define("REGEX_CODIGO_DIARIO_TURMA", 4);
define("REGEX_CODIGO_DIARIO_DISCIPLINA", 5);
define("REGEX_CODIGO_COORDENACAO", '/^ZL\.\d*/');

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
    return get_config('local_suap', $name);
}

function aget($array, $key, $default=null) {
    return \key_exists($key, $array) ? $array[$key] : $default;
}

function get_recordset_as_json($sql, $params) {
    global $DB;

    $result = "[";
    $sep = '';
    foreach ($DB->get_recordset_sql($sql, $params) as $disciplina) {
        $result .= $sep . json_encode($disciplina);
        $sep = ',';
    }
    return $result . "]";
}

function get_recordset_as_array($sql, $params) {
    global $DB;

    $result = [];
    foreach ($DB->get_recordset_sql($sql, $params) as $disciplina) {
        $result[] = $disciplina;
    }
    return $result;
}

function get_cursos($all_diarios) {
    global $DB;
    $result = [];
    foreach ($all_diarios as $course) {
        preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
        if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
            $curso = $matches[REGEX_CODIGO_DIARIO_CURSO];
            $result[$curso] = ['id' => $curso, 'label' => $curso];
        }
    }
    return array_values($result);
}

function get_disciplinas($all_diarios) {
    global $DB;
    $result = [];
    foreach ($all_diarios as $course) {
        preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
        if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
            $disciplina = $matches[REGEX_CODIGO_DIARIO_DISCIPLINA];
            $result[$disciplina] = ['id' => $disciplina, 'label' => "$course->fullname [$disciplina]"];
        }
    }
    return array_values($result);
}

function get_semestres($all_diarios) {
    global $DB;

    $result = [];
    foreach ($all_diarios as $course) {
        preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
        if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {   
            $semestre = $matches[REGEX_CODIGO_DIARIO_SEMESTRE];
            $result[$semestre] = ['id' => $semestre, 'label' => substr($semestre, 0, -1) . '.' . substr($semestre, 4, 1)];
        }
    }
    return array_values($result);
}

function get_all_diarios($username) {
    return get_recordset_as_array("
        SELECT      c.id, 
                    c.shortname shortname,
                    c.fullname fullname
        FROM        {role_assignments} ra
                        INNER JOIN {user} u ON (ra.userid=u.id)
                        INNER JOIN {role} r ON (ra.roleid = r.id)
                        INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                            INNER JOIN {course} c ON (ctx.instanceid=c.id)
        WHERE u.username = ?
        ",
        [$username]);
}

function get_diarios($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size) {
    global $DB, $CFG, $USER;

    $USER = $DB->get_record('user', ['username' => $username]);
    
    $all_diarios = get_all_diarios($USER->username);
    $enrolled_courses = \core_course_external::get_enrolled_courses_by_timeline_classification($situacao, 0, 0, $ordenacao)['courses'];
    $diarios = [];
    $coordenacoes = [];
    $praticas = [];
    foreach ($enrolled_courses as $diario) {
        unset($diario->summary);
        unset($diario->summaryformat);
        unset($diario->courseimage);
        preg_match(REGEX_CODIGO_COORDENACAO, $diario->shortname, $matches);
        if (count($matches)>0) {
            $coordenacoes[] = $diario;
        } elseif (strpos($diario->shortname, ".$username") !== false) {
            $praticas[] = $diario;
        } else {
            if (empty($semestre) && empty($disciplina) && empty($curso) && empty($q)) {
                $diarios[] = $diario;
            } else {
                preg_match(REGEX_CODIGO_DIARIO, $diario->shortname, $matches);
                if (
                        (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) &&
                        ( (empty($q)) || (!empty($q) && strpos(strtoupper($diario->shortname . ' ' . $diario->fullname), strtoupper($q)) !== false ) ) &&
                        ( ( (empty($semestre)) || (!empty($semestre) && $matches[REGEX_CODIGO_DIARIO_SEMESTRE] == $semestre) ) &&
                          ( (empty($disciplina)) || (!empty($disciplina) && $matches[REGEX_CODIGO_DIARIO_DISCIPLINA] == $disciplina)) &&
                          ( (empty($curso)) || (!empty($curso) && $matches[REGEX_CODIGO_DIARIO_CURSO] == $curso) ) )
                    ) {
                    $diarios[] = $diario;
                }
            }
        }
    }
    
    return [
        "semestres" => get_semestres($all_diarios),
        "disciplinas" => get_disciplinas($all_diarios),
        "cursos" => get_cursos($all_diarios, 'ASC'),
        "diarios" => $diarios,
        "coordenacoes" => $coordenacoes,
        "praticas" => $praticas,
    ];
}

function get_conversation_counts($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size) {
    /**
     * https://presencial.ava.ifrn.edu.br/lib/ajax/service.php?sesskey=SLJBgpK4mG&info=core_message_get_conversation_counts,core_message_get_unread_conversation_counts
     * [{"index": 0, "methodname": "core_message_get_conversation_counts", "args": {"userid": "903"}}, {"index": 1, "methodname": "core_message_get_unread_conversation_counts", "args": {"userid": "903"}}]
     * [{"error":false,"data":{"favourites":1,"types":{"1":1,"2":0,"3":0}}},{"error":false,"data":{"favourites":0,"types":{"1":0,"2":0,"3":0}}}]
     * https://presencial.ava.ifrn.edu.br/message/index.php
     */
}

function get_notification_counts($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size) {
    /**
     * https://presencial.ava.ifrn.edu.br/lib/ajax/service.php?sesskey=SLJBgpK4mG&info=message_popup_get_popup_notifications
     * [{"index": 0, "methodname": "message_popup_get_popup_notifications", "args": { "limit": 20, "offset": 0, "useridto": "903"}}]
     * [{"error":false,"data":{"notifications":[],"unreadcount":0}}]
     * https://presencial.ava.ifrn.edu.br/message/output/popup/notifications.php
     */
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

    function call() {
        try { 
            // $this->authenticate();           
            echo json_encode($this->do_call());
        } catch (Exception $ex) {
            http_response_code(500);
            if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
                echo json_encode(["error" => ["message" => "Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos."]]);
            } else {
                echo json_encode(["error" => ["message" => $ex->getMessage()]]);
            }
        }
    }

}
