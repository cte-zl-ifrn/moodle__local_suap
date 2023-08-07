<?php
namespace local_suap;

require_once('../../../course/lib.php');
require_once('../../../user/lib.php');
require_once('../../../user/profile/lib.php');
require_once('../../../group/lib.php');
require_once("../../../lib/enrollib.php");
require_once("../../../enrol/locallib.php");
require_once("../../../enrol/externallib.php");
require_once("../../../enrol/externallib.php");
require_once("../locallib.php");
require_once("servicelib.php");


class sync_up_enrolments_service extends service {


    function do_call() {
        global $CFG;
        
        $json = $this->validate_json();
        
        $diario_id = $this->sync_struct($json, false);
        
        $sala_id = $this->sync_struct($json, true);
        
        $prefix = "{$CFG->wwwroot}/course/view.php";
        return ["url" => "$prefix?id={$diario_id}", "url_sala_coordenacao" => "$prefix?id={$sala_id}"];
    }


    function validate_json() {
        if (!array_key_exists('jsonstring', $_POST)) {
            throw new \Exception("Atributo \'jsonstring\' é obrigatório.", 550);
        }

        $json = json_decode($_POST['jsonstring']);

        if (empty($json)) {
            throw new \Exception("Atributo 'jsonstring' sem JSON ou com JSON inválido.", 551);
        }

        // TODO: Validar o JSON usando um json-schema, mas só tem isso usando Composer
       
        return $json;
    }


