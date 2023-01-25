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

namespace suap;

require_once("$CFG->dirroot/course/externallib.php");
require_once("$CFG->dirroot/enrol/externallib.php");

define("REGEX_CODIGO_DIARIO", '/^(\d\d\d\d\d)\.(\d*)\.(\d*)\.(.*)\.(.*\..*)$/');
define("REGEX_CODIGO_DIARIO_ELEMENTS_COUNT", 6);
define("REGEX_CODIGO_DIARIO_SEMESTRE", 1);
define("REGEX_CODIGO_DIARIO_PERIODO", 2);
define("REGEX_CODIGO_DIARIO_CURSO", 3);
define("REGEX_CODIGO_DIARIO_TURMA", 4);
define("REGEX_CODIGO_DIARIO_COMPONENTE", 5);

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
            $curso = $matches[REGEX_CODIGO_DIARIO_COMPONENTE];
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
            $disciplina = $matches[REGEX_CODIGO_DIARIO_COMPONENTE];
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
    return ger_recordset_as_array("
            SELECT      c.shortname id,
                        c.fullname label
            FROM        {role_assignments} ra
                            INNER JOIN {user} u ON (ra.userid=u.id)
                            INNER JOIN {role} r ON (ra.roleid = r.id)
                            INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                                INNER JOIN {course} c ON (ctx.instanceid=c.id)
            WHERE u.username = ?
            ORDER BY 2 ASC
        ",
        [$username]);
}

function get_diarios($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size) {
    global $DB, $CFG, $USER;

    // $and_where = '';
    // $params = [];

    // // if ($semestre) {
    // //     $where .= "\n  AND (
    // //             EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='ano_mes_suap' AND cfd.value = ?)
    // //             OR c.shortname LIKE ?
    // //     )";
    // //     $params[] = $semestre;
    // //     $params[] = "$disciplina.%";
    // // }

    // // // if ($situacao) {
    // // //     $where .= "\n  AND (
    // // //         EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='codigo_disciplina_suap' AND cfd.value = ?)
    // // //         OR c.shortname LIKE ?
    // // //     )";
    // // //     $params[] = $situacao;
    // // // }

    // // if ($disciplina) {
    // //     $where .= "\n  AND (
    // //         EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='codigo_disciplina_suap' AND cfd.value = ?)
    // //         OR c.shortname LIKE ?
    // //     )";
    // //     $params[] = $disciplina;
    // //     $params[] = "%.$disciplina";
    // // }

    // // if ($curso) {
    // //     $where .= "\n  AND (
    // //         EXISTS (SELECT 1 FROM {customfield_data} cfd INNER JOIN {customfield_field} cff ON (cfd.fieldid=cff.id) WHERE c.id=cfd.instanceid AND cff.shortname='codigo_disciplina_suap' AND cfd.value = ?)
    // //         OR c.shortname LIKE ?
    // //     )";
    // //     $params[] = $curso;
    // //     $params[] = "%.$curso.%";
    // // }

    // // if ($q) {
    // //     $where .= "\n  AND c.fullname ILIKE ?";
    // //     $params[] = "%$q%";
    // // }

    // $ordering = in_array($ordenacao, ['fullname', 'shortname', 'ul.timeaccess desc']) ? "ORDER BY $ordenacao" : null;

    $sql = "
    SELECT      c.id, 
                c.shortname shortname,
                c.fullname fullname
    FROM        {role_assignments} ra
                    INNER JOIN {user} u ON (ra.userid=u.id)
                    INNER JOIN {role} r ON (ra.roleid = r.id)
                    INNER JOIN {context} ctx ON (ra.contextid=ctx.id AND ctx.contextlevel=50)
                        INNER JOIN {course} c ON (ctx.instanceid=c.id)
    WHERE u.username = ?
    ";

        $USER = $DB->get_record('user', ['username' => $username]);
    // $USER = $DB->get_record('user', ['username' => '1723011']);
    $all_diarios = get_recordset_as_array($sql, [$USER->username]);
    // $all_courses = \core_enrol_external::get_users_courses($USER->id);
    // $all_diarios = \core_course_external::get_enrolled_courses_by_timeline_classification('all', 0, 0, $ordenacao)['courses'];
    $filtered_diarios = \core_course_external::get_enrolled_courses_by_timeline_classification($situacao, 0, 0, $ordenacao)['courses'];
    foreach ($filtered_diarios as $diario) {
        $diario->summary = null;
        $diario->summaryformat = null;
    }

    return [
        "semestres" => get_semestres($all_diarios),
        "disciplinas" => get_disciplinas($all_diarios),
        "cursos" => get_cursos($all_diarios, 'ASC'),
        "diarios" => $filtered_diarios,
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
