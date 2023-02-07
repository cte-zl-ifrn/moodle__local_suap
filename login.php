<?php
require_once('../../config.php');

if (property_exists($_SESSION['USER'], 'sesskey')) {
    $wantsurl = "$CFG->wwwroot{$_SERVER['REQUEST_URI']}";
    $sesskey = $_SESSION['USER']->sesskey;
    redirect("$CFG->wwwroot/auth/oauth2/login.php?id=1&sesskey=$sesskey&wantsurl=$wantsurl");
} else {
    // require_once "../../login/logout.php";
    redirect("$CFG->wwwroot/auth/oauth2/login.php?errorcode=4&id=1&sesskey=$sesskey&wantsurl=$wantsurl");
    echo "Sessão inválida. Por favor, <a href='$CFG->wwwroot'>tente novamente</a>.";
}
