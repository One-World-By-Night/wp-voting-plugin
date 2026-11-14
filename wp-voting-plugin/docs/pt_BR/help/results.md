# Resultados das votações

O que a página de resultado de uma votação mostra, enquanto ela está aberta e depois de apurada.

## Quem pode usar

- Quem pode ver a votação, depois que ela fecha.
- Enquanto está aberta, se a votação mostra resultados ao vivo.
- Os administradores do site e quem criou a votação, sempre.

## Como chegar

council.owbn.net → **Vote Results** → a votação, ou **Results** sob ela em wp-admin → **WP Voting** → **All Votes**. Uma votação ainda aberta mostra a contagem ao vivo na própria página, sob a cédula.

## A tela

- O título da votação, as datas, e quem propôs e quem apoiou.
- **Total votes** e quando o resultado foi calculado (**Calculated**).
- **Export CSV** - a votação e todas as cédulas, para os administradores do site.
- O desfecho: quem venceu, **Passed** ou **Did Not Pass**, ou um empate.
- Uma tabela com os votos e a parcela de cada opção.
- Por tipo:
  - Votações ranqueadas: **Round-by-Round Results**, com quem foi eliminado em cada rodada.
  - Votações com vários assentos: cada assento e quem o ocupou.
  - **Condorcet Method**: o **Head-to-Head Matrix** e o **Final Rankings**.
  - **Disciplinary Vote**: o **Cascade (most → least severe)**, com os votos de cada nível e se ele alcançou o limite.
  - **Consent Agenda**: **Passed by Consent**, ou as objeções feitas e os **Objectors**.
- **Voter Comments**, quando a votação aceitou comentários.
- **Voter List** e **Participation**: quem votou, com que cargo, e quem ainda não.
- A **Admin Note**, quando ela é pública.

Enquanto uma votação está aberta com resultados ao vivo, a página diz "These are live results" e continua contando.

## Tarefas comuns

### Ver como uma votação terminou

1. Em **Vote Results**, ache a votação e abra-a.
2. Numa votação ranqueada, leia as rodadas para ver para onde cada cédula foi.

### Baixar as cédulas

1. Na página do resultado, clique em **Export CSV**.

## Bom saber

- Numa votação anônima, as contagens aparecem, mas não quem escolheu o quê.
- **Showing votes cast under your current eligible roles** quer dizer que você vê as cédulas que os seus cargos deixam ver, não todas.
- Um resultado é apurado uma vez, quando a votação fecha. Um administrador do site pode apurar as atrasadas em **Settings** → **Advanced** → **Process All Closed Votes**.

## Solução de problemas

- **Sem resultado numa votação fechada.** Ela ainda não foi apurada; será dentro de uma hora.
- **Sem o botão Export CSV.** Ele é para os administradores do site.
- **Um resultado ranqueado parece errado.** Siga as rodadas: quem saiu, e para onde as cédulas dele foram.

## Relacionados

- [Votar](casting-a-ballot.md)
- [Tipos de votação](voting-types.md)
- [All Votes](votes.md)
