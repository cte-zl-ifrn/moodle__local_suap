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

function ger_recordset_as_array($sql, $params) {
    global $DB;

    $result = [];
    foreach ($DB->get_recordset_sql($sql, $params) as $disciplina) {
        $result[] = $disciplina;
    }
    return $result;
}

function get_disciplinas($student, $username) {
    global $DB;
    $archetype = $student ? "r.archetype='student'" : "r.archetype<>'student'";
    $sql = "
    SELECT      cfd.value id, c.fullname label, COUNT(1) count
    FROM        {role_assignments} ra
                    INNER JOIN {role} r ON (ra.roleid = r.id and $archetype)
                    INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                        INNER JOIN {course} c ON (ctx.instanceid=c.id)
                            INNER JOIN {customfield_data} cfd ON (c.id=cfd.instanceid)
                                INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id AND cff.shortname='codigo_disciplina_suap')
                    INNER JOIN {user} u ON (ra.userid=u.id)
    WHERE       u.username = ?
    GROUP BY    cfd.value, c.fullname
    ";
    return ger_recordset_as_array($sql, [$username]);
}

function get_situacoes($student, $username) {
    return [
        ["id" => "all", "label" => "Sem filtro"],
        ["id" => "inprogress", "label" => "Em andamento"],
        ["id" => "future", "label" => "Não iniciados"],
        ["id" => "past", "label" => "Encerrados"],
        ["id" => "favourites", "label" => "Meus favoritos"],
        ["id" => "hidden", "label" => "Ocultados"],
    ];
}

function get_semestres($student, $username) {
    global $DB;
    $archetype = $student ? "r.archetype='student'" : "r.archetype<>'student'";
    $sql = "
    SELECT      cfd.value id, " . $DB->sql_concat($DB->sql_substr("cfd.value", 1, 4), "'.'", $DB->sql_substr("cfd.value", 5, 1)) . " label, COUNT(1) count
    FROM        {role_assignments} ra
                    INNER JOIN {role} r ON (ra.roleid = r.id and $archetype)
                    INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                        INNER JOIN {course} c ON (ctx.instanceid=c.id)
                            INNER JOIN {customfield_data} cfd ON (c.id=cfd.instanceid)
                                INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id AND cff.shortname='ano_mes_suap')
                    INNER JOIN {user} u ON (ra.userid=u.id)
    WHERE       u.username = ?
    GROUP BY    cfd.value
    ";
    return ger_recordset_as_array($sql, [$username]);
}

function get_diarios($student, $username, $disciplina, $situacao, $semestre, $q) {
    global $DB, $CFG;
    
    $params = [$username];
    $archetype = $student ? "r.archetype='student'" : "r.archetype<>'student'";
    $where = 'WHERE u.username = ?';

    if ($disciplina) {
        $where .= "\n  AND EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='codigo_disciplina_suap' AND cfd.value = ?)";
        $params[] = $disciplina;
    }

    // if ($situacao) {
    //     $where .= "\n  AND cfd.value = ?";
    //     $params[] = $disciplina;
    // }

    if ($semestre) {
        $where .= "\n  AND EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='ano_mes_suap' AND cfd.value = ?)";
        $params[] = $semestre;
    }

    if ($q) {
        $where .= "\n  AND c.fullname ILIKE ?";
        $params[] = "%$q%";
    }
    
    $sql = "
    SELECT      c.shortname codigo, 
                c.fullname titulo,
                0 progresso,
                'https://ead.ifrn.edu.br/portal/wp-content/uploads/2022/10/ifrn-logo.png' thumbnail,
                " . $DB->sql_concat("'$CFG->wwwroot/course/view.php'", "chr(63)", "'id='", "c.id") . " url
    FROM        {role_assignments} ra
                    INNER JOIN {role} r ON (ra.roleid = r.id AND $archetype)
                    INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                        INNER JOIN {course} c ON (ctx.instanceid=c.id)
                    INNER JOIN {user} u ON (ra.userid=u.id)
    $where
    ";
    return [
        "disciplinas" => get_disciplinas($student, $username),
        "situacoes" => get_situacoes($student, $username),
        "semestres" => get_semestres($student, $username),
        "diarios" => ger_recordset_as_array($sql, $params),
        "informativos" => [],
    ];
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
