<?php
require_once("../../config.php");
$pgversion = $DB->get_record_sql('SELECT version()');

echo "<pre>\n";
echo "\nHostname: " . gethostname();
echo "\nPHP: " . phpversion();
echo "\nMoodle: {$CFG->release}";
echo "\nBanco: {$pgversion->version}";
var_dump($CFG->dboptions);
echo "\nWWW root: {$CFG->wwwroot}";
echo "\nSession handler: {$CFG->session_handler_class}";
echo "\nReverse proxy: {$CFG->reverseproxy}";
echo "\nSSL proxy: {$CFG->sslproxy}";
echo "\nCache JS: {$CFG->cachejs}";
echo "\nCache Template: {$CFG->cachetemplates}";
echo "\nCache Lang String: {$CFG->langstringcache}";
echo "\nRota no .htaccess: OK";
echo "\nTudo bem até aqu: sim.";
