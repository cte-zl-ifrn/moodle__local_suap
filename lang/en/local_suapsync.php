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
 * @package     local_suapsync
 * @category    string
 * @copyright   2022 Kelson Medeiros <kelsoncm@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Sync with SUAP';


# Auth token
$string['auth_token_header'] = 'Authentication token';
$string['auth_token_header_desc'] = 'Which will be the token used by SUAP to authenticate itself to this Moodle installation';
$string["auth_token"] = 'SUAP auth token';
$string["auth_token_desc"] = 'Which will be the token used by SUAP to authenticate itself to this Moodle installation';


# Top category
$string['top_category_header'] = 'Top category';
$string['top_category_header_desc'] = 'Top category default settings';

$string["top_category_idnumber"] = 'Top category id number';
$string["top_category_idnumber_desc"] = 'Used to identify where put new courses, if a category with this idnumber does not exists create a new category with this idnumber';
$string["top_category_name"] = 'Top category name';
$string["top_category_name_desc"] = 'Used only to create the new top category';
$string["top_category_parent"] = 'Top category parent';
$string["top_category_parent_desc"] = 'Used only to create the new top category';


# New user and new enrolment defaults
$string['user_and_enrolment_header'] = 'New user and new enrolment defaults';
$string['user_and_enrolment_header_desc'] = 'Top category default settings';

$string["default_user_preferences"] = 'Default user preferences';
$string["default_user_preferences_desc"] = 'All new user (student or teacher) will have this preferences. Use one line per preferece. Like a .ini file.';

$string["default_student_auth"] = 'Default method authentication for new student users';
$string["default_student_auth_desc"] = 'We recommend that you configure oAuth with SOAP, but... the choices are yours. But why oauth? Because your students can take advantage of the SSO and AVA portal for SUAP.';
$string["default_student_role_id"] = 'Default roleid for a new student enrolment';
$string["default_student_role_id_desc"] = 'Normally 5. Why? This is the Moodle default.';
$string["default_student_enrol_type"] = 'Default enrol_type for a new student enrolment';
$string["default_student_enrol_type_desc"] = 'Normally manual. Why? Because new students will be enrolled -manually- on SUAP and synched to Moodle';

$string["default_teacher_auth"] = 'Default method authentication for new teacher users';
$string["default_teacher_auth_desc"] = 'We recommend that you configure oAuth with SOAP, but... the choices are yours. But why oauth? Because your teachers can take advantage of the SSO and AVA portal for SUAP.';
$string["default_teacher_role_id"] = 'Default roleid for a new teacher enrolment';
$string["default_teacher_role_id_desc"] = 'Normally 3. Why? This is the Moodle default.';
$string["default_teacher_enrol_type"] = 'Default enrol_type for a new teacher enrolment';
$string["default_teacher_enrol_type_desc"] = 'Normally manual. Why? Because new teachers will be enrolled -manually- on SUAP and synched to Moodle';

$string["default_assistant_auth"] = 'Default method authentication for new assistant users';
$string["default_assistant_auth_desc"] = 'We recommend that you configure oAuth with SOAP, but... the choices are yours. But why oauth? Because your assistants can take advantage of the SSO and AVA portal for SUAP.';
$string["default_assistant_role_id"] = 'Default roleid for a new assistant enrolment';
$string["default_assistant_role_id_desc"] = 'Normally 3. Why? This is the Moodle default.';
$string["default_assistant_enrol_type"] = 'Default enrol_type for a new assistant enrolment';
$string["default_assistant_enrol_type_desc"] = 'Normally manual. Why? Because new assistants will be enrolled -manually- on SUAP and synched to Moodle';
