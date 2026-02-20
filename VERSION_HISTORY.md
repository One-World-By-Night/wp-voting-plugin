# WP Voting Plugin - Version History

## Version 3.9.5 (Current - February 2026)

**Fix Sequential RCV ballot submission.**

### Bug Fixes

- **Sequential RCV voting broken**: The JavaScript ballot handler did not recognize `sequential_rcv` as a ranked voting type, causing it to fall through to the radio-button handler. Since ranked ballots render a sortable list (not radio buttons), `ballot_data` was sent as `undefined`, triggering "Invalid ballot data" on the server. Added `sequential_rcv` to the ranked type check in `public.js`.

### Technical Details

In `assets/js/public.js`, the type check on line 39 only covered `rcv`, `stv`, and `condorcet`. The `sequential_rcv` type (introduced for multi-winner ranked elections) was handled correctly on the PHP side (`class-ballot.php` validation and `ballot-form.php` template) but was missing from the JS submission handler. The fix adds `|| type === 'sequential_rcv'` to the conditional.

---

## Version 3.6.0 (February 2026)

**Indexed entity columns on ballots table for vote history lookup.**

### Database Changes

- **New columns**: `entity_type` (varchar 50) and `entity_slug` (varchar 255) added to `wp_wpvp_ballots` table
- **Composite index**: `idx_entity (entity_type, entity_slug)` for efficient lookups by chronicle or coordinator slug
- **Automatic backfill**: Existing ballots have entity data extracted from `ballot_data` JSON on upgrade
- **Forward population**: `cast_ballot()` and `update_ballot()` now populate entity columns on every write

### Technical Details

The `voting_role` field in `ballot_data` JSON (e.g., `chronicle/kony/cm` or `coordinator/sabbat/head`) is parsed via `parse_entity_from_role()` to extract the entity type and slug. This eliminates the need for `LIKE` queries on unindexed JSON text when building vote history views per chronicle or coordinator.

New public method `WPVP_Database::parse_entity_from_role($voting_role)` returns `array('type' => 'chronicle'|'coordinator'|null, 'slug' => string|null)`.

Migration runs automatically via `upgrade_to_360()` on first admin page load after update. Existing ballot data is untouched — only the new columns are populated.

---

## Version 3.5.0 (February 2026)

**Voter list role labels: resolved titles with detail page links.**

### Features

- **Resolved role labels**: Voter list now displays the resolved chronicle or coordinator title (e.g., "Sabbat Coordinator") instead of the raw AccessSchema path or user display name
- **Detail page links**: Role labels link directly to the chronicle-detail or coordinator-detail page using owbn-client page settings and the entity slug
- **Suffix removed**: Redundant role suffix dropped (no more "Sabbat Coordinator — COORDINATOR" or "Something Wicked — CM")
- **Graceful fallback**: When no ASC path is available or owbn-client is not active, falls back to the user's display name

### Technical Details

- `WPVP_Results_Display::resolve_role_label()` rewritten to call `owc_resolve_asc_path()` with `$with_suffix = false`, then build a link using `owc_option_name('coordinators_detail_page')` / `owc_option_name('chronicles_detail_page')`
- Voter list rendering switched from `esc_html()` to `wp_kses()` to allow anchor tags in role labels
- Voter entry now shows role label OR display name (not both) — role label takes priority

---

## Version 3.4.0 (February 2026)

**Non-blind votes visible to non-logged-in visitors.**

### Features

- **Guest visibility for non-blind votes**: Votes with "Show results while voting is open" enabled are now visible in the vote list to all visitors, including non-logged-in guests
- **Guest access to live results**: Live results on non-blind open votes are viewable by guests — no login required to see current standings
- Blind votes and completed votes retain their existing visibility rules (public/private/restricted)

### Technical Details

Two additions to `WPVP_Permissions`:
- `can_view_vote()`: Returns `true` for all visitors when the vote is open and has `show_results_before_closing` enabled, regardless of the vote's visibility setting
- `can_view_results()`: Returns `true` for all visitors under the same conditions, bypassing the login requirement

Non-logged-in users still see the "You must be logged in to vote" prompt and cannot cast ballots.

---

## Version 3.2.3 (February 2026)

**Fix potential fatal error when multiple plugins embed AccessSchema client.**

### Bug Fixes

- Wrapped `accessSchema_client_render_grouped_roles()` in `function_exists()` guard to prevent redeclaration fatal when multiple plugins load the AccessSchema client admin

---

## Version 3.2.2 (February 2026)

**Updated embedded AccessSchema client to v2.1.1.**

### Updates

- **AccessSchema client v2.1.1**: Local mode support for same-server installations, grouped role display in Users table, smart column visibility (only shown for remote clients), inline CSS styling, improved cache refresh logic
- Admin init remains disabled (WPVP has its own AccessSchema settings UI)

---

## Version 3.2.1 (February 2026)

**Abstain exclusion extended to all voting algorithms.**

### Bug Fixes

- **RCV**: Abstain filtered from candidate pool; abstain-only ballots counted and excluded from total votes
- **STV**: Abstain filtered from candidate pool; Droop quota recalculated after removing abstain ballots
- **Condorcet**: Abstain filtered from candidate pool; abstain-only ballots skip pairwise matrix entirely
- **Disciplinary**: Abstain removed from punishment cascade; excluded from threshold calculations, re-added for display

