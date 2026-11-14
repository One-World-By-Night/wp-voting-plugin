# Voting Types

The seven ways a vote can be counted, and what voters see on the ballot for each.

## Who can use this

Anyone reading a vote or its result. Site admins and the Web team pick the type when they set up a vote.

## How to get there

wp-admin on council.owbn.net → **WP Voting** → **Add New** → **Voting Type**.

## The screen

- **Single Choice (FPTP)** - each voter picks one option; the most votes wins. Abstain is counted but doesn't take sides. Use it for approve or deny.
- **Ranked Choice Voting** - voters drag the options into their order. If nobody has a majority, the last-place option drops out and its ballots move to their next choice, round after round, until one has a majority. Voters don't have to rank everything.
- **Single Transferable Vote** - ranked voting for several seats at once. An option that reaches the quota (the Droop quota) wins a seat, and its extra votes pass on at reduced weight. Set **Number of Winners** to the number of seats.
- **Sequential Ranked Choice (Multi-Seat)** - several seats filled one at a time: a ranked-choice count for the first seat, then the winner comes off every ballot and a fresh count runs for the next.
- **Condorcet Method** - ranked voting where the winner is the option that beats every other one head to head. If none does, the Schulze method decides.
- **Disciplinary Vote** - voters pick a punishment level, most severe first. Votes cascade down from the most severe level until one reaches its threshold; that level is the outcome.
- **Consent Agenda** - the item passes unless someone objects before it closes. Voters who agree do nothing. The first objection turns it into a full Approve / Deny / Abstain vote that runs for a week, and an `[AUTOPASS]` in its title becomes `[OBJECTION]`.

## Common tasks

### Pick a type

1. One decision, yes or no: **Single Choice (FPTP)**.
2. One seat, several candidates: **Ranked Choice Voting** or **Condorcet Method**.
3. Several seats: **Single Transferable Vote** or **Sequential Ranked Choice (Multi-Seat)**.
4. A sanction: **Disciplinary Vote**.
5. Routine business nobody is expected to oppose: **Consent Agenda**.

## Things to know

- **Majority Threshold** sets the share a winner needs where the type uses one: Simple Majority (50% + 1), 2/3, 3/4 or a custom percentage.
- An objection raised outside the ballot, such as at a meeting, is entered by the admins in **Objection By**, which turns the item into a full vote the same way, see [Creating and Editing a Vote](vote-editor.md).
- Ranked results can surprise. The round-by-round table on the result page shows exactly where each ballot went.

## Troubleshooting

- **A ranked result looks wrong.** Read the rounds on the result page: who dropped out when, and where their ballots moved.
- **A consent item turned into an Approve / Deny / Abstain vote.** Somebody objected. It's now an ordinary vote.

## Related

- [Creating and Editing a Vote](vote-editor.md)
- [Vote Results](results.md)
- [Voting](casting-a-ballot.md)
