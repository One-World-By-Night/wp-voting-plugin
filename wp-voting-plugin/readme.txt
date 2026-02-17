=== WP Voting Plugin ===
Contributors: oneworldbynight
Tags: voting, elections, ballot, poll, ranked-choice
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 3.2.0
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

= 3.2.0 =
* Consent→FPTP conversion now includes Abstain option alongside Approve/Deny
* Abstain votes are tracked but excluded from winner determination and percentages

= 3.1.2 =
* Fix: Admins can now object to consent votes even when they don't hold a matching voting role
* Admin bypass in eligible roles check returns "Administrator" fallback role

= 3.1.1 =
* Global default notification recipient settings in WP Voting > Settings > General
* 3-tier recipient cascade: per-vote override → global default → computed fallback
* Per-vote recipient placeholders now reference system settings page

= 2.8.1 =
* Guide wizard role fields upgraded to Select2 multi-selects (matching vote editor UX)
* Role template loader now populates wizard Select2 fields correctly
* Disciplinary auto-populate now works in the Guide wizard when switching voting type
* Wizard form submission sends additional_viewers to backend

= 2.8.0 =
* Additional vote history viewers: per-vote setting allows cross-role ballot visibility using slug-binding patterns
* Patterns like `Chronicle/*/HST` let HSTs see ballots cast by CMs from the same chronicle (slug must match)
* New "Additional Vote History Viewers" field in vote editor with Select2 multi-select and role template loader
* Guide wizard updated with Additional Vote History Viewers section
* Merged Interactive Vote Builder intro and wizard into a single unified block in the Guide page

= 2.7.0 =
* Optional voter comments: per-vote admin setting allows voters to add an optional comment/rationale with their ballot
* Comments displayed with attribution on non-anonymous votes, without attribution on anonymous votes
* Role-based vote history: logged-in users can see votes cast under their current eligible roles
* Vote history supports role succession — new role holders can see how the role previously voted
* Users can always view their own vote details regardless of current role eligibility
* Live results and comments now properly gated by the "Show results while voting is open" setting
* Admins can view full voter list (including comments) on all votes, including anonymous

= 2.6.0 =
* Full internationalization (i18n) support for TranslatePress and other translation plugins
* All JavaScript-generated strings now pass through WordPress translation functions via wp_localize_script
* Public UI strings localized: modal messages, revote button, loading states, error messages, accessibility labels
* Admin UI strings localized: option placeholders, disciplinary levels, status messages, confirmation dialogs
* Fixes untranslated buttons, select placeholders, and status text when using TranslatePress or similar tools

= 2.5.8 =
* Fixed revoting on ranked votes (RCV, STV, Condorcet) not restoring previous ranking order
* Previous ballot data now correctly unwrapped from new ballot format when pre-populating form

= 2.5.7 =
* Fixed ballot submission failing silently on RCV, STV, and Condorcet votes for single-role users
* Role selection dropdown now only requires input when visible (multiple eligible roles)
* Fixed voting role not being sent for single-role users during ballot submission

= 2.5.6 =
* Improved tie display: options marked as "Tied" instead of "Winner" when there's a tie in singleton votes
* Added tied_candidates to live results winner_data for proper tie banner display
* Moved live results to display AFTER ballot form, allowing users to vote before seeing current standings

= 2.5.5 =
* Fixed live results display: result data now stored as arrays instead of JSON strings in synthetic results object
* Live results now properly render vote totals and winner information on open votes

= 2.5.4 =
* Fixed error handling for live results to catch all PHP errors and throwables, not just exceptions
* Prevents fatal errors from breaking vote pages when calculating live results

= 2.5.3 =
* Fixed live results calculation for open votes
* Improved on-the-fly result processing without database saves

= 2.5.2 =
* Show live/current vote totals on open votes before casting ballot
* Real-time results visibility for voters to see current standings

= 2.5.1 =
* Enhanced non-anonymous vote results to show detailed voter list with display name, role, vote choice, and vote date
* Allow consent agenda votes to have simultaneous open/close times for instant passage
* Fixed wildcard role matching to work with user's personal cached roles
* Fixed role selection to only show roles user actually has

= 2.5.0 =
* Added role-based voting attribution: users with multiple eligible roles must select which role they're voting as
* Ballot data now stores voting_role, display_name, and username for accountability
* Consent voting results display voter attribution as "Display Name (username) role-path"
* Data preserved even if user or role is deleted later
* Maintains backward compatibility with existing ballots

= 2.4.3 =
* Fixed anonymous ID handling in disciplinary voting
* Fixed consent vote result display issues
* Updated vote count logic for consistency

= 2.4.2 =
* Fixed critical permission bypass bug in restricted voting
* Improved role matching with AccessSchema cached roles

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