### Technical Details

All algorithms now follow the same pattern established in v3.2.0 for Singleton: Abstain is tracked for display but excluded from winner determination, percentage calculations, and vote thresholds. Ranked algorithms (RCV, STV, Condorcet) detect abstain-only ballots before valid-option filtering and subtract them from total_votes. Single-choice algorithms (Disciplinary) extract Abstain from raw counts post-tally, matching the Singleton approach.

---

## Version 3.2.0 (February 2026)

**Abstain option for consent-converted FPTP votes.**

### New Features

- **Abstain option**: Consent→FPTP conversion now creates Approve/Deny/Abstain options instead of just Approve/Deny
- **Abstain exclusion in algorithm**: Abstain votes are recorded and displayed but excluded from winner determination, percentage calculations, and rankings. A vote with 2 Approve, 1 Deny, and 10 Abstain correctly declares Approve the winner at 66.67%

### Technical Details

In `class-singleton.php`, Abstain votes are removed from `$vote_counts` before winner/percentage/ranking logic, then re-added for display. The `$total_valid_votes` denominator subtracts both invalid votes and abstentions. An event log entry records the abstention count.

---

## Version 3.1.2 (February 2026)

**Fix admin unable to object on restricted consent votes.**

### Bug Fixes

- **Admin role bypass for eligible voting roles**: Admins who pass `can_cast_vote()` (via the `manage_options` bypass) were blocked by `get_eligible_voting_roles()` returning an empty array when they didn't hold any of the vote's restricted `voting_roles`. Now returns `['Administrator']` as a fallback role for admins, consistent with the existing admin bypass logic.

---

## Version 3.1.1 (February 2026)

**Global default notification recipients in Settings page.**

### New Features

- **Global notification defaults**: Three new fields in WP Voting > Settings > General to configure default recipients for vote opened, closing reminder, and vote closed notifications
- **3-tier recipient cascade**: Notification recipients now resolve as: per-vote override → global system default → computed fallback (eligible voters/voters/admin)
- **Updated per-vote placeholders**: Per-vote recipient fields in both the vote editor and Guide wizard now reference "system default (Settings > General)" instead of hardcoded descriptions

### Technical Details

New options: `wpvp_default_notify_open_to`, `wpvp_default_notify_reminder_to`, `wpvp_default_notify_close_to` (comma-separated email strings). When a per-vote notification recipient field is blank, the system checks the global default before falling back to computed behavior (eligible voters + admin for opens, admin only for reminders, voters + admin for closes).

---

## Version 3.1.0 (February 2026)

**Consent agenda overhaul and email formatting improvements.**

### New Features

- **Consent→FPTP conversion**: When someone files an objection on a consent agenda vote, the vote automatically converts to a singleton (FPTP) vote with Approve/Deny options, all ballots are cleared, and revoting is enabled
- **Consent 7-day review period**: Consent votes now default to a 7-day closing window instead of instant close, giving members time to review and object
- **Consent date bypass**: Objections can be filed any time while a consent vote is in the "open" stage, regardless of the opening/closing date window
- **Consent list view labels**: Vote list shows "Object" (red) and "Objected" (green) buttons for consent agenda items instead of generic Vote/Voted labels

### Bug Fixes

- **Email confirmation formatting**: Voter confirmation emails now display ballot data as clean labeled text (choice, voting role, comment) instead of raw JSON
- **Double-encoding fix**: Vote data passed through `update_vote()` is no longer double-JSON-encoded when converting consent votes

### Technical Details

Consent conversion is handled in `class-ballot.php` after the objection ballot is saved. The code deletes all ballots, updates `voting_type` to `singleton`, sets `voting_options` to Approve/Deny, and enables `allow_revote` in settings. The JS frontend detects the `converted` flag in the AJAX response and reloads the page after 2 seconds.

The email formatter in `class-notifications.php` now unwraps the enriched ballot payload (`{choice, voting_role, display_name, username, voter_comment}`) and formats each field as a labeled line instead of dumping raw JSON.

---

## Version 3.0.0 (February 2026)

**Smart action buttons, wider modals, per-vote email configuration, and public results visibility.**

### New Features

- **Context-aware list view buttons**: Action column now shows four distinct states — "Vote" (can vote, hasn't yet), "Update" (voted, can change), "Voted" (voted, can't change, green badge), "View" (can't vote)
- **Wider vote modal**: Lightbox modal widened from 900px to 80% viewport width for better readability
- **Voter email confirmation with custom recipients**: When opting in to email confirmation, voters now see an editable email field pre-filled with their address, supporting comma-separated additional recipients
- **Per-vote email notification settings**: Each vote can now configure its own notification behavior:
  - Notify when vote opens (with custom recipient override)
  - Remind 1 day before close (with custom recipient override)
  - Notify when vote closes with results (with custom recipient override)
  - Enable/disable voter confirmation emails per vote
  - All fields default to system-level settings when left blank
- **Public live results for guests**: Non-logged-in users can now see live results on public votes with "show results before closing" enabled

### Bug Fixes

- **PHP 7.4 compatibility**: Removed PHP 8.0+ syntax (`int|false` union type in class-migration.php, `match()` expression in AccessSchema users.php)
- **AccessSchema null reference crash**: Fixed fatal error in `client-api.php` when AccessSchema API function doesn't exist (e.g., when AccessSchema server plugin isn't installed)

