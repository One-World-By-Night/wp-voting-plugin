=== WP Voting Plugin ===
Contributors: oneworldbynight
Tags: voting, elections, ballot, poll, ranked-choice
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A flexible voting system supporting multiple algorithms including Single Choice, Ranked Choice (RCV), Single Transferable Vote (STV), Condorcet, Disciplinary, and Consent Agenda.

== Description ==

WP Voting Plugin provides a comprehensive voting system for WordPress. It supports six voting algorithms, role-based access control (via AccessSchema or WordPress capabilities), automated scheduling, and email notifications.

**Voting Types:**

* **Single Choice** -- Simple majority vote (first-past-the-post).
* **Ranked Choice Voting (RCV)** -- Instant-runoff with elimination rounds.
* **Single Transferable Vote (STV)** -- Multi-winner proportional representation using the Droop quota.
* **Condorcet** -- Pairwise comparison to find the candidate who beats all others head-to-head.
* **Disciplinary** -- Cascading severity levels from most to least severe.
* **Consent Agenda** -- Proposal passes by silence; any ballot cast is an objection.

**Key Features:**

* Create and manage votes from the WordPress admin
* Multiple visibility levels: Public, Private, Restricted (role-based)
* Automatic open/close scheduling via WordPress cron
* Email notifications when votes open or close
* CSV export of results
* AccessSchema integration for hierarchical role-based permissions
* WordPress capability fallback when AccessSchema is not configured
* Wildcard patterns in role paths (e.g., `Chronicle/*/CM`, `Players/**`)
* Elementor widgets for embedding votes and results
* Shortcodes: `[wpvp_votes]`, `[wpvp_vote]`, `[wpvp_results]`

== Installation ==

1. Upload the `wp-voting-plugin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **WP Voting > Settings** to configure the plugin.
4. Optionally configure AccessSchema for hierarchical role-based permissions.
5. Navigate to **WP Voting > Add New** to create your first vote.

== Frequently Asked Questions ==

= What PHP version is required? =

PHP 7.4 or higher is required.

= Does this plugin support multisite? =

No. WP Voting Plugin is designed for single-site WordPress installations only.

= How does the permission system work? =

The plugin uses a priority chain: if AccessSchema is configured and reachable, it checks role paths there first. If AccessSchema is unavailable or not configured, it falls back to WordPress roles and capabilities.

= What are wildcard patterns? =

When using AccessSchema, you can use `*` to match a single path segment (e.g., `Chronicle/*/CM` matches any chronicle's CM) and `**` to match one or more segments (e.g., `Players/**` matches all player roles at any depth).

= How does the Consent Agenda work? =

A consent agenda proposal passes automatically when the vote closes unless someone files an objection. Any ballot submitted is treated as an objection. The vote auto-processes on close via the hourly cron.

== Changelog ==

= 2.0.0 =
* Complete rebuild of the voting plugin.
* Six voting algorithms: Single Choice, RCV, STV, Condorcet, Disciplinary, Consent Agenda.
* Priority-chain permission system (AccessSchema first, WordPress capabilities fallback).
* Wildcard patterns for AccessSchema role paths.
* Wizard-style vote creation guide in the admin panel.
* Automated scheduling with WordPress cron (auto-open, auto-close, auto-process).
* Email notifications for vote open/close events.
* CSV export for vote results.
* Elementor widgets for vote lists, individual votes, and results.
* Data migration utility from v1.
* Comprehensive admin Guide page.

== Upgrade Notice ==

= 2.0.0 =
Complete rebuild. Use the built-in migration utility (WP Voting > Settings > Migration) to import data from v1.
