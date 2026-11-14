# Criar e editar uma votação

Tudo sobre uma votação: o que se decide, como se conta, quando ela corre, quem a vê e quem vota.

## Quem pode usar

Os administradores do site e a equipe de Web.

## Como chegar

wp-admin em council.owbn.net → **WP Voting** → **Add New**, ou **Edit** sob uma votação em **All Votes**.

## A tela

- **Title** e **Description** - o que os votantes leem primeiro.
- **Proposal Metadata**
  - **Proposal Type** - uma ou mais classificações, como Bylaw Amendment ou Election. Segure Ctrl (Cmd no Mac) para escolher várias.
  - **Proposed By** e **Seconded By** - digite para achar uma pessoa, ou digite qualquer nome.
  - **Objection By** - para um item da pauta de consentimento que recebeu objeção, veja abaixo.
- **Voting Type** - como as cédulas são contadas, veja [Tipos de votação](voting-types.md).
- **Voting Options** - uma linha por opção ou candidato, cada uma com descrição opcional. **+ Add Option** acrescenta uma; **Fill with Approve / Deny / Abstain** preenche as três de sempre. **Number of Winners** é para os tipos com vários assentos.
- **Status & Schedule** - **Status**, **Opens** e **Closes**.
- **Access Control** - **Visibility**: quem pode ver a votação.
  - **Public (anyone can view)**, **Private (logged-in users can view)** ou **Restricted (specific roles can view)** com **Allowed Roles / Groups**.
- **Who Can Vote** - **Voting Eligibility**: **Anyone (public voting)**, **Logged-in users only** ou **Specific roles/groups** com **Who Can Vote (Roles / Groups)**.
- **Additional Vote History Viewers** - cargos que podem ver como cargos relacionados votaram.
- **Load from template** e **Apply**, sob cada lista de cargos, a preenchem com um conjunto salvo, veja [Modelos de cargos e classificações](templates.md).
- **Vote Settings**
  - **Majority Threshold** - Simple Majority (50% + 1), 2/3, 3/4 ou uma porcentagem própria.
  - **Allow voters to change their vote**, **Show results while voting is open**, **Anonymous voting (hide voter names in results)** e **Allow voters to add an optional comment with their vote**.
- **Email Notifications** - **Notify when vote opens**, **Remind 1 day before close** e **Notify when vote closes (with results)**, cada um com seus destinatários (em branco usa os padrões de **Settings**), e **Allow voter email confirmations**.
- **Admin Note** - uma nota ou justificativa, mostrada na página do resultado se **Show this note publicly on the results page** estiver marcado.
- **Create Vote** ou **Update** salva; **Delete** apaga a votação de vez.

### Listas de cargos

Cada item entra como uma etiqueta própria: um caminho de cargo como `chronicle/mckn/cm`, ou um padrão. `*` vale por uma parte qualquer (`chronicle/*/cm` é o CM de cada crônica) e `**` por qualquer número de partes. Um papel do WordPress como `editor` também funciona.

## Tarefas comuns

### Montar uma votação

1. Clique em **Add New**.
2. Preencha o título, a descrição e quem propôs e quem apoiou.
3. Escolha o **Voting Type** e acrescente as opções.
4. Defina **Opens** e **Closes**. Uma votação nova começa abrindo em uma semana e fechando uma semana depois, à meia-noite.
5. Defina quem a vê e quem vota, com um modelo ou à mão.
6. Clique em **Create Vote**. Com data de abertura futura, ela fica **Scheduled** e abre sozinha.

### Transformar um item de consentimento com objeção numa votação completa

1. Abra o item da pauta de consentimento.
2. Digite o nome de quem fez a objeção em **Objection By**.
3. Clique em **Update**. O item vira uma votação de Approve / Deny / Abstain, e os votantes podem mudar as cédulas.

## Bom saber

- **Visibility** decide quem vê a votação; **Voting Eligibility** decide quem vota. Uma votação restrita precisa das duas preenchidas.
- A correspondência de cargos segue os cargos de cada membro em sso.owbn.net como este site os leu por último. Um membro que ganhou um cargo novo o recebe saindo e entrando de novo.
- Os administradores do site sempre veem e votam em todas as votações. A cédula deles leva o cargo mais próximo que combine, ou Administrator.
- As cédulas guardam o texto da opção. Renomear ou tirar uma opção depois que já votaram estraga essas cédulas; retire a votação e comece outra.
- Um rascunho nunca abre sozinho, seja qual for a data de abertura.

## Solução de problemas

- **"Title is required." ou "At least two options are required."** Preencha e salve de novo.
- **"Closing date must be after opening date."** Corrija as datas.
- **Um membro não vê ou não consegue votar.** Os cargos dele não combinam com **Allowed Roles / Groups** ou **Who Can Vote (Roles / Groups)**. Confira os caminhos dos cargos e peça ao membro para sair e entrar de novo.

## Relacionados

- [Tipos de votação](voting-types.md)
- [Modelos de cargos e classificações](templates.md)
- [All Votes](votes.md)
