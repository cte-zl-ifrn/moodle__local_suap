<?php
namespace local_suap\admin;

require_once(\dirname(\dirname(\dirname(__DIR__))) . '/config.php');

$PAGE->set_url(new \moodle_url('/local/suap/admin/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('SUAP Sync Admin');
  

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo "Fazes o quê aqui?";
    echo $OUTPUT->footer();
    die();
}
  
echo $OUTPUT->header();
$linhas = array_values($DB->get_records("suap_enrolment_to_sync", null, "id ASC", "id, timecreated, processed"));
$statuses = [0=>"Não processado", 1=>"Sucesso", 2=>'Falha'];
foreach ($linhas as $key => $value) {
    $value->status = $statuses[$value->processed];
} 

$totalRegistros = count($linhas); 
$itensPorPagina = 10; 

$paginaAtual = isset($_GET['pagina']) ? $_GET['pagina'] : 1;

$totalPaginas = ceil($totalRegistros / $itensPorPagina);

$indiceInicio = ($paginaAtual - 1) * $itensPorPagina;

$registrosPaginaAtual = array_slice($linhas, $indiceInicio, $itensPorPagina);

$templatecontext = [
    'linhas' => $registrosPaginaAtual, 
    'paginas' => range(1, $totalPaginas),
];


echo $OUTPUT->render_from_template('local_suap/index', $templatecontext);
echo $OUTPUT->footer();
