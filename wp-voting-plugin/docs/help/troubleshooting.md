# Voting Troubleshooting

Fixes for what goes wrong most with council votes: members who can't see or cast a vote, votes that don't open or close, missing results and missing emails.

## Who can use this

Voters for the first entries; site admins for the rest.

## How to get there

Voters start at council.owbn.net → **Voting Dashboard**. Admins start at wp-admin → **WP Voting**.

## The screen

Most fixes use the vote's own settings in [Creating and Editing a Vote](vote-editor.md) and the tools in [Voting Settings](settings.md).

## Common tasks

### A member can't vote

1. Open the vote and check **Who Can Vote (Roles / Groups)** covers the member's role, such as `chronicle/*/cm` for a CM.
2. Check the vote is **Open** and inside its dates.
3. Have the member sign out and in again, so this site reads their current roles.

### A vote is stuck in Closed

1. Go to **Settings** → **Advanced**.
2. Click **Process All Closed Votes**.

## Things to know

- Votes open, close and get counted by a job that runs every hour and at midnight, so expect up to an hour's delay.
- A member's roles come from sso.owbn.net. If they changed recently, signing out and in again picks them up.

## Troubleshooting

- **A vote isn't on the Voting Dashboard.** It's a draft, or its **Visibility** doesn't include the member's roles.
- **"You do not have permission to vote on this proposal."** The member's roles aren't under **Who Can Vote (Roles / Groups)**, or this site hasn't read their new role yet.
- **A vote didn't open on its date.** It's a draft, not scheduled: drafts never open on their own. Set its **Status** to **Open**; with a future opening date it becomes **Scheduled**.
- **A vote opened or closed late.** Up to an hour is normal. Longer means the site's scheduled jobs aren't running; ask the Web team.
- **A closed vote shows no result.** Wait for the next hourly run, or use **Process All Closed Votes**.
- **Members didn't get the "vote opened" email.** It goes to the recipients named on the vote, or else to the defaults under **Settings** → **General**. With neither set, it goes to every member who can vote on it, plus the admin address.
- **Someone's ballot disappeared.** Another member voted for the same chronicle or office, and the latest ballot replaced it.

## Related

- [Voting](casting-a-ballot.md)
- [Creating and Editing a Vote](vote-editor.md)
- [Voting Settings](settings.md)
- [WP Voting Plugin Overview](../overview.md)
