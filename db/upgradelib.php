<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade helper functions are defined here.
 *
 * @package     local_suap
 * @category    upgrade
 * @copyright   2022 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see         https://docs.moodle.org/dev/Data_definition_API
 * @see         https://docs.moodle.org/dev/XMLDB_creating_new_DDL_functions
 * @see         https://docs.moodle.org/dev/Upgrade_API
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/suap/locallib.php');
function suap_save_course_custom_field($categoryid, $shortname, $name, $type='text', $configdata='{"required":"0","uniquevalues":"0","displaysize":50,"maxlength":250,"ispassword":"0","link":"","locked":"0","visibility":"0"}') {
    return get_or_create(
        'customfield_field', 
        ['shortname'=>$shortname], 
        ['categoryid' => $categoryid, 'name' => $name, 'type' => $type, 'configdata' => $configdata, 'timecreated'=>time(), 'timemodified'=>time(), 'sortorder'=>get_last_sort_order('customfield_field')]
    );
}


function suap_save_user_custom_field($categoryid, $shortname, $name, $datatype='text', $visible=1, $p1=NULL, $p2=NULL) {
    return get_or_create(
        'user_info_field', 
        ['shortname'=>$shortname], 
        ['categoryid'=>$categoryid, 'name'=>$name, 'datatype'=>$datatype, 'visible'=>$visible, 'param1'=>$p1, 'param2'=>$p2]
    );
}


function suap_bulk_course_custom_field() {
    global $DB;
    $cid = get_or_create(
        'customfield_category', 
        ['name' => 'SUAP', 'component'=>'core_course', 'area'=>'course'], 
        ['sortorder'=>get_last_sort_order('customfield_category'), 'itemid'=>0, 'contextid'=>1, 'descriptionformat'=>0, 'timecreated'=>time(), 'timemodified'=>time()]
    )->id;
    suap_save_course_custom_field($cid, 'campus_id', 'ID do campus');
    suap_save_course_custom_field($cid, 'campus_descricao', 'Descrição do campus');
    suap_save_course_custom_field($cid, 'campus_sigla', 'Sigla do campus');

    suap_save_course_custom_field($cid, 'curso_id', 'ID do curso');
    suap_save_course_custom_field($cid, 'curso_codigo', 'Código do curso');
    suap_save_course_custom_field($cid, 'curso_descricao', 'Descrição do curso');
    suap_save_course_custom_field($cid, 'curso_nome', 'Nome do curso');
    suap_save_course_custom_field($cid, 'curso_sala_coordenacao', 'É sala de coordenação');

    suap_save_course_custom_field($cid, 'turma_id', 'ID da turma');
    suap_save_course_custom_field($cid, 'turma_codigo', 'Código da turma');

    suap_save_course_custom_field($cid, 'turma_ano_periodo', 'Ano/Semestre da turma');

    suap_save_course_custom_field($cid, 'diario_id', 'ID do diario');
    suap_save_course_custom_field($cid, 'diario_situacao', 'Situação do diario');

    suap_save_course_custom_field($cid, 'disciplina_id', 'ID da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_descricao', 'Descrição da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_descricao_historico', 'Descrição da disciplina que constará no histórico');
    suap_save_course_custom_field($cid, 'disciplina_sigla', 'Sigla da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_periodo', 'Período da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_tipo', 'Tipo da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_optativo', 'Optativo da disciplina');
    suap_save_course_custom_field($cid, 'disciplina_qtd_avaliacoes', 'Quantidade de avaliações da disciplina');
}


function suap_bulk_user_custom_field() {
    global $DB;

    $cid = get_or_create('user_info_category', ['name' => 'SUAP'], ['sortorder'=>get_last_sort_order('user_info_category')])->id;

    suap_save_user_custom_field($cid, 'email_google_classroom', 'E-mail @escolar (Google Classroom');
    suap_save_user_custom_field($cid, 'email_academico', 'E-mail @academico (Microsoft)');

    suap_save_user_custom_field($cid, 'campus_id', 'ID do campus');
    suap_save_user_custom_field($cid, 'campus_descricao', 'Descrição do campus');
    suap_save_user_custom_field($cid, 'campus_sigla', 'Sigla do campus');

    suap_save_user_custom_field($cid, 'curso_id', 'ID do curso');
    suap_save_user_custom_field($cid, 'curso_codigo', 'Código do curso');
    suap_save_user_custom_field($cid, 'curso_descricao', 'Descrição do curso');

    suap_save_user_custom_field($cid, 'turma_id', 'ID da turma');
    suap_save_user_custom_field($cid, 'turma_codigo', 'Código da turma');
    
    suap_save_user_custom_field($cid, 'polo_id', 'ID da polo');
    suap_save_user_custom_field($cid, 'polo_nome', 'Nome da polo');
    
    suap_save_user_custom_field($cid, 'ingresso_periodo', 'Período de ingresso');
}

>>>>>>> 9c58299febd1b92d6363caa1fd2cd37ee5a50b74
function local_suap_migrate($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion == 0) {
        # suap_enrolment_to_sync
        $table = new xmldb_table("suap_enrolment_to_sync");
        $table->add_field("id",             XMLDB_TYPE_INTEGER, '10',       XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE,  null, null, null);
        $table->add_field("json",           XMLDB_TYPE_TEXT,    'medium',   XMLDB_UNSIGNED, null,          null,            null, null, null);
        $table->add_field("timecreated",    XMLDB_TYPE_INTEGER, '10',       XMLDB_UNSIGNED, XMLDB_NOTNULL, null,            null, null, null);
        $table->add_field("attempts",        XMLDB_TYPE_INTEGER, '10',       XMLDB_UNSIGNED, XMLDB_NOTNULL, null,            null, null, null);

        $table->add_key("primary",      XMLDB_KEY_PRIMARY,  ["id"],         null,       null);
        $status = $dbman->create_table($table);

    }
    return true;
}
