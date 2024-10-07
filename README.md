# moodle__local_suap


## Fluxo de integração


### Fluxo comum a todos os serviços
1. Recerber requisição
2. Validar token
3. Identificar serviço
4. Executar serviço


### Sincronização de diários

> Neste fluxo, quando dizemos "sincronizar" significa: 1. caso não exista: crie; 2. caso já exista: atualizar alguns campos. Para saber se já existe é usada uma chave externa vinda do arquivo a ser sincronizado.

> ATENÇÃO: Se a chave for alterada manualmente ou por outro plugin a sincronização quebrará. Via de regra, a chave é o `idnumber`. Mas também pode ser o username, como no caso do `user`.

Após realizar o fluxo padrão

1. Validar JSON
    1. Sucesso: O JSON veio
    2. Sucesso: O JSON consegue ser decodificado com sucesso
    3. Sucesso: O JSON está conforme o esquema (falta)
2. Sincronizar o Issuer oAuth2 do SUAP (Mover isso pra ser feito no upgrade, aqui apenas pegaríamos o já existente)
3. Sincronizar Diário e Sala de Coordenação
    1. Sincronizar estrutura das categorias. 
        1. Sincronizar a categoria "Diários"
            - Chave: `idnumber`=**'diarios'**
            - Insere:
                - `idnumber`=**'diarios'**
                - `name`=**'Diários'**
                - `parent`=**0**
            - Atualiza: **nada**
            - Exclui: **nunca**
        2. Sincronizar a categoria do "Campus"
            - Chave: `idnumber`=**campus.sigla**
            - Insere:
                - `idnumber`=**campus.sigla**
                - `name`=**campus.descricao**
                - `parent`=**diario_category.id**
            - Atualiza: **nada**
            - Exclui: **nunca**
        3. Sincronizar a categoria do "Curso"
            - Chave: `idnumber`=**curso.codigo**
            - Insere:
                - `idnumber`=**curso.codigo**
                - `name`=**curso.nome**
                - `parent`=**campus_category.id**
            - Atualiza: **nada**
            - Exclui: **nunca**
        4. Sincronizar a categoria do "Semestre", se Diário
            - Chave: `idnumber`=**curso.codigo + '.' + ano_periodo**
            - Insere:
                - `idnumber`=**curso.codigo + '.' + ano_periodo**
                - `name`=**ano_periodo**
                - `parent`=**curso_category.id**
            - Atualiza: **nada**
            - Exclui: **nunca**
        5. Sincronizar a categoria da "Turma", se Diário
            - Chave: `idnumber`=**turma.codigo**
            - Insere:
                - `idnumber`=**turma.codigo**
                - `name`=**turma.codigo**
                - `parent`=**semestre_category.id**
           - Atualiza: **nada**
           - Exclui: **nunca**
        6. Retornar o ID da categoria da "Turma", se Diário, ou o ID da categoria do "Curso", se Coordenação
    2. Sincronizar o **Diário** na categoria da **Turma**
        - Chave: 
            - `idnumber`=**turma.codigo + '.' + componente.sigla + '#' + diario.id**. Se Diário
            - `idnumber`=**turma.codigo + '.' + componente.sigla + '#' + diario.id**. Se Sala de coordenação
        - Insere:
            - `idnumber`=**turma.codigo + '.' + componente.sigla + '#' + diario.id**
            - `shortname`=**turma.codigo + '.' + componente.sigla + '#' + diario.id**
            - `category`=**turma_category.id**
            - `fullname`=**componente.descricao**
            - `visible`=>**0**
            - `enablecompletion`=>**1**
            - `showreports`=>**1**
            - `completionnotify`=>**1**
            - `customfield_campus_id`=**campus.id**
            - `customfield_campus_descricao`=**campus.descricao**
            - `customfield_campus_sigla`=**campus.sigla**
            - `customfield_curso_id`=**curso.id**
            - `customfield_curso_codigo`=**curso.codigo**,
            - `customfield_curso_descricao`=**curso.descricao**
            - `customfield_curso_nome`=**curso.nome**
            - `customfield_curso_sala_coordenacao`=**'Não' || 'Sim'**
            - `customfield_turma_id`=**turma.id**. Se Diário
            - `customfield_turma_codigo`=**turma.codigo**. Se Diário
            - `customfield_turma_ano_periodo`=**turma.ano_periodo**. Se Diário
            - `customfield_diario_id`=**diario.id**. Se Diário
            - `customfield_diario_situacao`=**diario.situacao**. Se Diário
            - `customfield_disciplina_id`=**componente.id**. Se Diário
            - `customfield_disciplina_sigla`=**componente.sigla**. Se Diário
            - `customfield_disciplina_descricao`=**componente.descricao**. Se Diário
            - `customfield_disciplina_descricao_historico`=**componente.descricao_historico**. Se Diário
            - `customfield_disciplina_tipo`=**componente.qtd_avaliacoes**. Se Diário
            - `customfield_disciplina_optativo`=**componente.qtd_avaliacoes**. Se Diário
            - `customfield_disciplina_qtd_avaliacoes`=**componente.qtd_avaliacoes**. Se Diário
        - Atualiza: `shortname`=**turma.codigo + '.' + componente.sigla + '#' + diario.id**. Se Diário
        - Exclui: **nunca**
    3. Sincronizar usuarios
        1. Sincronizar usuário
            - Chave: `username`=**ifrnid**. Sendo que para servidores ou alunos é a matricula, para os demais é o CPF
            - Insere:
                - `username`=**usuario.ifrnid**
                - `firstname`=**usuario.apenas_o_primeiro_do_nome_completo**
                - `lastname`=**usuario.todo_o_resto_do_nome_completo**
                - `auth`=**config('default_student_auth' || 'default_teacher_auth' || 'default_assistant_auth' || 'default_instructor_auth')**
                - `email`=**usuario.email || usuario.email_secundario**
                - `suspended`=**(usuario.status || usuario.situacao) != 'ativo'**
                - `password`=**uniqid()**
                - `timezone`=**'99'**
                - `confirmed`=**1**
                - `mnethostid`=**1**
            - Atualiza:
                - `firstname`=**usuario.apenas_o_primeiro_do_nome_completo**
                - `lastname`=**usuario.todo_o_resto_do_nome_completo**
                - `auth`=**config('default_student_auth' || 'default_teacher_auth' || 'default_assistant_auth' || 'default_instructor_auth')**
                - `email`=**usuario.email || usuario.email_secundario**
                - `suspended`=**(usuario.status || usuario.situacao) != 'ativo'**
                - `profile__programa_nome`=**aluno.programa || 'Institucional'**. Apenas para alunos
                - `profile__curso_descricao`=**aluno.curso.nome**. Apenas para alunos
                - `profile__curso_codigo`=**aluno.curso.codigo**. Apenas para alunos
                - `profile__polo_id`=**aluno.polo.id**. Apenas para alunos
                - `profile__polo_nome`=**aluno.polo.descricao**. Apenas para alunos
            - Exclui: **nunca**
        2. Sincronizar grupos do usuário
            1. Cada grupo será conforme
                1. Entrada: **YYYYS**, onde: YYYY é o ano de entrada, S é o semestre de entrada. Estes dados estão nos 5 primeiros caracteres da matrícula do aluno.
                2. Turma: **turma.codigo**
                3. Polo: **aluno.polo.descricao**
                4. Programa: **aluno.programa || 'Institucional'**
            2. Sincronizar grupo
                - Chave:
                    - `courseid`=**course.id**
                    - `name`=**aluno.entrada || diario.turma || aluno.polo || aluno.programa**
                - Insere:
                    - `courseid`=**course.id**
                    - `name`=**aluno.entrada || diario.turma || aluno.polo || aluno.programa**
                - Atualiza: **nada**
                - Exclui: **nunca**
            3. Sincronizar inscrição do usuário no grupo
                - Chave:
                    - `groupid`=**group.id**
                    - `userid`=**usuario.id**
                - Insere:
                    - `groupid`=**group.id**
                    - `userid`=**usuario.id**
                - Atualiza: **nada**
                - Exclui: **nunca**
        2. Sincronizar usuário autenticação oAuth2
            - Chave:
                - `username`=**usuario.ifrnid**
                - `issuerid`=**default.issuerid**
            - Insere:
                - `username`=**usuario.ifrnid**
                - `issuerid`=**default.issuerid**
                - `email`=**usuario.todo_o_resto_do_nome_completo**
                - `timecreated`=**time()**
                - `usermodified`=**0**
                - `confirmtoken`=**''**
                - `confirmtokenexpires`=**0**
                - `timemodified`=**time()**
            - Atualiza: **nada**
            - Exclui: **nunca**
        3. Sincronizar preferencias padrões do usuário
            - Conforme configurado no admin do Moodle
        4. Sincronizar inscrição
            - Chave:
                - `courseid`=**course.id**
                - `userid`=**usuario.user.id**
            - Insere:
                - `enrol_instanceid`=**aluno_enrol.instance || professor_enrol.instance || tutor_enrol.instance || docente_enrol.instance**
                - `userid`=**usuario.user.id**
                - `roleid`=**aluno_enrol.roleid || professor_enrol.roleid || tutor_enrol.roleid || docente_enrol.roleid**
                - `status`=**aluno.situacao_diario == 'ativo'**. Apenas para alunos, os demais sempre `ENROL_USER_ACTIVE`.
                - `timestart`=**time()**
                - `timeend`=**0**
                - `recovergrades`=**null**
            - Atualiza:
                - `enrol_instanceid`=**aluno_enrol.instance || professor_enrol.instance || tutor_enrol.instance || docente_enrol.instance**
                - `userid`=**usuario.user.id**
                - `roleid`=**aluno_enrol.roleid || professor_enrol.roleid || tutor_enrol.roleid || docente_enrol.roleid**
                - `status`=**aluno.situacao_diario == 'ativo'**. Apenas para alunos, os demais sempre `ENROL_USER_ACTIVE`
            - Exclui: **nunca**
        5. Inativar os ALUNOS que não vieram na sicronização, se Diário
            - Chave:
                - `roleid`=**aluno_enrol.roleid**
                - `courseid`=**course.id**
                - `userid`=**not in (alunos_sincronizados)**
            - Insere: **nada**
            - Atualiza:
                - `enrol`=**aluno_enrol.enrol**
                - `enrol_instanceid`=**aluno_enrol.instance**
                - `userid`=**aluno.user.id**
                - `status`=**ENROL_USER_SUSPENDED**
            - Exclui: **nunca**
    4. Sincronizar coortes
        1. Sincronizar coorte
            - Chave: `idnumber`=**coorte.idnumber**
            - Insere:
                - `idnumber`=**coorte.idnumber**
                - `name`=**coorte.nome**
                - `description`=**coorte.descricao**
                - `visible`=**coorte.ativo**
                - `contextid`=**1**
            - Atualiza: **nada**
                - `name`=**coorte.nome**
                - `description`=**coorte.descricao**
                - `visible`=**coorte.ativo**
                - `contextid`=**1**
            - Exclui: **nunca**
        2. Sincronizar colaboradores da coorte
            1. Sincronizar usuário
                - Chave: `username`=**ifrnid**. Sendo que para servidores ou alunos é a matricula, para os demais é o CPF
                - Insere:
                    - `username`=**colaborador.ifrnid**
                    - `firstname`=**colaborador.apenas_o_primeiro_do_nome_completo**
                    - `lastname`=**colaborador.todo_o_resto_do_nome_completo**
                    - `email`=**colaborador.email || usuario.email_secundario**
                    - `auth`=**config('default_assistant_auth')**
                    - `suspended`=**!colaborador.vinculo_ativo**
                    - `password`=**uniqid()**
                    - `timezone`=**'99'**
                    - `confirmed`=**1**
                    - `mnethostid`=**1**
                - Atualiza:
                    - `firstname`=**colaborador.apenas_o_primeiro_do_nome_completo**
                    - `lastname`=**colaborador.todo_o_resto_do_nome_completo**
                    - `email`=**colaborador.email || usuario.email_secundario**
                    - `auth`=**config('default_assistant_auth')**
                    - `suspended`=**!colaborador.vinculo_ativo**
                - Exclui: **nunca**
            2. Adicionar colaborador à coorte. *se já existir dará certo?*
                - `cohortid`=**coorte.id**
                - `cohortid`=**colaborador.user.id**
        3. Sincroniza o enrol no curso
            1. Sincronizar usuário
                - Chave:
                    - `enrol`=**cohort**
                    - `customint1`=**coorte.id**
                    - `courseid`=**course.id**
                - Insere:
                    - `enrol`=**cohort**
                    - `customint1`=**coorte.id**
                    - `courseid`=**course.id**
                    - `roleid`=**colaborador.roleid**
                - Atualiza: **nada**
                - Exclui: **nunca**
            2. Adicionar colaborador à coorte. *se já existir dará certo?*
                - `cohortid`=**cooote.id**
                - `cohortid`=**colaborador.user.id**