### Technical Details

The list view in `vote-list.php` now queries both `can_cast_vote()` and `user_has_voted()` independently for open votes. This distinguishes "can vote + has voted" (Update) from "can't vote + has voted" (Voted). A new `.wpvp-btn--success` CSS class provides the green styling for the Voted state.

Per-vote email settings are stored in the existing `settings` JSON column alongside other vote settings. New keys: `notify_on_open`, `notify_before_close`, `notify_on_close`, `notify_voter_confirmation` (booleans) and `notify_open_to`, `notify_reminder_to`, `notify_close_to` (comma-separated email strings). The notification system checks these per-vote overrides first, falling back to the global system defaults.

The voter confirmation email system now stores custom email addresses in user meta (`wpvp_vote_{id}_notify_emails`) and sends to all specified addresses.

Public results visibility was fixed in both `can_view_results()` (which now returns true for public-visibility votes) and `vote-detail.php` (which no longer requires `$user_id` for result display).

---

## Version 2.8.2 (February 2026)

**Resolved entity titles on vote results and admin menu lockdown.**

### Changes

- Voter list on results page now shows ASC-resolved entity titles (e.g. "John Smith, Kings of New York — HST - Yes")
- Non-anonymous votes: full voter list visible to all users (not just admin)
- Anonymous votes: only unattributed comments visible to non-admins; admin always sees full details
- Consolidated voter list and voter comments into a single unified display method
- New CSS styles for voter entry cards with role labels and indented comments

---

## Version 2.8.1 (February 2026)

**Guide wizard UX upgrades and disciplinary auto-populate fix.**

### Changes

- ✅ **Guide wizard Select2 fields**: All three role fields (Who Can View, Who Can Vote, Additional Viewers) upgraded from plain text inputs to Select2 multi-selects, matching the vote editor UX
- ✅ **Template loader fix**: Role template loader now correctly populates wizard Select2 fields
- ✅ **Disciplinary auto-populate in wizard**: Selecting "Disciplinary" type in the Guide wizard now auto-populates punishment level options (was only working in the vote editor)
- ✅ **Additional viewers in wizard**: Wizard form submission now sends `additional_viewers` data to the backend

### Technical Details

The Guide wizard previously used `<input type="text">` for role fields while the vote editor used Select2 multi-selects. This caused inconsistent UX and prevented the template loader (which targets Select2 elements) from working in the wizard. All three fields now use the same Select2 classes as the vote editor (`.wpvp-select2-roles`, `.wpvp-select2-voting-roles`, `.wpvp-select2-additional-viewers`), so the existing Select2 initialization and template loader code works on both pages. The PHP AJAX handler was updated to accept arrays from Select2 instead of comma-separated strings.

---

## Version 2.8.0 (February 2026)

**Additional vote history viewers with cross-role slug-binding patterns.**

### New Features

- ✅ **Cross-role ballot visibility**: Per-vote setting allows related roles to see how votes were cast under other roles in the same group
- ✅ **Slug-binding patterns**: Patterns like `Chronicle/*/HST` let HSTs see ballots cast by CMs from the same chronicle — the `*` wildcard binds to the matching slug from the voter's role
- ✅ **New vote editor field**: "Additional Vote History Viewers" with Select2 multi-select and role template loader
- ✅ **Guide wizard section**: Additional Vote History Viewers section added to the wizard
- ✅ **Unified Guide layout**: Merged Interactive Vote Builder intro and wizard into a single unified block

### Technical Details

**Slug-binding algorithm**: Given ballot `voting_role` = `Chronicle/Kony/CM` and viewer pattern = `Chronicle/*/HST`:

1. Split both into segments: `['Chronicle','Kony','CM']` and `['Chronicle','*','HST']`
2. For each `*` in the pattern, substitute the same-position segment from the voting_role → `*` at position 1 gets `Kony`
3. Resolved role = `Chronicle/Kony/HST`
4. Check if current user holds `Chronicle/Kony/HST` (exact or child match, case-insensitive)

A user with `Chronicle/DDE/HST` would NOT see `Chronicle/Kony/CM`'s ballot because the slug doesn't match.

New `additional_viewers` column (text, JSON array) added to `wp_wpvp_votes` table. New `WPVP_Permissions::matches_additional_viewers()` method handles the slug-binding logic. Database migration via `upgrade_to_280()`.

---

## Version 2.7.0 (February 2026)

**Optional voter comments and role-based vote history.**

### New Features

- ✅ **Voter comments**: Per-vote admin setting allows voters to add an optional comment/rationale with their ballot
- ✅ **Comment attribution**: Comments displayed with attribution (name + role) on non-anonymous votes, without attribution on anonymous votes
- ✅ **Role-based vote history**: Logged-in users can see votes cast under their current eligible roles in "Your Role's Vote History" section
- ✅ **Role succession**: New role holders can see how the role previously voted, supporting organizational continuity
- ✅ **Personal history**: Users can always view their own vote details regardless of current role eligibility
- ✅ **Admin voter list**: Admins can view full voter list (including comments) on all votes, including anonymous

