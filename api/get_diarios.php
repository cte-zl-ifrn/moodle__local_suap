<?php

namespace local_suap;

require_once('../../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");


class get_diarios_service extends \local_suap\service {

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
        return \local_suap\get_recordset_as_array("
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
        
        $all_diarios = $this->get_all_diarios($USER->username);
        $enrolled_courses = \core_course_external::get_enrolled_courses_by_timeline_classification($situacao, 0, 0, $ordenacao)['courses'];
        $diarios = [];
        $coordenacoes = [];
        $praticas = [];
        foreach ($enrolled_courses as $diario) {
            unset($diario->summary);
            unset($diario->summaryformat);
            unset($diario->courseimage);
            
            if (preg_match(REGEX_CODIGO_COORDENACAO, $diario->shortname)) {
                $coordenacoes[] = $diario;
            } elseif (preg_match(REGEX_CODIGO_PRATICA, $diario->shortname)) {
                $praticas[] = $diario;
            } elseif (!empty($semestre . $disciplina . $curso . $q) ) {
                preg_match(REGEX_CODIGO_DIARIO, $diario->shortname, $matches);
                if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
                    if (
                            ( (empty($q)) || (!empty($q) && strpos(strtoupper($diario->shortname . ' ' . $diario->fullname), strtoupper($q)) !== false ) ) &&
                            ( 
                                ( (empty($semestre)) || (!empty($semestre) && $matches[REGEX_CODIGO_DIARIO_SEMESTRE] == $semestre) ) &&
                                ( (empty($disciplina)) || (!empty($disciplina) && $matches[REGEX_CODIGO_DIARIO_DISCIPLINA] == $disciplina)) &&
                                ( (empty($curso)) || (!empty($curso) && $matches[REGEX_CODIGO_DIARIO_CURSO] == $curso) ) 
                            )
                        ) {
                        $diarios[] = $diario;
                    }
                }
            } else {
                // $diario->fullname = $diario->fullname . ' []';
                $diarios[] = $diario;
            }
        }
        
        return [
            "semestres" => $this->get_semestres($all_diarios),
            "disciplinas" => $this->get_disciplinas($all_diarios),
            "cursos" => $this->get_cursos($all_diarios, 'ASC'),
            "diarios" => $diarios,
            "coordenacoes" => $coordenacoes,
            "praticas" => $praticas,
        ];
    }

    function do_call() {
        return $this->get_diarios(
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
