# WP Voting Plugin

The voting engine for One World by Night. Runs all organizational votes -- coordinator elections, bylaw proposals, disciplinary actions.

Version: 3.13.0
Deployed to: council.owbn.net

## What It Does

A full-featured voting system supporting seven algorithms, role-based voter eligibility, scheduled open/close, email notifications, and detailed results with round-by-round breakdowns for ranked methods.

### Voting Types

- FPTP -- First Past the Post, single-winner plurality
- RCV / IRV -- Ranked choice with instant runoff elimination
- Sequential RCV -- Fill multiple seats via sequential IRV rounds
- STV -- Multi-winner proportional representation (Droop quota)
- Condorcet -- Pairwise comparison with Schulze/Smith-set fallback
- Consent Agenda -- Auto-pass by silence, objection converts to FPTP
- Disciplinary -- Majority threshold with configurable punishments

### Key Features

- Per-vote role restrictions via accessSchema or WordPress capabilities
- Configurable open/close dates with cron-based auto-open and auto-close
- Anonymous or attributed ballots
- Email notifications (open, close, results, reminders)
- Admin vote editor, bulk actions, search and filtering
- Elementor widgets for front-end display
- Used by owbn-election-bridge for coordinator election management

## Requirements

- WordPress 5.0+, PHP 7.4+
- accessSchema (via owbn-core) for role-based voter eligibility

## License

GPL-2.0-or-later