4. Retornar URL do Diário e URL da Sala de Coordenação


### Sincronização da sala de coordenação

1. Validar JSON
    1. Sucesso: O JSON veio
    2. Sucesso: O JSON consegue ser decodificado com sucesso
    3. Sucesso: O JSON está conforme o esquema (falta)
2. Sincronizar o Issuer oAuth2 do SUAP (Mover isso pra ser feito no upgrade, aqui apenas pegaríamos o já existente)
3. Sincronizar Diário e Sala de Coordenação
    1. Sincronizar estrutura das categorias. 
        1. Sincronizar a categoria "Diários"
        2. Sincronizar a categoria do "Campus"
        3. Sincronizar a categoria do "Curso"
        4. Sincronizar a categoria do "Semestre", se Diário
        5. Sincronizar a categoria da "Turma", se Diário
        6. Retornar o ID da categoria da "Turma", se Diário, ou o ID da categoria do "Curso", se Coordenação
    2. Sincronizar o **Diário** na categoria da **Turma** ou **Coordenação** na categoria da **Curso**
    3. Sincronizar usuarios
        1. Sincronizar usuário
        2. Sincronizar grupos do usuário
            1. Cada grupo será conforme
            2. Sincronizar grupo (grupos: Entrada, Turma, Polo, Programa)
            3. Sincronizar inscrição do usuário no grupo
        2. Sincronizar usuário autenticação oAuth2
        3. Sincronizar preferencias padrões do usuário
        4. Sincronizar inscrição
        5. Inativar os ALUNOS que não vieram na sicronização, se Diário
    4. Sincronizar coortes
        1. Sincronizar coorte
        2. Sincronizar colaboradores da coorte
            1. Sincronizar usuário
            2. Adicionar colaborador à coorte. *se já existir dará certo?*
        3. Sincroniza o enrol no curso
            1. Sincronizar usuário
            2. Adicionar colaborador à coorte. *se já existir dará certo?*
4. Retornar URL do Diário e URL da Sala de Coordenação


### Sincronização das notas

1. Descrever.



### Sincronização das faltas

1. Descrever .


## Tipo de commits

- `feat:` novas funcionalidades.
- `fix:` correção de bugs.
- `refactor:` refatoração ou performances (sem impacto em lógica).
- `style:` estilo ou formatação de código (sem impacto em lógica).
- `test:` testes.
- `doc:` documentação no código ou do repositório.
- `env:` CI/CD ou settings.
- `build:` build ou dependências.
