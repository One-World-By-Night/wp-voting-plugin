# VOTE-REMEDIATION.md — wp-voting-plugin Bug Fix Plan

**Date**: 2026-02-25
**Plugin version**: 3.10.1 (active-network on council.owbn.net)
**Repo**: `/Users/greghacke/Development/One-World-by-Night/wp-voting-plugin/wp-voting-plugin/`

---

## Issues Overview (Priority Order)

| # | Issue | Severity | Files Affected |
|---|-------|----------|----------------|
| 1 | Results page missing vote description | Medium | `templates/public/results.php` |
| 2 | Email notifications not sending (open + reminder) | High | `class-notifications.php`, `class-vote-editor.php` |
| 3 | Closed vote email should include full HTML results | Medium | `class-notifications.php` (new method) |
| 4 | Consent agenda says passed AND failed simultaneously | High | `class-database.php` (`save_results`) |
| 5 | Voter list exposes user identity; position slug not tracked | High | `class-results-display.php`, `class-ballot.php`, `class-database.php` |

---

## Issue 1: Results Page Missing Vote Description

### Root Cause

The `[wpvp_results]` shortcode renders `templates/public/results.php`, which shows `$vote->proposal_name` as the title but **never renders `$vote->proposal_description`**.

Compare with `templates/public/vote-detail.php` (lines 59-63) which does:
```php
<?php if ( ! empty( $vote->proposal_description ) ) : ?>
    <div class="wpvp-vote-detail__description">
        <?php echo wp_kses_post( wpautop( $vote->proposal_description ) ); ?>
    </div>
<?php endif; ?>
```

The email links to `vote-results` page (see `class-notifications.php` line 299-301), which uses the results shortcode — so the user clicks a "vote completed" email link and lands on a page with the title but no description.

### Fix

**File**: `templates/public/results.php`

Add the proposal description block after the title/badge section (after line 29, before the live results notice):

```php
<?php if ( ! empty( $vote->proposal_description ) ) : ?>
    <div class="wpvp-results-wrap__description">
        <?php echo wp_kses_post( wpautop( $vote->proposal_description ) ); ?>
    </div>
<?php endif; ?>
```

Also add the vote metadata (type, opens/closes dates) matching the vote-detail template style, and proposal metadata (proposed by, seconded by, classification) if populated.

### Scope
- [x] Add description to results template
- [x] Add vote metadata (type, dates, classification, proposed_by, seconded_by) to results template
- [x] Add matching CSS in `assets/css/public.css`

---

## Issue 2: Email Notifications Not Sending

### Root Causes (Three Separate Bugs)

#### Bug 2a: New vote creation doesn't fire stage change action

**File**: `class-vote-editor.php`, `process_form()` method

When creating a **new** vote (lines 92-98), the code calls `WPVP_Database::save_vote()` and immediately redirects. It never fires `wpvp_vote_stage_changed`. So a vote created directly as "open" with a future `opening_date` never schedules a deferred open notification, and a vote created as "open" with a past/current `opening_date` never sends the open notification.

The stage change action only fires when **updating** an existing vote (lines 86-88):
```php
if ( $old_stage && $old_stage !== $data['voting_stage'] ) {
    do_action( 'wpvp_vote_stage_changed', $this->vote_id, $data['voting_stage'], $old_stage );
}
```

**Fix**: After successful new vote creation, fire the action if the new stage isn't 'draft':
```php
$new_id = WPVP_Database::save_vote( $data );
if ( $new_id ) {
    if ( 'draft' !== $data['voting_stage'] ) {
        do_action( 'wpvp_vote_stage_changed', $new_id, $data['voting_stage'], 'draft' );
    }
    wp_safe_redirect( ... );
    exit;
}
```

#### Bug 2b: Closing reminder scheduled at wrong time (9am on closing day)

**File**: `class-notifications.php`, `schedule_closing_reminder()` method (lines 645-664)

Current code:
```php
$close_timestamp = strtotime( $vote->closing_date );
$close_date_only = gmdate( 'Y-m-d', $close_timestamp );
$reminder_time   = strtotime( $close_date_only . ' 09:00:00' );
```

This schedules the reminder at **9am UTC on the closing date**. For consent votes that close at midnight (00:00:00), the reminder fires **9 hours after the vote already closed**. The handler then exits because the vote is no longer in 'open' stage.

**Confirmed on server**: Vote 28 closes 2026-02-26 00:00:00. Reminder scheduled for 2026-02-26 09:00:00 — 9 hours too late.

