<?php
namespace local_suap;

require_once('../../../course/lib.php');
require_once('../../../user/lib.php');
require_once('../../../cohort/lib.php');
require_once('../../../user/profile/lib.php');
require_once('../../../group/lib.php');
require_once("../../../lib/enrollib.php");
require_once("../../../enrol/locallib.php");
require_once("../../../enrol/externallib.php");
require_once("../locallib.php");
require_once("servicelib.php");


class sync_up_enrolments_service extends service {

    private $json;
    private $context;
    private $course;
    private $issuerid;
    private $aluno_enrol;
    private $professor_enrol;
    private $tutor_enrol;
    private $docente_enrol;


    function do_call() {
        global $CFG;
        $prefix = "{$CFG->wwwroot}/course/view.php";

        $this->validate_json();
        $this->issuerid = $this->sync_suap_issuer();
        $this->professor_enrol = $this->get_enrolment_config($this->course, 'teacher');
        $this->tutor_enrol = $this->get_enrolment_config($this->course, 'assistant');
        $this->docente_enrol = $this->get_enrolment_config($this->course, 'instructor');
        $this->aluno_enrol = $this->get_enrolment_config($this->course, 'student');

        $diario_id = $this->sync_struct($this->json, false);
        $sala_id = $this->sync_struct($this->json, true);
        return ["url" => "$prefix?id={$diario_id}", "url_sala_coordenacao" => "$prefix?id={$sala_id}"];
    }


