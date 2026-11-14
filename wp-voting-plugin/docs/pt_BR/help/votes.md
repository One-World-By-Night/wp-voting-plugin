# All Votes

Todas as votações do site, em qualquer estágio, com o tipo, o estágio e o número de cédulas.

## Quem pode usar

Os administradores do site e a equipe de Web.

## Como chegar

wp-admin em council.owbn.net → **WP Voting** → **All Votes**.

## A tela

- **Add New**, ao lado do título, começa uma votação, veja [Criar e editar uma votação](vote-editor.md).
- **All**, **Draft**, **Scheduled** e os outros estágios acima da lista mostram as votações naquele estágio.
- **All Statuses** e **All Types**, e depois **Filter**, filtram a lista; **Clear Filters** desfaz. **Search Votes** procura nos títulos e descrições.
- Uma linha por votação: **Title**, **Type**, **Status**, **Responses** (cédulas dadas), **Created By** e **Date**.
- Sob cada título:
  - **Edit** - abre a votação.
  - **View** - a página pública da votação, numa aba nova.
  - **Results** - a página do resultado, depois que ela fecha.
  - **Delete** - apaga a votação, as cédulas e o resultado de vez.
- **Bulk actions**: **Delete**, **Set Open** e **Set Closed** para as votações marcadas.

### Estágios

- **Draft** - só os administradores a veem, e ela nunca abre sozinha.
- **Scheduled** - os votantes a veem com a data de abertura; ela abre nessa data. Um item da pauta de consentimento já recebe objeções.
- **Open** - recebendo cédulas.
- **Closed** - a votação acabou e o resultado está sendo apurado.
- **Completed** - o resultado saiu.
- **Withdrawn** - retirada antes de terminar. As cédulas já dadas continuam.
- **Archived** - guardada, mas fora do caminho.

## Tarefas comuns

### Achar uma votação

1. Escolha um estágio em **All Statuses** ou um tipo em **All Types**, ou digite parte do título em **Search Votes**.
2. Clique em **Filter** ou **Search Votes**.

### Retirar uma votação

1. Clique em **Edit** sob a votação.
2. Mude **Status** para **Withdrawn** e clique em **Update**.

## Bom saber

- As votações abrem, fecham e são apuradas sozinhas: uma rotina confere a cada hora, e de novo à meia-noite.
- **Delete** não tem volta, e a votação some do histórico de votos de todas as crônicas e coordenadores. Para tirar uma votação do ar, retire-a ou arquive-a.
- **Set Open** numa votação cuja data de abertura ainda não chegou a deixa **Scheduled** até essa data.

## Solução de problemas

- **Uma votação não abriu ou não fechou na hora.** A rotina de hora em hora ainda não rodou. Se passou de uma hora, as rotinas agendadas do site não estão rodando; fale com a equipe de Web.
- **Uma votação fechada está sem resultado.** A próxima rodada a apura. Um administrador do site pode apurá-la já em **Settings** → **Advanced** → **Process All Closed Votes**.

## Relacionados

- [Criar e editar uma votação](vote-editor.md)
- [Resultados das votações](results.md)
- [Visão geral do WP Voting Plugin](../overview.md)
