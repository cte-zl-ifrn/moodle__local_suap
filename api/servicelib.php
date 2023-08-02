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


class service {

    function authenticate() {
        $sync_up_auth_token = config('auth_token');

        $headers = getallheaders();
        if (!array_key_exists('Authentication', $headers)) {
            throw new \Exception("Bad Request - Authentication not informed", 400);
        }

        if ("Token $sync_up_auth_token" != $headers['Authentication']) {
            throw new \Exception("Unauthorized", 401);
        }
    }

    function call() {
        // $this->authenticate();
        echo json_encode($this->do_call());
    }

}