# WP Voting Plugin

WordPress voting system supporting multiple algorithms with AccessSchema role-based permissions and WordPress capability fallback.

## Algorithms

- **FPTP** (First Past the Post) — single-winner plurality
- **RCV / IRV** — ranked choice with instant runoff elimination
- **Sequential RCV** — fill multiple seats via sequential IRV rounds
- **STV** — multi-winner proportional representation (Droop quota)
- **Condorcet** — pairwise comparison with Schulze/Smith-set fallback
- **Consent Agenda** — auto-pass by silence, objection converts to FPTP
- **Disciplinary** — majority threshold with configurable punishments

## Features

- Per-vote role restrictions via AccessSchema or WordPress capabilities
- Configurable open/close dates with cron-based auto-open and auto-close
- Anonymous or attributed ballots
- Email notifications (open, close, results, reminders)
- Detailed results with round-by-round breakdowns for ranked methods
- Admin vote editor, bulk actions, search and filtering
- Elementor widgets for frontend display
- Built-in admin guide with worked examples
- Data migration utility from v1.x

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Version History

See [VERSION_HISTORY.md](VERSION_HISTORY.md).
