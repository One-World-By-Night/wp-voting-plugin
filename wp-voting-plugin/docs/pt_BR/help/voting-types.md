# Tipos de votação

As sete formas de contar uma votação, e o que os votantes veem na cédula em cada uma.

## Quem pode usar

Quem lê uma votação ou o resultado dela. Os administradores do site e a equipe de Web escolhem o tipo ao montar a votação.

## Como chegar

wp-admin em council.owbn.net → **WP Voting** → **Add New** → **Voting Type**.

## A tela

- **Single Choice (FPTP)** - cada votante escolhe uma opção; a mais votada vence. Abstain é contado, mas não toma partido. Use para aprovar ou negar.
- **Ranked Choice Voting** - os votantes arrastam as opções na sua ordem. Se ninguém tem maioria, a última colocada sai e as cédulas dela passam para a próxima escolha, rodada após rodada, até alguém ter maioria. Ninguém precisa ranquear todas.
- **Single Transferable Vote** - votação ranqueada para vários assentos de uma vez. Uma opção que alcança a cota (a cota Droop) ganha um assento, e os votos que sobram passam adiante com peso menor. Ponha em **Number of Winners** o número de assentos.
- **Sequential Ranked Choice (Multi-Seat)** - vários assentos preenchidos um de cada vez: uma contagem ranqueada para o primeiro assento, depois quem venceu sai de todas as cédulas e uma nova contagem corre para o próximo.
- **Condorcet Method** - votação ranqueada em que vence a opção que ganha de todas as outras no confronto direto. Se nenhuma ganha, o método Schulze decide.
- **Disciplinary Vote** - os votantes escolhem um nível de punição, do mais grave para o mais leve. Os votos descem em cascata do nível mais grave até um alcançar o seu limite; esse nível é o resultado.
- **Consent Agenda** - o item passa, a menos que alguém faça objeção antes de fechar. Quem concorda não faz nada. A primeira objeção o transforma numa votação completa de Approve / Deny / Abstain que corre por uma semana, e um `[AUTOPASS]` no título vira `[OBJECTION]`.

## Tarefas comuns

### Escolher um tipo

1. Uma decisão, sim ou não: **Single Choice (FPTP)**.
2. Um assento, vários candidatos: **Ranked Choice Voting** ou **Condorcet Method**.
3. Vários assentos: **Single Transferable Vote** ou **Sequential Ranked Choice (Multi-Seat)**.
4. Uma sanção: **Disciplinary Vote**.
5. Assunto de rotina que ninguém deve contestar: **Consent Agenda**.

## Bom saber

- **Majority Threshold** define a parcela que um vencedor precisa, nos tipos que usam isso: Simple Majority (50% + 1), 2/3, 3/4 ou uma porcentagem própria.
- Uma objeção feita fora da cédula, como numa reunião, é registrada pelos administradores em **Objection By**, o que transforma o item numa votação completa do mesmo jeito, veja [Criar e editar uma votação](vote-editor.md).
- Resultados ranqueados podem surpreender. A tabela rodada a rodada na página do resultado mostra para onde cada cédula foi.

## Solução de problemas

- **Um resultado ranqueado parece errado.** Leia as rodadas na página do resultado: quem saiu quando, e para onde as cédulas dele foram.
- **Um item de consentimento virou uma votação de Approve / Deny / Abstain.** Alguém fez objeção. Agora é uma votação comum.

## Relacionados

- [Criar e editar uma votação](vote-editor.md)
- [Resultados das votações](results.md)
- [Votar](casting-a-ballot.md)