**Requirement**: Reminder should go out at **9am ET on the day the vote closes** (beginning of the business day), with a guaranteed minimum **15-hour window** before close. If the vote closes before midnight (i.e., less than 15 hours from 9am that day), send the reminder the **evening before** instead.

**Fix**: Smart reminder scheduling with 15-hour minimum window:
```php
$close_timestamp = strtotime( $vote->closing_date );

// Target: 9am ET on closing day (14:00 UTC during EST, 13:00 UTC during EDT)
$et = new DateTimeZone( 'America/New_York' );
$close_dt = new DateTime( $vote->closing_date, new DateTimeZone( 'UTC' ) );
$close_dt->setTimezone( $et );
$close_day_9am = clone $close_dt;
$close_day_9am->setTime( 9, 0, 0 );

// If less than 15 hours between 9am and close, send evening before (6pm ET day before)
$hours_until_close = ( $close_timestamp - $close_day_9am->getTimestamp() ) / 3600;
if ( $hours_until_close < 15 ) {
    $close_day_9am->modify( '-1 day' );
    $close_day_9am->setTime( 18, 0, 0 ); // 6pm ET evening before
}

$reminder_time = $close_day_9am->getTimestamp();
```

#### Bug 2c: Open notification fires late (36 minutes after midnight)

Notification should fire **simultaneously with the opening_date**. Current behavior: last night emails went out at 00:36 even though votes opened at 00:00. This is a WP-Cron latency issue — `wp_schedule_single_event` depends on a page visit to trigger. The `wpvp_daily_cron` runs hourly, so if the last page visit was at 23:30, the deferred event wouldn't fire until the next cron trigger.

**Fix**: Two-pronged approach:
1. **Keep the deferred scheduling** at the exact `opening_date` timestamp (this is correct)
2. **Add the open notification check to `auto_open_votes()`** — when the hourly cron detects a vote that should be open, it already transitions draft→open and fires `wpvp_vote_stage_changed`. But for votes already set to 'open' by admin (with a past opening_date), also check if the open notification was sent:
   - Add a vote meta flag `_wpvp_open_notification_sent` set when the notification fires
   - In `auto_open_votes()`, after opening a vote, this flag gets set via the normal notification flow
   - Add a secondary check: scan 'open' votes whose `opening_date` has passed and whose flag is not set — send the notification and set the flag
3. **Note**: The 36-minute delay is inherent to WP-Cron's pseudo-cron model. SiteGround server-side cron triggers WP-Cron periodically but not every minute. For exact-midnight delivery, we'd need a real server cron entry (`* * * * * curl council.owbn.net/wp-cron.php`). This is a server config item, not a code fix.

### Scope
- [x] Fire `wpvp_vote_stage_changed` on new vote creation (Bug 2a)
- [x] Change closing reminder to smart ET-aware scheduling with 15hr minimum (Bug 2b)
- [x] Add missed-notification catch-up in cron for open votes (Bug 2c)
- [x] Add `_wpvp_open_notification_sent` meta flag to prevent duplicate sends
- [ ] Clear stale scheduled events for votes that have already closed
- [ ] Consider server-side cron for tighter timing (operational, not code)

---

## Issue 3: Vote Closed Email Should Include Full Results

### Root Cause

`send_vote_closed_notification()` calls `format_results_summary()` which produces a basic plain-text summary with just the winner and vote counts. The user wants a rich HTML email matching the web page display.

### Required Content (per user spec)

The closed vote email should include:
1. **The proposal itself** — title + description
2. **The options** — list of all voting options
3. **The winner** — winning option/candidate
4. **Who voted** — list of chronicles that voted (chronicle title only, NOT the voter's name)
5. **Who didn't vote** — list of chronicles that did NOT vote (chronicle title only, NOT the voter's name)

### Fix

**File**: `class-notifications.php`

1. **Switch to HTML email**: Set `Content-Type: text/html` header in `send_bulk_email()` when sending closed vote notifications.

2. **New method `build_closed_vote_html()`**: Generates the full HTML email body:
   - Styled HTML template (inline CSS for email client compatibility)
   - Proposal title and description (wp_kses_post)
   - Voting options list
   - Winner/outcome banner
   - For consent: "Passed by Consent" or "Objected" status
   - Voted chronicles list (resolve `entity_slug` → entity title via `owc_resolve_asc_path`)
   - Not-voted chronicles list (same approach as participation tracker)
   - Link to full results page

