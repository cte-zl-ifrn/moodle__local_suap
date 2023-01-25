<?php
namespace local_suap\event;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/suap/db/upgrade.php');
require_once(__DIR__.'/upgradelib.php');

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_auth_suap_install() {
    suap_bulk_course_custom_field();
    suap_bulk_user_custom_field();
}
