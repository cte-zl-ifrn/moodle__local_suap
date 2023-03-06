<?php
namespace local_suap;

require_once('../../../config.php');
require_once('../../../course/lib.php');
require_once('../../../user/lib.php');
require_once('../../../group/lib.php');
require_once("../../../enrol/locallib.php");
require_once("../../../enrol/externallib.php");
require_once("../../../enrol/externallib.php");
require_once("../locallib.php");
require_once("servicelib.php");


class sync_up_enrolments_service extends service {


    function validate_json() {
        if (!array_key_exists('jsonstring', $_POST)) {
            dienow("Atributo \'jsonstring\' é obrigatório.", 550);
        }

        $json = json_decode($_POST['jsonstring']);

        if (empty($json)) {
            dienow("Atributo 'jsonstring' sem JSON ou com JSON inválido.", 551);
        }

        // TODO: Validar o JSON usando um json-schema, mas só tem isso usando Composer
        
        return $json;
    }


    function call() {
        global $CFG;
    
        try { 
            $this->authenticate();
            
            $json = $this->validate_json();
    
            $diario_id = $this->sync_struct($json, false);
            $sala_id = $this->sync_struct($json, true);

            echo json_encode(
                [
                    "url" => "{$CFG->wwwroot}/course/view.php?id={$diario_id}",
                    "url_sala_coordenacao" => "{$CFG->wwwroot}/course/view.php?id={$sala_id}"
                ]
            );
        } catch (Exception $ex) {
            http_response_code(500);
            if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
                echo json_encode(["error" => ["message" => "Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos."]]);
            } else {
                echo json_encode(["error" => ["message" => $ex->getMessage()]]);
            }
        }
    }


    function sync_struct($json, $room=false) {
        global $CFG;
    
        try { 
            $categoryid = $this->sync_category_hierarchy($json, $room);
            $courseid = $this->sync_course($categoryid, $json, $room);
            $context = \context_course::instance($courseid);
        
            $issuerid = $this->sync_suap_issuer();

            $principal_config = $this->get_enrolment_config($courseid, 'teacher');
            $moderador_config = $this->get_enrolment_config($courseid, 'assistant');
            foreach ($json->professores as $professor) {
                $userid = $this->sync_user($professor, $issuerid);
                $tipo = strtolower($professor->tipo);
                $conf = $tipo == 'principal' || $tipo == 'formador' ? $principal_config : $moderador_config;
                $this->sync_enrol($context->id, $userid, $conf->enrolid, $conf->roleid);
            }
    
            $aluno_config = $this->get_enrolment_config($courseid, 'student');
            foreach ($json->alunos as $aluno) {
                $userid = $this->sync_user($aluno, $issuerid);
                $this->sync_enrol($context->id, $userid, $aluno_config->enrolid, $aluno_config->roleid);
                $this->sync_group($courseid, $userid, $aluno->username, $aluno->polo, $json->turma, $room);
            }
    
            $issuerid = $this->sync_suap_issuer();

            return $courseid;
        } catch (Exception $ex) {
            http_response_code(500);
            if ($ex->getMessage() == "Data submitted is invalid (value: Data submitted is invalid)") {
                echo json_encode(["error" => ["message" => "Ocorreu uma inconsistência no servidor do AVA. Este erro é conhecido e a solução dele já está sendo estudado pela equipe de desenvolvimento. Favor tentar novamente em 5 minutos."]]);
            } else {
                echo json_encode(["error" => ["message" => $ex->getMessage()]]);
            }
        }
    }


    function sync_category_hierarchy($data, $room=false) {
        $top_category_idnumber = config('top_category_idnumber') ?: 'diarios'; 
        $top_category_name = config('top_category_name') ?: 'Diários';
        $top_category_parent = config('top_category_parent') ?: 0;
        $ano_periodo = substr($data->turma->codigo, 0, 4) . "." . substr($data->turma->codigo, 4, 1);

        $top_category = $this->sync_category($top_category_idnumber, $top_category_name, $top_category_parent);
        $campus = $this->sync_category($data->campus->sigla, $data->campus->descricao, $top_category->id);
        $curso = $this->sync_category($data->curso->codigo, $data->curso->nome, $campus->id);
        if ($room) {
            return $curso->id;
        }
        $semestre = $this->sync_category("{$data->curso->codigo}.{$ano_periodo}", $ano_periodo, $curso->id);
        $turma = $this->sync_category($data->turma->codigo, $data->turma->codigo, $semestre->id);
        return $turma->id;
    }


    function sync_category($idnumber, $name, $parent){
        global $DB;
    
        $course_category = $DB->get_record('course_categories', ['idnumber'=>$idnumber]);
        if (empty($course_category)) {
            $course_category = \core_course_category::create(['name'=>$name, 'idnumber'=>$idnumber, 'parent'=>$parent]);
        }
    
        return $course_category;
    }


    function sync_course($categoryid, $json, $room){
        global $DB;

        $diario_code = $room ? "{$json->campus->sigla}.{$json->curso->codigo}" : "{$json->turma->codigo}.{$json->componente->sigla}";
        $course = $DB->get_record('course', ['shortname'=>$diario_code]);
        if (!$course) {
            if ($room) {
                $data = (object) [
                    "category"=>$categoryid,
                    "fullname"=>$room ? "Sala de coordenação do curso {$json->curso->nome}" : $json->componente->descricao,
                    "shortname"=>$diario_code,
                    "idnumber"=>$diario_code,
                    "visible"=>1,
                    "enablecompletion"=>1,
                    // "startdate"=>time(),
                    "showreports"=>1,
                    "completionnotify"=>1,

                    "customfield_campus_id"=> $json->campus->id,
                    "customfield_campus_descricao"=> $json->campus->descricao,
                    "customfield_campus_sigla"=> $json->campus->sigla,

                    "customfield_curso_id"=> $json->curso->id,
                    "customfield_curso_codigo"=> $json->curso->codigo,
                    "customfield_curso_descricao"=> $json->curso->descricao,
                    "customfield_curso_nome"=> $json->curso->nome,
                    "customfield_curso_sala_coordenacao"=> 'Sim',
                ];
            } else {
                $data = (object) [
                    "category"=>$categoryid,
                    "fullname"=>$room ? "Sala de coordenação do curso {$json->curso->nome}" : $json->componente->descricao,
                    "shortname"=>$diario_code,
                    "idnumber"=>$diario_code,
                    "visible"=>1,
                    "enablecompletion"=>1,
                    // "startdate"=>time(),
                    "showreports"=>1,
                    "completionnotify"=>1,
                    
                    "customfield_campus_id"=> $json->campus->id,
                    "customfield_campus_descricao"=> $json->campus->descricao,
                    "customfield_campus_sigla"=> $json->campus->sigla,
    
                    "customfield_curso_id"=> $json->curso->id,
                    "customfield_curso_codigo"=> $json->curso->codigo,
                    "customfield_curso_descricao"=> $json->curso->descricao,
                    "customfield_curso_nome"=> $json->curso->nome,
                    "customfield_curso_sala_coordenacao"=> 'Não',
    
                    "customfield_turma_id"=> $json->turma->id,
                    "customfield_turma_codigo"=> $json->turma->codigo,
    
                    "customfield_turma_ano_periodo"=> substr($json->turma->codigo, 0, 4) . "." . substr($json->turma->codigo, 4, 1),
    
                    "customfield_diario_id"=> $json->diario->id,
                    "customfield_diario_situacao"=> $json->diario->situacao,
    
                    "customfield_disciplina_id"=> $json->componente->id,
                    "customfield_disciplina_sigla"=> $json->componente->sigla,
                    "customfield_disciplina_descricao"=> $json->componente->descricao,
                    "customfield_disciplina_descricao_historico"=> $json->componente->descricao_historico,
                    // "customfield_disciplina_periodo"=> $json->componente->periodo,
                    "customfield_disciplina_tipo"=> $json->componente->tipo,
                    "customfield_disciplina_optativo"=> $json->componente->optativo,
                    "customfield_disciplina_qtd_avaliacoes"=> $json->componente->qtd_avaliacoes,
                ];
            }

            $course = create_course($data);
        }
        return $course->id;
    }


    function sync_suap_issuer() {
        return create_or_update(
            'oauth2_issuer', 
            [
                'name'=>'suap'
            ],
            [
                'image'=>'https://ead.ifrn.edu.br/portal/wp-content/uploads/2020/08/SUAP.png', 
                'loginscopes'=>'identificacao email',
                'loginscopesoffline'=>'identificacao email documentos_pessoais',
                'baseurl'=>'',
                'loginparams'=>'',
                'loginparamsoffline'=>'',
                'alloweddomains'=>'',
                'enabled'=>1,
                'showonloginpage'=>1,
                'basicauth'=>0,
                'sortorder'=>0,
                'timecreated'=>time(),
                'timemodified'=>time(),
                'usermodified'=>2
            ],
            [
                'requireconfirmation'=>0
            ],
            [
                'clientid'=>'changeme',
                'clientsecret'=>'changeme'
            ]
        )->id;
    }


    function get_enrolment_config($courseid, $type) {
        $role_id = config("default_{$type}_role_id");
        $enrol_type = config("default_{$type}_enrol_type");
        $enrol_id = get_or_create(
            'enrol', 
            ['enrol'=>$enrol_type, 'courseid'=>$courseid, 'roleid'=>$role_id],
            ['timecreated'=>time(), 'timemodified'=>time()]
        )->id;
        return (object)['roleid'=>$role_id, 'enrol_type'=>$enrol_type, 'enrolid'=>$enrol_id];
    }


    function sync_user($user, $issuerid){
        global $DB;
        $username = property_exists($user, 'matricula') ? $user->matricula : $user->login;
        $status = property_exists($user, 'situacao') ? $user->situacao : $user->status;
        if (property_exists($user, 'matricula')) {
            $auth = 'default_student_auth';
        } else {
            if ($user->tipo == 'Principal') {
                $auth = 'default_teacher_auth';
            } else {
                $auth = 'default_assistant_auth';
            }
        }

        $usuario = $DB->get_record("user", ["username" => $username]);
        $nome_parts = explode(' ', $user->nome);
        $lastname = array_pop($nome_parts);
        $firstname = implode(' ', $nome_parts);
        $common = [
            'lastname'=>$lastname,
            'firstname'=>$firstname,
            'auth'=>config($auth),
            'email'=> !empty($user->email) ? $user->email : $user->email_secundario,
            'suspended'=>($status == 'ativo' ? 0 : 1),
        ];
        $insert_only = [
            'username'=>$username,
            'password'=>'!aA1' . uniqid(),
            'timezone'=>'99',
            // 'lang'=>'pt_br',
            'confirmed'=>1,
            'mnethostid'=>1,
        ];

        if (!$usuario) {
            $userid = \user_create_user(array_merge($common, $insert_only));
        } else {
            \user_update_user(array_merge(['id'=>$usuario->id], $common));
            $userid = $usuario->id;
        }

        $default_user_preferences = preg_split('/\r\n|\r|\n/', config('default_user_preferences'));
        foreach ($default_user_preferences as $preference) {
            $parts = explode("=", $preference);
            create_or_update('user_preferences', ['userid'=>$userid, 'name'=>$parts[0]], ['value'=>$parts[1]]);
        }
        create_or_update(
            'auth_oauth2_linked_login', 
            ['userid'=>$userid, 'issuerid'=>$issuerid],
            ['username'=>$username, 'email'=> !empty($user->email) ? $user->email : $user->email_secundario, 'timecreated'=>time(), 'usermodified'=>0, 'confirmtoken'=>'', 'confirmtokenexpires'=>0, 'timemodified'=>time()],
            ['timemodified'=>time()]
        );
        
        return $userid;
    }


    function sync_enrol($contextid, $userid, $enrolid, $roleid){
        $n = time();
        $user_enrolments = get_or_create(
            'user_enrolments',
            ['userid'=>$userid, 'enrolid'=>$enrolid],
            ['timecreated'=>$n, 'timemodified'=>$n, 'timestart'=>$n, 'timeend'=>0, 'modifierid'=>$userid]
        );

        $role_assignments = get_or_create(
            'role_assignments',
            ['userid'=>$userid, 'contextid'=>$contextid, 'roleid'=>$roleid],
            ['timemodified'=>$n, 'modifierid'=>$userid]
        );
    }


    function sync_group($courseid, $userid, $username, $polo, $turma, $room) {
        global $DB;
        if (empty($polo)) {
            return;
        }
        $entrada = substr($username, 0, 5);
        $polo_array = gettype($polo) == 'integer' ? [] : [$polo->descricao];

        $groups = array_merge($polo_array, $room ? [$turma->codigo, $entrada] : []);
        foreach ($groups as $groupname) {
            $data = ['courseid' => $courseid, 'name' => $groupname];
            $group = $DB->get_record('groups', $data);
            if (!$group) {
                \groups_create_group((object)$data);
                $group = $DB->get_record('groups', $data);
            }
            if (!$DB->get_record('groups_members', ['groupid' => $group->id, 'userid' => $userid])) {
                \groups_add_member($group->id, $userid);
            }
        }
    }


}
$service = new sync_up_enrolments_service();
$service->call();