3. **Helper `get_voted_entities()`**: Query ballots for a vote, extract `entity_type`/`entity_slug` from the ballot rows, resolve to entity titles. Return chronicle titles only (not voter names).

4. **Helper `get_not_voted_entities()`**: Reuse logic from `render_participation_tracker()` — query users by role patterns, diff against voted user IDs, resolve to entity titles.

### Scope
- [x] Add `build_closed_vote_html()` method
- [x] Add `get_voted_entities()` helper
- [x] Add `get_not_voted_entities()` helper
- [x] Modify `send_vote_closed_notification()` to use HTML builder
- [x] Modify `send_bulk_email()` to accept content-type parameter
- [ ] Test with actual vote data on server

---

## Issue 4: Consent Agenda Says Passed AND Failed Simultaneously

### Root Cause

**File**: `class-database.php`, `save_results()` method (lines 997-1003)

When saving results, `final_results` is constructed as:
```php
wp_json_encode(
    array(
        'vote_counts' => $results['vote_counts'] ?? array(),
        'percentages' => $results['percentages'] ?? array(),
        'rankings'    => $results['rankings'] ?? array(),
    )
)
```

The consent algorithm (`class-consent.php`) returns `passed`, `objectors`, and `objection_count` in its result array, but `save_results()` **drops all three** when building `final_results`. Only `vote_counts`, `percentages`, and `rankings` are persisted.

**Confirmed on server**: Vote #27 `final_results`:
```json
{"vote_counts":{"Passed":1,"Objected":0},"percentages":[],"rankings":[]}
```
Missing: `passed`, `objectors`, `objection_count`.

When `render_consent()` reads the results:
```php
$passed = $final['passed'] ?? false;  // Always false — key doesn't exist!
```

So the **winner banner** (from `winner_data`) shows "Result: Passed" but the **consent detail section** (from `final_results`) shows "Did Not Pass" because `passed` defaults to `false`.

The user sees BOTH messages simultaneously — passed AND failed.

### Fix

**File**: `class-database.php`, `save_results()` method

Include consent-specific fields in `final_results`:
```php
wp_json_encode(
    array(
        'vote_counts'     => $results['vote_counts'] ?? array(),
        'percentages'     => $results['percentages'] ?? array(),
        'rankings'        => $results['rankings'] ?? array(),
        'passed'          => $results['passed'] ?? null,
        'objectors'       => $results['objectors'] ?? null,
        'objection_count' => $results['objection_count'] ?? null,
    )
)
```

Using `null` means the keys only appear for consent votes (where the algorithm sets them). Non-consent algorithms don't return these keys, so they'll serialize as `null` and be ignored by non-consent renderers.

After deploying the code fix, reprocess existing completed consent votes to fix their stored results:
```sql
-- No data loss: reprocessing recalculates from existing ballots
```
Or call `WPVP_Processor::process($vote_id)` for each affected vote via WP-CLI.

### Scope
- [x] Add `passed`, `objectors`, `objection_count` to `final_results` in `save_results()`
- [x] Reprocess existing completed consent votes after deploy
- [x] Verify vote #27 displays correctly after fix — `"passed":true` confirmed

---

## Issue 5: Votes Should Never List the User Who Cast the Vote

### Root Cause (Two Sub-Issues)

#### 5a: Voter list shows user display_name as fallback

**File**: `class-results-display.php`, `render_voter_list()` method (lines 836-843)

```php
<?php if ( $entry['role_label'] ) : ?>
    <span class="wpvp-voter-entry__role"><?php echo wp_kses( ... ); ?></span>
<?php else : ?>
    <span class="wpvp-voter-entry__name"><?php echo esc_html( $entry['display_name'] ); ?></span>
<?php endif; ?>
```