    function sync_struct($json, $room=false) {
        global $CFG, $DB;


        $categoryid = $this->sync_category_hierarchy($json, $room);       
        $course = $this->sync_course($categoryid, $json, $room);
        $context = \context_course::instance($course->id);

        $issuerid = $this->sync_suap_issuer();

        $principal_enrol = $this->get_enrolment_config($course, ($room ? 'instructor' : 'teacher'));
        $moderador_enrol = $this->get_enrolment_config($course, ($room ? 'instructor' : 'assistant'));
        foreach ($json->professores as $professor) {
            $user = $this->sync_user($professor, $issuerid);
            $tipo = strtolower($professor->tipo);
            $enrol_info = ($tipo == 'principal' || $tipo == 'formador' ? $principal_enrol : $moderador_enrol);
            $this->sync_enrol($context, $enrol_info->enrol, $enrol_info->enrol_instance, $enrol_info->roleid, $user, \ENROL_USER_ACTIVE);
        }

        $aluno_enrol = $this->get_enrolment_config($course, 'student');
        $alunos_sincronizados = [];
        $alunos_suspensos = [];
        foreach ($json->alunos as $aluno) {
            $user = $this->sync_user($aluno, $issuerid);
            $situacao_diario = property_exists($aluno, "situacao_diario") ? $aluno->situacao_diario : "Ativo";
            $status = strtolower($situacao_diario) == 'ativo' ? \ENROL_USER_ACTIVE : \ENROL_USER_SUSPENDED;
            $this->sync_enrol($context, $aluno_enrol->enrol, $aluno_enrol->enrol_instance, $aluno_enrol->roleid, $user, $status);

            // Ativa/inativa na sala de coordenação conforme matrícula no curso
            if ($room) {
                $status = $user->suspended ? \ENROL_USER_SUSPENDED : \ENROL_USER_ACTIVE;
                $aluno_enrol->enrol->update_user_enrol($aluno_enrol->enrol_instance, $user->id, $status);
            }

            $groups = [
                substr($user->username, 0, 5), // Entrada YYYYS
                $json->turma->codigo, // Turma
            ];

            if (property_exists($aluno, "polo") && property_exists($aluno->polo, "descricao")) {
                $groups[] = $aluno->polo->descricao;
            }
            if (property_exists($aluno, "programa") && property_exists($aluno->programa, "descricao")) {
                $groups[] = $aluno->programa->descricao;
            }
            
            $this->sync_groups($course->id, $user, $groups);
            $alunos_sincronizados[] = $user->id;
        }

        // Inativa no diário os ALUNOS que não vieram na sicronização
        if (!$room) {
            foreach ($DB->get_records_sql("SELECT ra.userid FROM {role_assignments} ra WHERE ra.roleid = {$aluno_enrol->roleid} AND ra.contextid={$context->id}") as $userid => $ra) {
                if (!in_array($userid, $alunos_sincronizados)) {
                    $aluno_enrol->enrol->update_user_enrol($aluno_enrol->enrol_instance, $userid, \ENROL_USER_SUSPENDED);
                }
            }
        }
        return $course->id;
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
        $diario_code_long = $room ? $diario_code : "{$diario_code}#{$json->diario->id}";
        $course = $DB->get_record('course', ['idnumber'=>$diario_code_long]) ?: $DB->get_record('course', ['idnumber'=>$diario_code]);
        if (!$course) {
            $course = $DB->get_record('course', ['shortname'=>$diario_code_long]) ?: $DB->get_record('course', ['shortname'=>$diario_code]);
        }
        
        if (!$course) {
            $data = [
                    "category"=>$categoryid,
                    "shortname"=>$diario_code_long,
                    "fullname"=> $room ? "Sala de coordenação do curso {$json->curso->nome}" : $json->componente->descricao,
                    "idnumber"=>$diario_code_long,
                    "visible"=>0,
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
            ];
            
            if ($room) {
                $data = array_merge(
                    $data,
                    [
                        "customfield_curso_sala_coordenacao"=> 'Sim',
                    ]
                );
            } else {
                $data = array_merge(
                    $data,
                    [
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
                    ]
                );
            }

            $course = create_course((object)$data);
        } else {
            $course->idnumber = $diario_code_long;
            $course->shortname = $diario_code_long;
            update_course($course);
        }
        return $course;
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

    function get_enrol_instance($course, $enrol_type) {
        $enrol_instance = null;
        foreach (\enrol_get_instances($course->id, FALSE) as $i) {
            if ($i->enrol == $enrol_type) {
                $enrol_instance = $i;
            }
        }
        return $enrol_instance;
    }

    function get_enrolment_config($course, $type)  {
        $roleid = config("default_{$type}_role_id");
        $enrol_type = config("default_{$type}_enrol_type");
        $enrol = enrol_get_plugin($enrol_type);
        $enrol_instance = $this->get_enrol_instance($course, $enrol_type);
        if ($enrol_instance == null) {
            $enrol->add_instance($course);
            $enrol_instance = $this->get_enrol_instance($course, $enrol_type);
        }
        return (object)[
            'roleid'=>$roleid,
            'enrol_type'=>$enrol_type,
            'enrol'=>$enrol,
            'enrol_instance'=>$enrol_instance,
        ];
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
        $user->username = $username;
        $nome_parts = explode(' ', $user->nome);
        $common = [
            'firstname'=>$nome_parts[0],
            'lastname'=>implode(' ', array_slice($nome_parts, 1)),
            'auth'=>config($auth),
            'email'=> !empty($user->email) ? $user->email : $user->email_secundario,
            'suspended'=>(strtolower($status) == 'ativo' ? 0 : 1),
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
            $usuario = $DB->get_record("user", ["username" => $username]);
        } else {
            \user_update_user(array_merge(['id'=>$usuario->id], $common));
            $userid = $usuario->id;
            
        }

        if (property_exists($user, 'programa')) {
            \profile_save_custom_fields(
                $userid,
                [
                    'programa_id' => property_exists($user->programa, 'id') ? $user->programa->id : null,
                    'programa_nome' => property_exists($user->programa, 'descricao') ? $user->programa->descricao : null
                ]
            );
        }

        if (property_exists($user, 'polo')) {
            \profile_save_custom_fields(
                $userid,
                [
                    'polo_id' => property_exists($user->polo, 'id') ? $user->polo->id : null,
                    'polo_nome' => property_exists($user->polo, 'descricao') ? $user->polo->descricao : null
                ]
            );
        }

        foreach (preg_split('/\r\n|\r|\n/', config('default_user_preferences')) as $preference) {
            $parts = explode("=", $preference);
            \set_user_preference($parts[0], $parts[1], $user);
        }

        create_or_update(
            'auth_oauth2_linked_login',
            ['userid'=>$userid, 'issuerid'=>$issuerid, 'username'=>$username],
            ['email'=> !empty($user->email) ? $user->email : $user->email_secundario, 'timecreated'=>time(), 'usermodified'=>0, 'confirmtoken'=>'', 'confirmtokenexpires'=>0, 'timemodified'=>time()],
            ['timemodified'=>time()]
        );
        
        return $usuario;
    }


    function sync_enrol($context, $enrol, $instance, $roleid, $user, $status) {
        global $DB;

        if (is_enrolled($context, $user)) {
            $enrol->update_user_enrol($instance, $user->id, $status);
            return;
        } else {
            $enrol->enrol_user($instance, $user->id, $roleid, time(), 0, $status);
        }
    }


    function sync_groups($courseid, $user, $groups) {
        global $DB;
        foreach ($groups as $groupname) {
            $group_name = (!empty($groupname)) ? $groupname : '--Sem pólo--';
            $data = ['courseid' => $courseid, 'name' => $group_name];
            $group = $DB->get_record('groups', $data);
            if (!$group) {
                \groups_create_group((object)$data);
                $group = $DB->get_record('groups', $data);
            }
            if (!$DB->get_record('groups_members', ['groupid' => $group->id, 'userid' => $user->id])) {
                \groups_add_member($group->id, $user->id);
            }
        }
    }

}