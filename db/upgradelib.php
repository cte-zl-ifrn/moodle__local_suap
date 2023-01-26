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
