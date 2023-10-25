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

$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'ASC';

// Número de itens por página
$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Consulta SQL personalizada para buscar os registros com LIMIT e OFFSET
$sql = "
    SELECT id, timecreated, processed
    FROM {suap_enrolment_to_sync}
    ORDER BY id $ordenacao, timecreated $ordenacao, processed $ordenacao
    LIMIT :limit OFFSET :offset
";

$params = [
    'limit' => $itensPorPagina,
    'offset' => ($paginaAtual - 1) * $itensPorPagina,
];
$registros = $DB->get_records_sql($sql, $params);

$statuses = [0 => "Não processado", 1 => "Sucesso", 2 => 'Falha'];
foreach ($registros as $key => $value) {
    $value->status = $statuses[$value->processed];
} 

// Consulta SQL para contar o total de registros
$sqlTotalRegistros = "SELECT COUNT(*) as total FROM {suap_enrolment_to_sync}" ;

$totalRegistros = $DB->get_field_sql($sqlTotalRegistros);

$numeroTotalDePaginas = ceil($totalRegistros / $itensPorPagina);

$primeirasPaginas = 5;
$ultimasPaginas = 3;

$paginaInicio = max(2, $paginaAtual - floor($primeirasPaginas / 2));
$paginaFim = $paginaInicio + $primeirasPaginas - 1;

$registrosPaginaAtual = array_slice($registros, 0, $itensPorPagina);

// verifica o numero total de páginas com o range de paginação, para delimitar um fim para a paginação, caso outras páginas sejam clicadas
if ( in_array($numeroTotalDePaginas, range($paginaInicio, $paginaFim)) ) {
    $primeirosCinco = range($paginaInicio, $numeroTotalDePaginas);
}else{
    $primeirosCinco = range($paginaInicio, $paginaFim);
}

$ultimosTres = range($numeroTotalDePaginas, $numeroTotalDePaginas);

 $paginacaoVariada = [];

// Verifica se tem mais de 13 páginas. Se tiver, irá acrescentar a lógica de aparecer as 3 ultimas.
if ($numeroTotalDePaginas < $primeirasPaginas + $ultimasPaginas) {
    $paginacaoVariada = range($paginaInicio, $paginaFim);
    
}else{

    if($paginaAtual < $numeroTotalDePaginas-3 && $paginaAtual >= 5){
        echo("TO AQUI");
        $mergeUnique= array_unique(array_merge($primeirosCinco,['...'],$ultimosTres));      
        $paginacaoVariada = array_merge(['...'], $mergeUnique);

    }elseif($paginaAtual < $numeroTotalDePaginas-3){
        $mergeUnique= array_unique(array_merge($primeirosCinco,['...'],$ultimosTres));       
        $paginacaoVariada = array_merge($mergeUnique);

    }elseif($paginaAtual >= 5){
        $mergeUnique= array_unique(array_merge($primeirosCinco,$ultimosTres));        
        $paginacaoVariada = array_merge(['...'], $mergeUnique);
    }else{
        $paginacaoVariada = array_unique(array_merge($primeirosCinco, $ultimosTres));

    }
    
    // if($paginaAtual >= 5){
    //     //  $paginacaoVariadaInicio = '...'; 
    //     //  $mergePaginaPrimeirosCincoInicio[] = array_push($primeirosCinco, $paginacaoVariadaInicio);
    //     // foreach($mergePaginaPrimeirosCincoInicio as $t){
    //     //     echo($t.'-');
    //     // }
        
    //     //  $paginacaoInicio= array_unique(array_merge($mergePaginaPrimeirosCincoInicio, $ultimosTres));
    //     //  $paginacaoVariada = $mergePaginaPrimeirosCincoInicio; 
    //     $mergeUnique= array_unique(array_merge($primeirosCinco,$ultimosTres));
        

        
    //     $paginacaoVariada = array_merge(['...'], $mergeUnique);
    //     echo("OPA");
    // }
    

}

$templatecontext = [
    'linhas' => $registrosPaginaAtual, 
    'paginas' => $paginacaoVariada,
];

echo $OUTPUT->render_from_template('local_suap/index', $templatecontext);
echo $OUTPUT->footer();
