<?php
require_once('../../config.php');

redirect("$CFG->wwwroot/auth/oauth2/login.php?id=1&wantsurl=$CFG->wwwroot&sesskey={$_SESSION['USER']->sesskey}");