When `role_label` is empty (e.g., for admin bypass votes, or roles that can't be resolved), the fallback shows the **user's actual display name**, which the user says should never happen.

#### 5b: Participation tracker shows user names, not entity titles

**File**: `class-results-display.php`, `render_participation_tracker()` method

Both the Voted and Not Voted columns show `$user->display_name` / `$wp_user->display_name` — the actual person who voted. Should show **entity title only** (the chronicle or coordinator name they represent), never the person. No role suffix needed — for chronicles it's always CM, for coordinators it's always the coordinator. Just the entity title.

#### 5c: Position slug tracking for role departure

The ballot already stores `voting_role` in `ballot_data` JSON and `entity_type`/`entity_slug` as indexed columns. The `display_name` and `username` are also stored in the ballot payload. This means:

- The `voting_role` path (e.g., `chronicle/kony/cm`) is durably recorded at ballot-time
- The `username` is stored so the user can look up all their own past votes in the future
- Even if the user leaves their role, the ballot preserves what position they held when they voted

The user confirms: **username must remain stored** in the ballot so users can find their own vote history. The privacy concern is only about the **display of results** — results pages and emails should show entity titles, never user identity.

### Fix

#### 5a Fix: Remove user identity from voter list

**File**: `class-results-display.php`, `render_voter_list()` method

- Remove the `display_name` fallback entirely
- Show only the entity title (resolved from `voting_role` path via `owc_resolve_asc_path()`)
- When resolution fails, show the entity slug from the path (e.g., "Kony" from `chronicle/kony/cm`)
- Remove `get_userdata()` lookup for display purposes (keep only for internal user-matching logic)

```php
// Before (shows user name as fallback):
$display_name = $current_user ? $current_user->display_name : ...;

// After (entity title only, no role suffix — it's always CM or Coordinator):
$entity_title = self::resolve_entity_title( $voting_role );
// Fallback: extract slug from path if resolution unavailable
if ( empty( $entity_title ) && ! empty( $voting_role ) ) {
    $entity_title = self::extract_role_group( $voting_role );
}
```

#### 5b Fix: Participation tracker shows entity titles only

**File**: `class-results-display.php`, `render_participation_tracker()` method

- In both Voted and Not Voted columns: show **entity title only** (the chronicle or coordinator name)
- No role suffix needed — for chronicles it's always CM, for coordinators it's always the coordinator
- Resolve via `owc_resolve_asc_path( $type . '/' . $slug, 'title', false )` (already used in `build_group_key()`)
- Group headers already show entity titles; the individual entries under each group should also be entity titles (not user names)
- For the individual items within each group: since each group IS an entity, the items become redundant — consider flattening to just the group headers with a count, or listing only groups without individual names

#### 5c Fix: Data model is already correct — display-only change

The ballot stores `voting_role`, `display_name`, `username`, `entity_type`, and `entity_slug` at ballot-time. The `username` remains stored so users can find their own vote history in the future. No schema changes needed.

The fix is purely in the rendering layer:
1. `render_voter_list()`: show entity title from `voting_role`, never user identity
2. `render_participation_tracker()`: show entity title, never user identity
3. The stored `voting_role` path serves as the durable record of what position the voter held
4. `username` in ballot_data ensures users can always find their own past votes

### Scope

- [x] Remove user display_name from voter list rendering — show entity title only
- [x] Replace user display_name in participation tracker with entity title only
- [x] Remove `get_userdata()` calls used for identity display in results
- [ ] Verify entity title resolution when owbn-client available and fallback when not
- [x] Confirm `username` remains stored in ballot_data for user's own vote history lookup
- [ ] Test with vote data on server

---

## Implementation Order

1. **Issue 4** (Consent pass/fail) — quickest fix, highest confidence, prevents user confusion
2. **Issue 1** (Results description) — simple template change
3. **Issue 5** (Voter identity) — privacy fix, changes visible on all results pages
4. **Issue 2** (Email notifications) — three sub-bugs, needs careful cron testing
5. **Issue 3** (HTML results email) — largest scope, depends on Issues 4 and 5 being fixed first

## Testing Rules

**CRITICAL**: During all local and remote testing, emails must ONLY be sent to `web@owbn.net`. All test notification recipients, test ballot confirmations, and test closed-vote emails must be hardcoded or overridden to `web@owbn.net`. No production email addresses should receive test emails.

## Deploy Plan

1. Fix locally, test on local MAMP if possible (emails to `web@owbn.net` only)
2. Deploy to council.owbn.net via zip/scp/unzip pattern
3. Test email sends on server (emails to `web@owbn.net` only)
4. Reprocess completed consent votes via WP-CLI after deploy
5. Clear stale cron events and let new scheduling take effect
6. Bump plugin version
7. Remove test email override once all fixes verified

## Files Changed Summary

| File | Issues |
|------|--------|
| `templates/public/results.php` | 1 |
| `includes/class-notifications.php` | 2, 3 |
| `includes/admin/class-vote-editor.php` | 2 |
| `includes/class-database.php` | 4 |
| `includes/public/class-results-display.php` | 5 |
| `assets/css/public.css` | 1 |
