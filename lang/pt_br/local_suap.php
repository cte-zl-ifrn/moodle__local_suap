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
 * Plugin strings are defined here.
 *
 * @package     local_suap
 * @category    string
 * @copyright   2022 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SUAP Integration';


# Auth token
$string['auth_token_header'] = 'Token de autenticação';
$string['auth_token_header_desc'] = 'Qual será o token utilizado pelo SUAP para se autenticar nesta instalação do Moodle';
$string["auth_token"] = 'SUAP auth token';
$string["auth_token_desc"] = 'Qual será o token utilizado pelo SUAP para se autenticar nesta instalação do Moodle';


# Top category
$string['top_category_header'] = 'Categoria principal';
$string['top_category_header_desc'] = 'Configurações padrão da categoria principal';
$string["top_category_iznumber"] = 'Número de identificação da categoria superior';
$string["top_category_idnumber_desc"] = 'Usado para identificar onde colocar novos cursos, caso não exista uma categoria com este idnumber crie uma nova categoria com este idnumber';
$string["top_category_name"] = 'Nome da categoria principal';
$string["top_category_name_desc"] = 'Usado apenas para criar a nova categoria principal';
$string["top_category_parent"] = 'Pai de categoria superior';
$string["top_category_parent_desc"] = 'Usado apenas para criar a nova categoria principal';


# New user and new enrolment defaults
$string['user_and_enrolment_header'] = 'Novo usuário e novos padrões de inscrição';
$string['user_and_enrolment_header_desc'] = 'Configurações padrão da categoria principal';

$string["default_user_preferences"] = 'Preferências padrão do usuário';
$string["default_user_preferences_desc"] = 'Todo novo usuário (aluno ou professor) terá essas preferências. Use uma linha por preferência. Como um arquivo .ini.';

$string["default_student_auth"] = 'Autenticação de método padrão para novos usuários alunos';
$string["default_student_auth_desc"] = 'Recomendamos que você configure oAuth com SOAP, mas... as escolhas são suas. Mas por que oauth? Porque seus alunos podem usufruir do portal SSO e AVA para SUAP.';
$string["default_student_role_id"] = 'Roleid padrão para uma inscrição de aluno';
$string["default_student_role_id_desc"] = 'Normalmente 5. Por quê? Este é o padrão do Moodle.';
$string["default_student_enrol_type"] = 'Enrol_type padrão para uma inscrição de aluno inativa';
$string["default_student_enrol_type_desc"] = 'Normalmente manuais. Por que? Porque os novos alunos serão matriculados no SUAP e sincronizados com o Moodle';

$string["default_teacher_auth"] = 'Autenticação de método padrão para novos usuários professores';
$string["default_teacher_auth_desc"] = 'Recomendamos que você configure oAuth com SOAP, mas... as escolhas são suas. Mas por que oauth? Porque seus alunos podem usufruir do portal SSO e AVA para SUAP.';
$string["default_teacher_role_id"] = 'Roleid padrão para uma inscrição como professor';
$string["default_teacher_role_id_desc"] = 'Normalmente 5. Por quê? Este é o padrão do Moodle.';
$string["default_teacher_enrol_type"] = 'Enrol_type padrão para uma inscrição como professor';
$string["default_teacher_enrol_type_desc"] = 'Normalmente manuais. Por que? Porque os novos alunos serão matriculados no SUAP e sincronizados com o Moodle';

$string["default_assistant_auth"] = 'Autenticação de método padrão para novos usuários tutores';
$string["default_assistant_auth_desc"] = 'Recomendamos que você configure oAuth com SOAP, mas... as escolhas são suas. Mas por que oauth? Porque seus alunos podem usufruir do portal SSO e AVA para SUAP.';
$string["default_assistant_role_id"] = 'Roleid padrão para uma inscrição como tutor';
$string["default_assistant_role_id_desc"] = 'Normalmente 5. Por quê? Este é o padrão do Moodle.';
$string["default_assistant_enrol_type"] = 'Enrol_type padrão para uma inscrição como tutor';
$string["default_assistant_enrol_type_desc"] = 'Normalmente manuais. Por que? Porque os novos alunos serão matriculados no SUAP e sincronizados com o Moodle';

$string["default_instructor_auth"] = 'Autenticação de método padrão para novos usuários moderadores em salas de coordenação';
$string["default_instructor_auth_desc"] = 'Recomendamos que você configure oAuth com SOAP, mas... as escolhas são suas. Mas por que oauth? Porque seus alunos podem usufruir do portal SSO e AVA para SUAP.';
$string["default_instructor_role_id"] = 'Roleid padrão para uma inscrição como moderador em salas de coordenação';
$string["default_instructor_role_id_desc"] = 'Normalmente 4. Por quê? Este é o padrão do Moodle para professores que não podem editar.';
$string["default_instructor_enrol_type"] = 'Enrol_type padrão para uma inscrição como moderador em salas de coordenação';
$string["default_instructor_enrol_type_desc"] = 'Normalmente manuais. Por que? Porque os novos moderadores em salas de coordenação serão matriculados no SUAP e sincronizados com o Moodle';

$string["sync_up_enrolments_task"] = 'Sync Up Enrolments Task';