### Changes

- ✅ Live results and comments now properly gated by the "Show results while voting is open" setting
- ✅ Ballot form includes optional comment textarea (max 1000 characters) when enabled
- ✅ Comment data stored in ballot_data JSON alongside choice and voting_role
- ✅ Vote detail template filters ballots by role match, user match, or both

---

## Version 2.6.0 (February 2026)

**Full internationalization (i18n) support for TranslatePress and other translation plugins.**

### Changes

- ✅ **JavaScript string localization**: All JS-generated strings now pass through WordPress translation functions via `wp_localize_script`
- ✅ **Public UI strings localized**: Modal messages, revote button, loading states, error messages, accessibility labels
- ✅ **Admin UI strings localized**: Option placeholders, disciplinary levels, status messages, confirmation dialogs
- ✅ **TranslatePress compatibility**: Fixes untranslated buttons, select placeholders, and status text

### Technical Details

Two localized data objects (`wpvp` for admin, `wpvp_public` for frontend) now contain `i18n` sub-objects with all translatable strings. JavaScript code references these instead of hardcoded English strings. This enables TranslatePress and similar tools to detect and translate all user-facing text.

---

## Version 2.5.8 (February 2026)

**Fix revote ranking order not restored on RCV/STV/Condorcet.**

### Bug Fixes

- ✅ **Revote ranking restoration**: Fixed revoting on ranked votes (RCV, STV, Condorcet) not restoring previous ranking order
- ✅ **Ballot format unwrapping**: Previous ballot data now correctly unwrapped from new ballot format when pre-populating form

---

## Version 2.5.7 (February 2026)

**Fix RCV and multi-winner ballot submission issues.**

### Bug Fixes

- ✅ **Silent submission failure**: Fixed ballot submission failing silently on RCV, STV, and Condorcet votes for single-role users
- ✅ **Role dropdown visibility**: Role selection dropdown now only requires input when visible (multiple eligible roles)
- ✅ **Single-role fix**: Fixed voting role not being sent for single-role users during ballot submission

---

## Version 2.5.6 (February 2026)

**Improved tie display and repositioned live results.**

### Changes

- ✅ **Tie display**: Options marked as "Tied" instead of "Winner" when there's a tie in singleton votes
- ✅ **Tie banner data**: Added `tied_candidates` to live results `winner_data` for proper tie banner display
- ✅ **Results positioning**: Moved live results to display AFTER ballot form, allowing users to vote before seeing current standings

---

## Version 2.5.5 (February 2026)

**Fix live results rendering.**

### Bug Fixes

- ✅ **Data format fix**: Result data now stored as arrays instead of JSON strings in synthetic results object
- ✅ **Rendering fix**: Live results now properly render vote totals and winner information on open votes

---

## Version 2.5.4 (February 2026)

**Robust error handling for live results.**

### Bug Fixes

- ✅ **Error handling**: Fixed error handling for live results to catch all PHP errors and Throwables, not just Exceptions
- ✅ **Stability**: Prevents fatal errors from breaking vote pages when calculating live results

---

## Version 2.5.3 (February 2026)

**Fix live results calculation.**

### Bug Fixes

- ✅ **Calculation fix**: Fixed live results calculation for open votes
- ✅ **No database writes**: Improved on-the-fly result processing without database saves

---

## Version 2.5.2 (February 2026)

**Live results visibility for open votes.**

### New Features

- ✅ **Live vote totals**: Show current vote totals on open votes before casting ballot
- ✅ **Real-time standings**: Voters can see current standings while voting is open (controlled by per-vote "Show results while voting is open" setting)

---

## Version 2.5.1 (February 2026)

**Enhanced results display and consent agenda improvements.**

### Changes

- ✅ **Detailed voter list**: Enhanced non-anonymous vote results to show display name, role, vote choice, and vote date
- ✅ **Instant consent passage**: Allow consent agenda votes to have simultaneous open/close times for instant passage
- ✅ **Wildcard matching fix**: Fixed wildcard role matching to work with user's personal cached roles
- ✅ **Role filtering**: Fixed role selection to only show roles user actually has

---

## Version 2.5.0 (February 2026)

**Role-based voting attribution for accountability.**

### New Features

- ✅ **Role selection**: Users with multiple eligible roles must select which role they're voting as
- ✅ **Ballot attribution**: Ballot data now stores `voting_role`, `display_name`, and `username` for accountability
- ✅ **Consent display**: Consent voting results display voter attribution as "Display Name (username) role-path"
- ✅ **Data durability**: Attribution data preserved even if user or role is deleted later
- ✅ **Backward compatibility**: Maintains compatibility with existing ballots that lack attribution data

---

## Version 2.4.3 (February 2026)

**Bug fixes for vote creation and editing UX issues.**

### Bug Fixes

- ✅ **Fixed revoting confirmation message**: Votes with revoting enabled now show "Update your vote?" instead of misleading "This cannot be changed" message
- ✅ **Fixed Consent Agenda creation**: Removed silent form validation blocking caused by hidden required fields when creating consent agenda votes
- ✅ **Fixed disciplinary auto-populate prompt**: Auto-populate prompt no longer appears when editing existing disciplinary votes, only when actively switching to disciplinary type

### Technical Details