    function validate_json() {
        if (!array_key_exists('jsonstring', $_POST)) {
            throw new \Exception("Atributo \'jsonstring\' é obrigatório.", 550);
        }

        $this->json = json_decode($_POST['jsonstring']);

        if (empty($this->json)) {
            throw new \Exception("Atributo 'jsonstring' sem JSON ou com JSON inválido.", 551);
        }

        // TODO: Validar o JSON usando um json-schema, mas só tem isso usando Composer
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


    function sync_struct($room) {
        global $CFG, $DB;
 
        $categoryid = $this->sync_category_hierarchy($room);       
        $this->course = $this->sync_course($categoryid, $room);
        $this->sync_docentes($room);
        $this->sync_discentes($room);
        if (!$room) {
            $this->sync_cohort($this->json->coortes);
        }
        return $this->course->id;
    }


    function sync_category_hierarchy($ate_curso) {
        $top_category_idnumber = config('top_category_idnumber') ?: 'diarios'; 
        $top_category_name = config('top_category_name') ?: 'Diários';
        $top_category_parent = config('top_category_parent') ?: 0;
        $ano_periodo = substr($this->json->turma->codigo, 0, 4) . "." . substr($this->json->turma->codigo, 4, 1);
        
        $diario_category = $this->sync_category($top_category_idnumber, $top_category_name, $top_category_parent);
        $campus = $this->sync_category($this->json->campus->sigla, $this->json->campus->descricao, $diario_category->id);
        $curso = $this->sync_category($this->json->curso->codigo, $this->json->curso->nome, $campus->id);
        
        if ($ate_curso) {
            return $curso->id;
        }

        $semestre = $this->sync_category("{$this->json->curso->codigo}.{$ano_periodo}", $ano_periodo, $curso->id);
        $turma = $this->sync_category($this->json->turma->codigo, $this->json->turma->codigo, $semestre->id);

        return $turma->id;
    }


    function sync_category($idnumber, $name, $parent){
        global $DB;
    
        $category = $DB->get_record('course_categories', ['idnumber'=>$idnumber]);
        if (empty($category)) {
            $category = \core_course_category::create(['name'=>$name, 'idnumber'=>$idnumber, 'parent'=>$parent]);
        }   

        return $category;
    }


    function sync_course($categoryid, $room){
        global $DB;
        
        $diario_code = $room ? "{$this->json->campus->sigla}.{$this->json->curso->codigo}" : "{$this->json->turma->codigo}.{$this->json->componente->sigla}";
        $diario_code_long = $room ? $diario_code : "{$diario_code}#{$this->json->diario->id}";
        $this->course = $DB->get_record('course', ['idnumber'=>$diario_code_long]) ?: $DB->get_record('course', ['idnumber'=>$diario_code]);
        if (!$this->course) {
            $this->course = $DB->get_record('course', ['shortname'=>$diario_code_long]) ?: $DB->get_record('course', ['shortname'=>$diario_code]);
        }
        
        if (!$this->course) {
            $data = [
                    "category"=>$categoryid,
                    "shortname"=>$diario_code_long,
                    "fullname"=> $room ? "Sala de coordenação do curso {$this->json->curso->nome}" : $this->json->componente->descricao,
                    "idnumber"=>$diario_code_long,
                    "visible"=>0,
                    "enablecompletion"=>1,
                    // "startdate"=>time(),
                    "showreports"=>1,
                    "completionnotify"=>1,

                    "customfield_campus_id"=> $this->json->campus->id,
                    "customfield_campus_descricao"=> $this->json->campus->descricao,
                    "customfield_campus_sigla"=> $this->json->campus->sigla,

                    "customfield_curso_id"=> $this->json->curso->id,
                    "customfield_curso_codigo"=> $this->json->curso->codigo,
                    "customfield_curso_descricao"=> $this->json->curso->descricao,
                    "customfield_curso_nome"=> $this->json->curso->nome,
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
        
                        "customfield_turma_id"=> $this->json->turma->id,
                        "customfield_turma_codigo"=> $this->json->turma->codigo,
        
                        "customfield_turma_ano_periodo"=> substr($this->json->turma->codigo, 0, 4) . "." . substr($this->json->turma->codigo, 4, 1),
        
                        "customfield_diario_id"=> $this->json->diario->id,
                        "customfield_diario_situacao"=> $this->json->diario->situacao,
        
                        "customfield_disciplina_id"=> $this->json->componente->id,
                        "customfield_disciplina_sigla"=> $this->json->componente->sigla,
                        "customfield_disciplina_descricao"=> $this->json->componente->descricao,
                        "customfield_disciplina_descricao_historico"=> $this->json->componente->descricao_historico,
                        // "customfield_disciplina_periodo"=> $this->json->componente->periodo,
                        "customfield_disciplina_tipo"=> $this->json->componente->tipo,
                        "customfield_disciplina_optativo"=> $this->json->componente->optativo,
                        "customfield_disciplina_qtd_avaliacoes"=> $this->json->componente->qtd_avaliacoes,
                    ]
                );
            }

            $this->course = create_course((object)$data);
        } elseif (!$room) {
            $this->course->idnumber = $diario_code_long;
            $this->course->shortname = $diario_code_long;
            update_course($this->course);
        }

        $this->context = \context_course::instance($this->course->id);

