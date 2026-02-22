=== WP Voting Plugin ===
Contributors: oneworldbynight
Tags: voting, elections, ballot, poll, ranked-choice
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 3.9.8
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

= 3.9.8 =
* Security: Restricted votes no longer leak to unauthorized users via show_results_before_closing
* Fix: Draft votes hidden from non-admin users

= 3.9.7 =
* Fix: Abstain in ranked ballots now truncates all preferences below it (RCV, STV, Sequential RCV, Condorcet)

= 3.9.6 =
* Fix: WordPress administrators can now always see live results on open votes, regardless of the "show results before closing" setting

= 3.9.5 =
* Fix: Sequential RCV votes now submit correctly — added missing `sequential_rcv` type to JS ballot handler

= 3.9.4 =
* Security: When AccessSchema is configured, it is the sole permission authority — users without cached ASC roles are denied access instead of falling back to WordPress capabilities

= 3.9.3 =
* Performance: Participation tracker now queries only users whose cached roles match the vote's specific voting_roles patterns via SQL LIKE, instead of all users with any AccessSchema role

= 3.9.2 =
* Performance: Participation tracker now queries only users with AccessSchema cached roles (or matching WP roles) instead of all site users

= 3.9.1 =
* Fix: Invalid ballot JSON now returns an error instead of silently falling back to a string value
* Fix: Null-safe access to user object when recording consent objector identity
* Fix: Consent-to-FPTP conversion now uses atomic SQL update to prevent race conditions from concurrent requests
* Fix: Ballots rejected with a clear message when voting options are missing or corrupted
* Fix: Participation tracker limits get_users() to 1000 results to prevent unbounded queries
* Fix: RCV algorithm handles empty ballot edge case with full result structure instead of division-by-zero
* Improvement: Centralized hardcoded Abstain string into WPVP_ABSTAIN_LABEL constant across all 7 algorithm files

= 3.9.0 =
* Performance: Participation tracker now primes WordPress object cache before user loops, reducing N+1 queries to 5 total queries regardless of user count
* Improvement: AccessSchema permission fallback now logs to PHP error log when users have no cached roles, aiding debugging of permission chain issues
* Improvement: Consent agenda objection now records the objector's identity (user ID, display name, username, role, timestamp) in vote settings JSON
* Fix: Consent agenda conversion is now idempotent — duplicate AJAX submissions return a safe "already converted" response instead of failing

= 3.8.9 =
* Elementor widgets updated: Vote List status dropdown simplified to Open / Vote Results / All
* Elementor Vote Detail and Results widgets now support dynamic mode (ID=0 reads vote from URL parameter)
* Elementor editor shows placeholder with instructions when widgets are in dynamic mode

= 3.8.8 =
* Simplified default pages: removed redundant "Open Votes" page, renamed "Closed Votes" to "Vote Results"
* New installs get two user-facing pages: Voting Dashboard (open votes) and Vote Results (closed/completed/archived)

= 3.8.7 =
* Fix: Vote description now preserves line breaks and paragraph formatting on the public vote page (applies wpautop)

= 3.8.6 =
* Fix: Admins can now set a vote to "Open" with a future opening date — vote publishes immediately and front-end shows "Opens on {date}" until the date arrives
* Fix: Open notification is deferred via wp_cron and fires at the scheduled opening date instead of immediately on publish
* Fix: Singleton vote results now format whole-number counts without trailing decimals
* Fix: Added sequential_rcv to ranked types in email ballot formatting

= 3.8.5 =
* Front-end vote list: added direct link icon next to every vote action button for opening in a new tab
* Admin vote list: added "View" row action that opens the public vote page in a new tab for any vote stage
* Admin vote list: "Results" row action now also opens in a new tab

= 3.8.3 =
* Sequential RCV tie resolution: when tied candidates fit within remaining seats, all tied candidates are seated and the election continues for remaining seats
* Per-seat display annotates co-winners with "(tied with ...)" notation
* Results banner marks tied-but-seated candidates with "(tied)" label

= 3.8.2 =
* Participation tracker now shows resolved entity titles (e.g., "Admin Coordinator") as links to detail pages instead of raw slugs
* Vote list no longer shows "Vote" button for votes with future opening dates — shows "Opens {date}" instead
* Groups in participation tracker sorted by resolved title

= 3.8.1 =
* Fix: Sequential RCV results banner now shows all winners and tied candidates (e.g., "Winners: A, B, (Tie: C, D)") instead of only the first winner
* Fix: Shows "Elected X of Y seats" when seats are unfilled due to ties
* Fix: Algorithm now surfaces tie information at top level for proper display

= 3.8.0 =
* New: Sequential RCV (multi-seat instant runoff) voting algorithm — fills multiple seats by running independent IRO elections in sequence, matching OWBN's historical election process
* New: Batch tie elimination for Sequential RCV — all tied-for-last candidates eliminated simultaneously per round
* New: Per-seat results display with round-by-round tables and "removed from ballots" annotations
* Fix: Vote history privacy leak — users with public/private eligibility votes no longer see other users' ballots in "Your Role's Vote History"
* Fix: Blind-open votes now mask other users' choices as "Voted" and hide comments in role history
* Fix: Anonymous votes hide "Cast By" column entirely in role history

= 3.7.0 =
* Fix: Prevent premature vote-open notifications — setting a vote to "Open" with a future opening date now saves as Draft; the hourly cron auto-opens it and sends notifications at the correct time
* Fix: Hide ballot form and voting options when a vote's opening date hasn't arrived yet; shows "Voting opens on {date}" message instead
* Fix: Guide wizard value propagation — all 11 settings fields and Step 2 metadata now correctly transfer to the vote editor form
* STV round-by-round results now display weighted vote counts formatted to 2 decimal places instead of raw floating-point values

= 3.6.0 =
* Added entity_type and entity_slug indexed columns to ballots table for vote-history lookups
* Backfill migration parses existing ballot_data JSON to populate new columns
* cast_ballot() and update_ballot() now auto-populate entity columns on write

= 3.5.0 =
* Voter list now shows resolved chronicle/coordinator titles instead of raw AccessSchema paths or user display names
* Role labels link to the chronicle-detail or coordinator-detail page via owbn-client page settings
* Redundant role suffix removed (e.g., "Sabbat Coordinator" instead of "Sabbat Coordinator — COORDINATOR")

= 3.4.0 =
* Non-blind votes (show results before closing) are now visible to non-logged-in visitors
* Both the vote listing and live results are accessible to guests when a vote has "Show results while voting is open" enabled
* Blind votes and completed votes retain their existing visibility rules

= 3.3.0 =
* Participation tracker accordion on vote results: shows Voted / Not Voted columns grouped by chronicle or coordinator name
* Restricted non-anonymous votes show both Voted and Not Voted; public/private show Voted only; anonymous votes hide tracker
* Increased vote comment textarea limit from 1,000 to 5,000 characters
* Updated embedded AccessSchema client to v2.4.0 (shared role cache across all AccessSchema-enabled plugins)
* Fixed cross-plugin role cache isolation: all plugins now read/write a single shared cache key so roles refreshed by one plugin are visible to all

= 3.2.3 =
* Add function_exists guard to AccessSchema client to prevent fatal on multi-plugin installs

= 3.2.2 =
* Updated embedded AccessSchema client to v2.1.1
* Local mode support, grouped role display, improved Users table column

= 3.2.1 =
* Abstain exclusion extended to all voting algorithms: RCV, STV, Condorcet, and Disciplinary
* Previously only Singleton (FPTP) excluded Abstain from results

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