**Revoting message fix**: Added `data-allow-revote` attribute to ballot container and updated JavaScript to check the vote's revoting setting instead of just presence of revote notice.

**Consent Agenda fix**: When consent type is selected, the voting options section is hidden (since consent votes don't use options). However, hidden option inputs retained their `required` attribute, causing browser HTML5 validation to silently block form submission. The fix removes the `required` attribute when consent or disciplinary type is selected.

**Disciplinary prompt fix**: Added `isUserAction` parameter to `onTypeChange()` function to distinguish between initial page load and user-initiated type changes. Auto-populate only triggers on actual user selection of disciplinary type.

---

## Version 2.4.2 (February 2026)

**Critical security and performance fix for AccessSchema permission checks.**

### Security Fix

- ✅ **Fixed permission bypass vulnerability**: AccessSchema role checks now use cached roles instead of remote API calls
- ✅ **Eliminated WP_Error truthy evaluation**: Strict type checking prevents error objects from being treated as valid permissions
- ✅ **Applies to both visibility and voting eligibility**: All restricted access controls now properly enforce role requirements

### Performance Improvements

- ✅ **Removed remote API calls**: Permission checks now use locally cached role data from user meta
- ✅ **Faster page loads**: Vote list pages load significantly faster by eliminating network latency
- ✅ **Reduced server load**: No external requests for every permission check

### Technical Details

The `check_accessschema()` method now retrieves user roles from the `wpvp_accessschema_cached_roles` user meta field instead of making remote API calls. A new helper method `user_has_cached_role()` performs local role matching with support for hierarchical child roles. Wildcard pattern expansion continues to work as expected, checking expanded paths against cached roles.

This fix ensures that users without required AccessSchema roles cannot vote on or view restricted votes, while maintaining full compatibility with wildcard patterns and hierarchical role matching.

---

## Version 2.4.1 (February 2026)

**Bug fix: Admin notification email setting now saves properly.**

### Changes

- ✅ **Fixed email field saving**: Added `wpvp_admin_notification_email` to settings whitelist and manual save handler
- ✅ **Settings now persist**: Admin notification email field was displayed but not being saved on form submission

### Bug Fix

The admin notification email setting field was registered and displayed in the UI but was missing from both the `whitelist_options()` method and the `handle_save()` method. This meant the setting would appear to save but would not persist to the database. The field is now properly included in both locations with email sanitization.

---

## Version 2.4.0 (February 2026)

**Comprehensive email notification system with voter opt-in and scheduled reminders.**

### New Features

- ✅ **Email to admin when vote opens**: Sends notification to configurable admin email when vote transitions to 'open' stage
- ✅ **Voter confirmation emails with opt-in**: Voters can choose to receive email confirmation each time they cast or update their ballot
  - Per-vote opt-in checkbox on ballot form
  - User preference saved in user meta
  - Email includes ballot details and vote closing date
- ✅ **9am closing day reminder**: Scheduled email sent to admin at 9:00 AM on the day a vote closes
  - Uses WordPress cron (`wp_schedule_single_event`)
  - Includes current ballot count
  - Only sent if vote is still open
- ✅ **Completion notification with results**: Email sent to admin when vote completes with formatted results summary
  - Winner/winners displayed
  - Vote counts and percentages
  - Total votes cast
- ✅ **Admin notification email setting**: New settings field to configure admin email address (defaults to site admin email)

### Changes

- Extended `WPVP_Notifications` class with new email methods
- Added `wpvp_ballot_submitted` action hook for voter confirmation emails
- Added `wpvp_closing_reminder` action hook for scheduled reminders
- Updated ballot form template with opt-in checkbox
- Updated JavaScript to send opt-in preference with ballot submission
- Added `get_admin_notification_email()` helper method with fallback logic
- Enhanced completion notification with formatted results display

### Technical Details

All email notifications respect the global `wpvp_enable_email_notifications` setting. The admin notification email defaults to an empty string and falls back to `get_option('admin_email')` if not set or invalid. Voter opt-in preferences are stored per-vote using user meta with key format `wpvp_vote_{vote_id}_notify`.

---

## Version 2.3.2 (February 2026)

**Database enhancement: Classification system and proposal metadata fields.**

### Changes

- ✅ **Classification system**: Added support for categorizing and organizing votes
- ✅ **Proposal metadata**: Enhanced proposal data storage with additional metadata fields
- ✅ **Automatic upgrade**: Database schema updated via `upgrade_to_231()` method

### Technical Details

The upgrade adds classification-related fields to support better organization and categorization of votes. This upgrade runs automatically when users update from versions prior to 2.3.2.

---

## Version 2.3.0 (February 2026)

**New default pages for improved vote organization.**

### New Features

- ✅ **Open Votes page**: Dedicated page showing only votes with status "open" (limit: 50)
- ✅ **Closed Votes page**: Dedicated page showing votes with status "closed", "completed", or "archived" (limit: 50)
- ✅ **Smart page creation**: Pages are only created if they don't already exist

### Changes

- Added `open-votes` page with shortcode: `[wpvp_votes status="open" limit="50"]`
- Added `closed-votes` page with shortcode: `[wpvp_votes status="closed,completed,archived" limit="50"]`
- Updated activator to support comma-separated status values in shortcode
- Enhanced page creation logic to skip existing pages

### Use Cases

These dedicated pages provide better organization for sites with many active and historical votes. Users can now navigate directly to "Open Votes" to see what's currently active, or "Closed Votes" to review past decisions.

---

## Version 2.2.1 (February 2026)

**UI Enhancement: Rich text editor for Guide description field.**

### Changes

- ✅ **Guide builder Step 1**: Replaced plain textarea with wp_editor for proposal description
- ✅ **Consistent rich text support**: Description field now properly supports bold, italic, links, and lists as documented
- ✅ **Improved user experience**: Teeny mode provides simplified but functional rich text editing

### Bug Fix

The Guide builder instructions stated that "The description field supports rich text (bold, italic, links, lists)" but was only providing a plain textarea. This version adds the wp_editor (WordPress TinyMCE) integration to deliver the promised rich text editing functionality.

---

## Version 2.2.0 (February 2026)

**Independent visibility and voting eligibility controls: Separate who can VIEW from who can VOTE.**

### New Features

- ✅ **Separate visibility and voting eligibility**: Visibility controls who can VIEW a vote; voting eligibility controls who can VOTE
- ✅ **New database fields**: Added `voting_eligibility` (public/private/restricted) and `voting_roles` (JSON array)
- ✅ **Independent permission checks**: New `user_can_view_vote()` and `user_can_vote_on()` functions replace combined `user_passes_vote_access()`
- ✅ **Updated vote editor**: New "Who Can Vote" section in sidebar with voting eligibility dropdown and roles field
- ✅ **Updated Guide builder**: Step 5 now has separate sections for "Who Can View" and "Who Can Vote"
- ✅ **Automatic migration**: Existing votes automatically copy `visibility` → `voting_eligibility` and `allowed_roles` → `voting_roles` on upgrade

### Changes

- Split permission logic into view access vs. voting access
- Updated vote editor form to include voting eligibility controls
- Updated Guide builder Step 5 to separate viewing and voting permissions
- Updated AJAX handler to process new voting eligibility fields
- Added `WPVP_Database::get_voting_eligibility_options()` method
- Updated visibility option labels to clarify they control viewing

### Use Cases

**Example configurations now possible:**
- **Public visibility, restricted voting**: Everyone can see election results, only CMs can cast ballots
- **Private visibility, public voting**: Logged-in users see internal poll, anyone can vote
- **Restricted visibility, different restricted voting**: Coordinators can see proposal, only admins can vote
- **Public visibility, public voting**: Open referendum visible and votable by anyone

### Backward Compatibility

All existing votes are automatically migrated on upgrade. The migration copies existing `visibility` settings to `voting_eligibility` and `allowed_roles` to `voting_roles`, preserving current behavior. After migration, you can customize viewing and voting permissions independently.

---

## Version 2.1.0 (February 2026)

**Interactive Vote Builder: Learn by doing with the new Guide feature.**

### New Features

- ✅ **Interactive vote builder in Guide**: Added "Try It Now" form at the end of the Creating a Vote walkthrough
- ✅ **Inline vote creation**: Users can fill out vote details while reading the tutorial
- ✅ **One-click save**: Submit button creates the vote and redirects to the editor
- ✅ **Form validation**: Client-side and server-side validation with helpful error messages
- ✅ **Dynamic form behavior**: Options and settings automatically show/hide based on voting type and visibility

### Changes

- Enhanced Guide page with collapsible interactive form
- Added AJAX endpoint `wpvp_guide_create_vote` for form submission
- JavaScript form handler with spinner and success/error feedback
- Auto-redirect to vote editor after successful creation

### Use Case

New users can learn about vote creation by reading the step-by-step guide, then immediately practice by creating a test vote using the integrated form - all without leaving the Guide page.

---

## Version 2.0.8 (February 2026)

**Critical bug fix: AJAX handlers now register correctly on all admin requests.**

### Changes

- ✅ **Fixed AJAX handler registration**: Moved Settings class instantiation from page render to Admin constructor
- ✅ **Test connection now works**: AJAX actions are registered on every admin request, not just when viewing settings page
- ✅ **Settings save fixed**: Form submission now properly triggers validation and save logic

### Bug Fix

AJAX handlers (test connection, fetch roles, process closed votes) were only registered when the Settings page was being rendered. On AJAX requests, the Settings class was never instantiated, so WordPress returned 400 errors for all AJAX calls. Moving the Settings instantiation to the Admin class constructor ensures AJAX actions are registered on every admin page load, including AJAX requests themselves.

**Root Cause**: WordPress processes AJAX requests through `admin-ajax.php`, which doesn't render any admin pages. The Settings class was only instantiated inside `render_settings_page()`, so AJAX actions were never registered during AJAX requests. The fix ensures the Settings constructor (where `add_action('wp_ajax_*')` calls live) runs on every admin request.

---

## Version 2.0.7 (February 2026)

**NUCLEAR OPTION: Bypassed options.php entirely with manual save handler.**

### Changes
- ✅ **Manual save handler**: Settings now save directly using `update_option()` instead of going through `options.php`
- ✅ **Custom form processing**: Form submits to itself with nonce verification, bypassing WordPress Settings API completely
- ✅ **No more whitelist errors**: The "options page is not in the allowed options list" error is impossible since we don't use the whitelist anymore
- ✅ **100% multisite compatible**: Direct option updates work identically on single-site and multisite

### Breaking Change
This version **completely abandons the WordPress Settings API** for form processing. While we still use `register_setting()` for sanitization callbacks, the actual save process no longer goes through `options.php`. This is a nuclear option, but the Settings API's multisite whitelist validation was proving impossible to work around.

**Why This Works**: Instead of fighting with WordPress's option whitelist, we handle POST data directly in the `render()` method, validate the nonce, sanitize each field, and call `update_option()` manually. This approach is more explicit, easier to debug, and works identically on single-site and multisite without any whitelist concerns.

---

## Version 2.0.6 (February 2026)

**Fixed filter timing issue that prevented settings from saving on multisite.**

### Changes
- ✅ **Moved filter to constructor**: The `allowed_options` filter is now registered in the constructor with priority 1, ensuring it's available before WordPress checks it
- ✅ **Added dual filter support**: Registered both `allowed_options` (WP 5.5+) and `whitelist_options` (pre-5.5) for maximum compatibility
- ✅ **Proper method implementation**: Created dedicated `whitelist_options()` method instead of anonymous function for better debugging
- ✅ **Fixed missing timezone**: Added `wpvp_timezone` to the advanced options whitelist

### Bug Fix
The v2.0.5 approach of adding the filter inside `register_settings()` (which is hooked to `admin_init`) had a timing issue - the filter needs to be registered earlier in the WordPress lifecycle. By moving it to the constructor and setting priority 1, the whitelist is guaranteed to be in place when WordPress validates the form submission to `options.php`. Additionally, using a named method instead of an anonymous function improves debugging and stack traces.

**Root Cause**: WordPress processes option saves through `options.php`, which checks the `allowed_options` global array very early. Filters added during `admin_init` may not execute early enough to modify this array before the check happens, especially on multisite where the validation is stricter.

---

## Version 2.0.5 (February 2026)

**Critical fix: Settings now save properly on multisite installations.**

### Changes
- ✅ **Multisite settings whitelist**: Added explicit `allowed_options` filter to whitelist all plugin option groups
- ✅ **Permissions tab now saves**: AccessSchema URL, API key, and all permission settings now persist correctly
- ✅ **Test connection works**: With settings saving properly, the connection test button now functions as intended

### Bug Fix
On WordPress multisite, the Settings API was rejecting option saves with "The wpvp_permissions options page is not in the allowed options list" error. While v2.0.2 added settings sections, multisite requires explicit whitelisting via the `allowed_options` filter. All three option groups (wpvp_general, wpvp_permissions, wpvp_advanced) are now properly whitelisted, allowing settings to save on both single-site and multisite installations.

**Technical Detail**: WordPress multisite has stricter validation for option updates. The `register_setting()` calls alone don't guarantee the option group will be in the allowed list. The `allowed_options` filter explicitly adds our option groups and their associated option names to the whitelist checked during form submission to `options.php`.

---

## Version 2.0.4 (February 2026)

**Removed duplicate AccessSchema admin menu to eliminate confusion.**

### Changes
- ✅ **Disabled embedded client admin UI**: The accessSchema client no longer creates a separate "WPVP ASC" menu under Users
- ✅ **Unified settings interface**: All AccessSchema configuration is now exclusively in Voting → Settings → Permissions tab
- ✅ **Cleaner admin experience**: One settings location instead of two conflicting interfaces

### Bug Fix
The embedded accessSchema client was creating its own basic admin page under Users → "WPVP ASC", which duplicated and conflicted with the voting plugin's comprehensive AccessSchema settings in the Permissions tab. The client's page lacked features like connection testing and was causing confusion. By disabling the client's admin UI, users now have a single, complete interface for all AccessSchema configuration.

---

## Version 2.0.3 (February 2026)

**PHP 7.4 compatibility fix for str_contains() usage.**

### Changes
- ✅ **PHP 7.4 compatibility**: Replaced `str_contains()` (PHP 8.0+) with `strpos()` for broader compatibility
- ✅ **Fixes both URL handling functions**: Test connection and permission check URL normalization now work on PHP 7.4+

### Bug Fix
The v2.0.2 release inadvertently used `str_contains()` which is only available in PHP 8.0+, causing silent failures on servers running PHP 7.4-7.9. This version restores compatibility with the plugin's minimum PHP requirement of 7.4.

---

## Version 2.0.2 (February 2026)

**Bug fixes for multisite settings and AccessSchema URL handling.**

### Changes
- ✅ **Settings page fix**: Register required settings sections for WordPress Settings API (required for multisite)
- ✅ **Flexible URL handling**: AccessSchema connection now accepts either base URL or full API URL
  - Base URL format: `https://example.com`
  - Full API URL format: `https://example.com/wp-json/access-schema/v1/`
  - Both formats automatically normalized for consistent API calls

### Technical Details
- Added `add_settings_section()` calls for `wpvp_general`, `wpvp_permissions`, and `wpvp_advanced` option groups
- Updated `accessSchema_client_get_remote_url()` to strip API path if present in URL
- Updated test connection AJAX handler to normalize URLs before building API endpoint

### Bug Fixes
- Fixed "wpvp_permissions options page is not in the allowed options list" error when saving settings on multisite
- Fixed test connection failure when full API URL provided instead of base URL

---

## Version 2.0.1 (February 2026)

**Multisite support enabled with per-site data isolation.**

### Changes
- ✅ **Multisite support**: Plugin now works on WordPress multisite/network installations
- ✅ **Per-site data isolation**: Each site in the network has its own votes, ballots, and results (isolated via `$wpdb->prefix`)
- ✅ **No shared data**: Data is completely isolated per site, no cross-site access

### Technical Details
The database layer already used `$wpdb->prefix` for all table names, which WordPress automatically makes site-specific in multisite environments:
- Site 1: `wp_wpvp_votes`, `wp_wpvp_ballots`, `wp_wpvp_results`
- Site 2: `wp_2_wpvp_votes`, `wp_2_wpvp_ballots`, `wp_2_wpvp_results`
- Site 3: `wp_3_wpvp_votes`, etc.

This ensures complete data isolation between sites with no additional code changes required.

### Migration Notes
If you were unable to use this plugin on multisite before, you can now activate it on individual sites within your network. Each site will have its own isolated voting system.

---

## Version 2.0.0 (February 2026)

**Complete rebuild of the voting plugin with improved architecture, security, and maintainability.**

### Location
- `/wp-voting-plugin/` (current directory)

### Major Changes
- **Complete rewrite**: Built from scratch with modern WordPress standards
- **25 custom PHP files**: Modular architecture with clear separation of concerns
- **Security hardened**: PHPCS clean (0 errors, 0 warnings), all WordPress security standards met
- **6 voting algorithms**: FPTP, RCV, STV, Condorcet, Disciplinary, Consent Agenda
- **AccessSchema integration**: Priority-chain permissions (accessSchema → WP caps)
- **Elementor widgets**: 3 widgets for vote lists, ballots, and results
- **Notification system**: wp_mail() with cron-based auto-open/close
- **Migration tool**: Idempotent v1→v2 data import (class-migration.php)

### Architecture
```
wp-voting-plugin.php                    # Bootstrap
├── includes/
│   ├── class-plugin.php                # Main controller
│   ├── class-activator.php             # Installation/upgrade
│   ├── class-database.php              # Schema management
│   ├── class-permissions.php           # AccessSchema → WP caps
│   ├── class-admin.php                 # Admin UI orchestration
│   ├── class-settings.php              # Settings page
│   ├── class-vote-list.php             # Vote list table
│   ├── class-vote-editor.php           # Vote creation/edit
│   ├── algorithms/                     # 6 algorithm implementations
│   ├── class-public.php                # Frontend orchestration
│   ├── class-ballot.php                # Ballot submission
│   ├── class-results-display.php       # Results rendering
│   ├── class-notifications.php         # Email + cron
│   ├── class-migration.php             # v1→v2 import
│   ├── class-elementor-widgets.php     # Widget registration
│   └── accessschema-client/            # AccessSchema client library (PHPCS clean)
├── templates/                          # Public templates
└── assets/                             # CSS/JS + Select2 vendor
```

### Code Quality
- ✅ PHPCS Clean: 0 errors, 0 warnings (WordPress Coding Standards)
- ✅ Security: Yoda conditions, wp_unslash, nonces, output escaping, wp_safe_redirect
- ✅ WordPress Standards: Follows all WordPress coding and plugin development standards
- ✅ Documentation: PHPDoc blocks for all public functions

### AccessSchema Client (Embedded)
- **Location**: `includes/accessschema-client/`
- **Status**: Production-ready, PHPCS clean
- **Version**: Cleaned and hardened (February 2026)
- **Features**: Remote/local/none modes, user role caching, admin interface

---

## Version 1.x (Legacy - Archived)

**Original implementation (~40% complete, multiple bugs, deprecated).**

### Archive
- **File**: `wp-voting-plugin-v1-legacy-20260209.tar.gz` (267KB)
- **Status**: Archived for reference only, not production-ready

### Known Issues (v1)
- Incomplete implementation (~40% of planned features)
- Multiple bugs in vote processing
- Inconsistent permission checks
- No PHPCS compliance
- Missing security hardening
- No migration path from v1 to v2 (v2 includes migration tool)

### Why v2 Was Built
The v1 codebase had accumulated too much technical debt and incomplete features. Rather than continue patching, a complete rebuild was undertaken to:
1. Implement all 6 voting algorithms correctly
2. Add proper security hardening
3. Integrate with AccessSchema properly
4. Meet WordPress plugin directory standards
5. Provide a migration path from v1

---

## Migration Guide (v1 → v2)

If you have v1 data:
1. Install v2 (this version)
2. Activate the plugin
3. Go to **Voting > Settings > Migration** tab
4. Click **"Run Migration"**
5. The migration is idempotent (safe to run multiple times)

The migration tool (`class-migration.php`) will:
- Import all v1 votes with settings preserved
- Map v1 algorithms to v2 equivalents
- Convert ballot data to v2 format
- Preserve all timestamps and user associations

---

## Support & Development

- **Repository**: https://github.com/One-World-By-Night/wp-voting-plugin
- **Author**: One World By Night
- **License**: GPL-2.0-or-later
- **Requires**: WordPress 5.0+, PHP 7.4+
