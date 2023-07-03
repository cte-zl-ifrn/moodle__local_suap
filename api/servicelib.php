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
            dienow("Bad Request - Authentication not informed", 400);
        }

        if ("Token $sync_up_auth_token" != $headers['Authentication']) {
            dienow("Unauthorized", 401);
        }
    }

    function call() {
        try { 
            $this->authenticate();
            echo json_encode($this->do_call());
        } catch (Exception $ex) {
            if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
                dienow("Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos.", 509);
            } else {
                dienow($ex->getMessage(), 500);
            }
        }
    }

}