        return $this->course;
    }


    function sync_docentes($room) {
        if (!isset($this->json->professores)) {
            return;
        }

        global $CFG, $DB;

        foreach ($this->json->professores as $professor) {
            if ($room) {
                $enrol = $this->docente_enrol;
            } elseif (in_array(strtolower($professor->tipo), ['principal', 'formador']))  {
                $enrol = $this->professor_enrol;
            } else {
                $enrol = $this->tutor_enrol;
            }
            
            $this->sync_user($professor);
            $this->sync_enrol($enrol, $professor, \ENROL_USER_ACTIVE);
        }
    }


    function sync_discentes($room=false) {
        global $CFG, $DB;
        if (!isset($this->json->alunos)) {
            return;
        }
        $alunos_suspensos = [];
        foreach ($this->json->alunos as $aluno) {
            $this->sync_user($aluno);

            $situacao_diario = strtolower(property_exists($aluno, "situacao_diario") ? $aluno->situacao_diario : "Ativo");
            $status = $situacao_diario == 'ativo' ? \ENROL_USER_ACTIVE : \ENROL_USER_SUSPENDED;
            
            $this->sync_enrol($this->aluno_enrol, $aluno->user, $status);

            // Ativa/inativa na sala de coordenação conforme matrícula no curso
            if ($room) {
                $status = $aluno->user->suspended ? \ENROL_USER_SUSPENDED : \ENROL_USER_ACTIVE;
                $aluno_enrol->enrol->update_user_enrol($aluno_enrol->instance, $aluno->user->id, $status);
            }

            $groups = [
                substr($aluno->user->username, 0, 5), // Entrada YYYYS
                $this->json->turma->codigo, // Turma
            ];

            if (property_exists($aluno, "polo") && property_exists($aluno->polo, "descricao")) {
                $groups[] = $aluno->polo->descricao;
            }
            if (isset($aluno->programa) || isset($aluno->programa) && $aluno->programa == null) {
                $groups[] = $aluno->programa;
            }else{
                $groups[] = "Institucional";
            }
            
            $this->sync_groups($this->course->id, $aluno->user, $groups);
            $alunos_sincronizados[] = $aluno->user->id;
        }

        // Inativa no diário os ALUNOS que não vieram na sicronização
        if (!$room) {
            foreach ($DB->get_records_sql("SELECT ra.userid FROM {role_assignments} ra WHERE ra.roleid = {$aluno_enrol->roleid} AND ra.contextid={$this->context->id}") as $userid => $ra) {
                if (!in_array($userid, $alunos_sincronizados)) {
                    $aluno_enrol->enrol->update_user_enrol($aluno_enrol->instance, $userid, \ENROL_USER_SUSPENDED);
                }
            }
        }
    }


    function sync_user($usuario){
        global $DB;
        $ifrnid = property_exists($usuario, 'matricula') ? $usuario->matricula : $usuario->login;
        $status = strtolower(property_exists($usuario, 'situacao') ? $usuario->situacao : $usuario->status);

        if (property_exists($usuario, 'matricula')) {
            $auth = config('default_student_auth');
        } else {
            if ($usuario->tipo == 'Principal') {
                $auth = config('default_teacher_auth');
            } else {
                $auth = config('default_assistant_auth');
            }
        }

        $usuario->user = $this->create_or_update_user(
            $ifrnid, 
            !empty($usuario->email) ? $usuario->email : $usuario->email_secundario,
            $usuario->nome,
            ($status == 'ativo' ? 0 : 1),
            $auth
        );

        $custom_fields = [
            'programa_nome' => isset($usuario->programa) ? $usuario->programa : "Institucional",
            'curso_descricao' => $this->json->curso->nome,
            'curso_codigo' => $this->json->curso->codigo
        ];

        if (property_exists($usuario, 'polo')) {
            $custom_fields['polo_id'] = property_exists($usuario->polo, 'id') ? $usuario->polo->id : null;
            $custom_fields['polo_nome'] = property_exists($usuario->polo, 'descricao') ? $usuario->polo->descricao : null;
        }

        \profile_save_custom_fields($usuario->user->id, $custom_fields);

        foreach (preg_split('/\r\n|\r|\n/', config('default_user_preferences')) as $preference) {
            $parts = explode("=", $preference);
            \set_user_preference($parts[0], $parts[1], $usuario);
        }
    }


    function create_or_update_user($username, $email, $nome_completo, $suspended, $auth) {
        global $DB;

        $usuario = $DB->get_record("user", ["username" => $username]);

        $nome_parts = explode(' ', $nome_completo);
        $firstname = $nome_parts[0];
        $lastname = implode(' ', array_slice($nome_parts, 1));
        $insert_only = ['username'=>$username, 'password'=>'!aA1' . uniqid(), 'timezone'=>'99', 'confirmed'=>1, 'mnethostid'=>1];
        $insert_or_update = ['firstname'=>$firstname, 'lastname'=>$lastname, 'auth'=>$auth, 'email'=> $email];
        if ($suspended != null) {
            $insert_or_update['suspended'] = $suspended;
        } else {
            $insert_or_update['suspended'] = 0;
        }

        if (!$usuario) {
            $userid = \user_create_user(array_merge($insert_or_update, $insert_only));
            $usuario = $DB->get_record("user", ["username" => $username]);
        } else {
            \user_update_user(array_merge(['id'=>$usuario->id], $insert_or_update));
            $userid = $usuario->id;
        }

        get_or_create(
            'auth_oauth2_linked_login',
            ['userid'=>$userid, 'issuerid'=>$this->issuerid, 'username'=>$username],
            ['email'=> $email, 'timecreated'=>time(), 'usermodified'=>0, 'confirmtoken'=>'', 'confirmtokenexpires'=>0, 'timemodified'=>time()],
        );
        return $usuario;
    }

    
    function sync_enrol($enrol, $usuario, $status) {
        if (is_enrolled($this->context, $usuario->user)) {
            $enrol->enrol->update_user_enrol($enrol->instance, $usuario->user->id, $status);
        } else {
            $enrol->enrol->enrol_user($enrol->instance, $usuario->user->id, $enrol->roleid, time(), 0, $status);
        }
    }


    function get_enrolment_config($type)  {
        $roleid = config("default_{$type}_role_id");
        $enrol_type = config("default_{$type}_enrol_type");
        $enrol = enrol_get_plugin($enrol_type);
        $instance = $this->get_instance($enrol_type);
        if ($instance == null) {
            $enrol->add_instance($this->course);
            $instance = $this->get_instance($enrol_type);
        }
        return (object)['roleid'=>$roleid, 'enrol_type'=>$enrol_type, 'enrol'=>$enrol, 'instance'=>$instance];
    }


    function get_instance($enrol_type) {
        foreach (\enrol_get_instances($this->course->id, FALSE) as $i) {
            if ($i->enrol == $enrol_type) {
                return $i;
            }
        }
        return null;
    }


    function sync_groups($usuario, $groups) {
        global $DB;
        foreach ($groups as $groupname) {
            $group_name = (!empty($groupname)) ? $groupname : '--Sem pólo--';
            $data = ['courseid' => $this->course->id, 'name' => $group_name];
            $group = $DB->get_record('groups', $data);
            if (!$group) {
                \groups_create_group((object)$data);
                $group = $DB->get_record('groups', $data);
            }
            if (!$DB->get_record('groups_members', ['groupid' => $group->id, 'userid' => $usuario->user->id])) {
                \groups_add_member($group->id, $usuario->user->id);
            }
        }
    }

    
    function sync_cohort($coortes){
        global $DB;
        
        $auth = config('default_assistant_auth');

        foreach ($coortes as $coorte) {
            $instance = $DB->get_record('cohort', ['idnumber'=>$coorte->idnumber]);

            if (!$instance) {
                $cohortid = \cohort_add_cohort((object)[
                    "name"=>$coorte->nome,
                    "idnumber"=>$coorte->idnumber,
                    "description"=>$coorte->descricao,
                    "visible"=>$coorte->ativo,
                    "contextid"=>1,
                ]);
            } else {
                $instance->name = $coorte->nome;
                $instance->idnumber = $coorte->idnumber;
                $instance->description = $coorte->descricao;
                $instance->visible = $coorte->ativo;
                \cohort_update_cohort($instance);
                $cohortid = $instance->id;
            }

            if (isset($coorte->colaboradores)) {
                foreach ($coorte->colaboradores as $colaborador) {
                    $usuario = $this->create_or_update_user(
                        $colaborador->username,
                        $colaborador->email,
                        $colaborador->nome_completo,
                        !$colaborador->vinculo_ativo,
                        $auth
                    );
                    \cohort_add_member($cohortid, $usuario->id);
                }
            }
            
            $groupid = 0;
            $role = $DB->get_record('role', ['shortname'=>$coorte->role]);
            $enrol = enrol_get_plugin("cohort");
            $instance = $DB->get_record('enrol', ["enrol"=>"cohort", "customint1"=> $cohortid, "courseid"=>$this->course->id]);
            if (!$instance) {
                $enrol->add_instance($this->course, ["customint1"=>$cohortid, "roleid"=>$role->id, "customint2"=>$groupid]);
            }     
        }

    }
}