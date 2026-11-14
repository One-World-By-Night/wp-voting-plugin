# Solução de problemas das votações

Soluções para o que mais dá errado nas votações do conselho: membros que não veem ou não conseguem votar, votações que não abrem ou não fecham, resultados que faltam e emails que não chegam.

## Quem pode usar

Os votantes, nas primeiras entradas; os administradores do site, no resto.

## Como chegar

Os votantes começam em council.owbn.net → **Voting Dashboard**. Os administradores começam em wp-admin → **WP Voting**.

## A tela

A maioria das soluções usa as configurações da própria votação em [Criar e editar uma votação](vote-editor.md) e as ferramentas de [Configurações das votações](settings.md).

## Tarefas comuns

### Um membro não consegue votar

1. Abra a votação e confira se **Who Can Vote (Roles / Groups)** cobre o cargo do membro, como `chronicle/*/cm` para um CM.
2. Confira se a votação está **Open** e dentro das datas.
3. Peça ao membro para sair e entrar de novo, para este site ler os cargos atuais dele.

### Uma votação está presa em Closed

1. Vá a **Settings** → **Advanced**.
2. Clique em **Process All Closed Votes**.

## Bom saber

- As votações abrem, fecham e são apuradas por uma rotina que roda a cada hora e à meia-noite, então conte com até uma hora de atraso.
- Os cargos de um membro vêm de sso.owbn.net. Se mudaram há pouco, sair e entrar de novo os atualiza.

## Solução de problemas

- **Uma votação não está no Voting Dashboard.** É um rascunho, ou a **Visibility** dela não inclui os cargos do membro.
- **"You do not have permission to vote on this proposal."** Os cargos do membro não estão em **Who Can Vote (Roles / Groups)**, ou este site ainda não leu o cargo novo dele.
- **Uma votação não abriu na data.** É um rascunho, não está agendada: rascunhos nunca abrem sozinhos. Mude o **Status** para **Open**; com data de abertura futura, ela fica **Scheduled**.
- **Uma votação abriu ou fechou atrasada.** Até uma hora é normal. Mais que isso quer dizer que as rotinas agendadas do site não estão rodando; fale com a equipe de Web.
- **Uma votação fechada não mostra resultado.** Espere a próxima rodada de hora em hora, ou use **Process All Closed Votes**.
- **Os membros não receberam o email de "votação aberta".** Ele vai para os destinatários indicados na votação, ou então para os padrões em **Settings** → **General**. Sem nenhum dos dois, vai para todos os membros que podem votar nela, mais o endereço do administrador.
- **A cédula de alguém sumiu.** Outro membro votou pela mesma crônica ou escritório, e a cédula mais recente substituiu a dela.

## Relacionados

- [Votar](casting-a-ballot.md)
- [Criar e editar uma votação](vote-editor.md)
- [Configurações das votações](settings.md)
- [Visão geral do WP Voting Plugin](../overview.md)
