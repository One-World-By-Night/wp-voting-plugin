<?php

/**
 * File: includes/helper/migrate-from-owbn.php
 * Migrate from OWBN structure to new three-table structure
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Run the complete migration
 */
function wpvp_run_migration()
{
    global $wpdb;

    // Check if old table exists
    $old_table = $wpdb->prefix . 'voting';
    if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") !== $old_table) {
        return ['success' => false, 'message' => 'Old voting table not found'];
    }

    // Create new tables if they don't exist
    wpvp_create_tables();

    $results = [
        'votes_migrated' => 0,
        'ballots_migrated' => 0,
        'errors' => []
    ];

    // Get all old votes
    $old_votes = $wpdb->get_results("SELECT * FROM $old_table", ARRAY_A);

    foreach ($old_votes as $old_vote) {
        try {
            $new_vote_id = wpvp_migrate_single_vote($old_vote);

            if ($new_vote_id) {
                $results['votes_migrated']++;

                // Migrate ballots
                $ballots_count = wpvp_migrate_vote_ballots($old_vote, $new_vote_id);
                $results['ballots_migrated'] += $ballots_count;

                // Process and save results if vote is completed
                if ($old_vote['voting_stage'] === 'completed') {
                    wpvp_migrate_vote_results($old_vote, $new_vote_id);
                }
            }
        } catch (Exception $e) {
            $results['errors'][] = "Vote {$old_vote['id']}: " . $e->getMessage();
        }
    }

    return $results;
}

/**
 * Migrate a single vote
 */
function wpvp_migrate_single_vote($old_vote)
{
    global $wpdb;

    // Unserialize old data
    $voting_options = @unserialize($old_vote['voting_options']);
    if ($voting_options === false) {
        $voting_options = json_decode($old_vote['voting_options'], true) ?? [];
    }

    // Format options for new structure
    $formatted_options = [];
    foreach ($voting_options as $index => $option) {
        if (is_object($option)) {
            $formatted_options[] = [
                'id' => $index + 1,
                'text' => $option->text ?? '',
                'description' => $option->description ?? ''
            ];
        } else {
            $formatted_options[] = [
                'id' => $index + 1,
                'text' => (string)$option,
                'description' => ''
            ];
        }
    }

    // Prepare new vote data
    $new_data = [
        'proposal_name' => $old_vote['proposal_name'],
        'proposal_description' => $old_vote['content'],
        'voting_type' => $old_vote['voting_choice'] ?? 'single',
        'voting_options' => wp_json_encode($formatted_options),
        'number_of_winners' => $old_vote['number_of_winner'] ?? 1,
        'allowed_roles' => '[]', // Will need manual update based on your AccessSchema setup
        'visibility' => $old_vote['visibility'] ?? 'private',
        'voting_stage' => $old_vote['voting_stage'],
        'created_by' => $old_vote['created_by'],
        'created_at' => $old_vote['create_date'],
        'opening_date' => $old_vote['opening_date'],
        'closing_date' => $old_vote['closing_date'],
        'settings' => wp_json_encode([
            'maximum_choices' => $old_vote['maximum_choices'] ?? null,
            'proposed_by' => $old_vote['proposed_by'] ?? null,
            'seconded_by' => $old_vote['seconded_by'] ?? null,
            'migrated_from_id' => $old_vote['id'],
            'migrated_at' => current_time('mysql')
        ])
    ];

    $wpdb->insert($wpdb->prefix . 'wpvp_votes', $new_data);

    return $wpdb->insert_id;
}

/**
 * Migrate ballots for a vote
 */
function wpvp_migrate_vote_ballots($old_vote, $new_vote_id)
{
    global $wpdb;

    if (empty($old_vote['vote_box'])) {
        return 0;
    }

    // Decode vote box
    $vote_box = json_decode($old_vote['vote_box'], true);
    if (!is_array($vote_box)) {
        return 0;
    }

    $count = 0;
    $voting_type = $old_vote['voting_choice'] ?? 'single';

    foreach ($vote_box as $ballot) {
        // Get user ID from username
        $user = get_user_by('login', $ballot['userName']);
        if (!$user) {
            continue; // Skip if user not found
        }

        // Format ballot data based on voting type
        $ballot_data = $ballot['userVote'];

        // Ensure proper format
        if ($voting_type === 'single' && is_array($ballot_data)) {
            $ballot_data = $ballot_data[0] ?? '';
        }

        $insert_data = [
            'vote_id' => $new_vote_id,
            'user_id' => $user->ID,
            'ballot_data' => wp_json_encode($ballot_data),
            'voted_at' => $ballot['voted_at'] ?? $old_vote['closing_date'] ?? current_time('mysql'),
            'ip_address' => $ballot['ip_address'] ?? '',
            'user_agent' => $ballot['user_agent'] ?? ''
        ];

        $inserted = $wpdb->insert($wpdb->prefix . 'wpvp_ballots', $insert_data);
        if ($inserted) {
            $count++;
        }
    }

    return $count;
}

/**
 * Process and save results for completed votes
 */
function wpvp_migrate_vote_results($old_vote, $new_vote_id)
{
    global $wpdb;

    // Get the migrated ballots
    $ballots = wpvp_get_ballots_for_processing($new_vote_id);

    if (empty($ballots)) {
        return false;
    }

    // Get voting options
    $vote_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d",
        $new_vote_id
    ), ARRAY_A);

    $voting_options = json_decode($vote_data['voting_options'], true);

    // Process votes based on type
    $results = wpvp_process_votes([
        'voting_choice' => $vote_data['voting_type'],
        'number_of_winner' => $vote_data['number_of_winners']
    ], $ballots, $voting_options);

    // Save results
    return wpvp_save_vote_results($new_vote_id, $results);
}

/**
 * Create admin page for migration
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'WPVP Migration',
        'WPVP Migration',
        'manage_options',
        'wpvp-migration',
        'wpvp_migration_page'
    );
});

/**
 * Migration admin page
 */
function wpvp_migration_page()
{
?>
    <div class="wrap">
        <h1>WP Voting Plugin Migration</h1>

        <?php if (isset($_POST['run_migration']) && check_admin_referer('wpvp_migration')): ?>
            <?php $results = wpvp_run_migration(); ?>

            <div class="notice notice-<?php echo empty($results['errors']) ? 'success' : 'warning'; ?>">
                <p><strong>Migration Complete!</strong></p>
                <ul>
                    <li>Votes migrated: <?php echo $results['votes_migrated']; ?></li>
                    <li>Ballots migrated: <?php echo $results['ballots_migrated']; ?></li>
                </ul>

                <?php if (!empty($results['errors'])): ?>
                    <p><strong>Errors:</strong></p>
                    <ul>
                        <?php foreach ($results['errors'] as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('wpvp_migration'); ?>

            <h2>Migrate from OWBN Structure</h2>
            <p>This will migrate your votes from the old OWBN plugin structure to the new three-table structure.</p>

            <table class="form-table">
                <tr>
                    <th>Old Table</th>
                    <td><?php echo $GLOBALS['wpdb']->prefix; ?>voting</td>
                </tr>
                <tr>
                    <th>New Tables</th>
                    <td>
                        <?php echo $GLOBALS['wpdb']->prefix; ?>wpvp_votes<br>
                        <?php echo $GLOBALS['wpdb']->prefix; ?>wpvp_ballots<br>
                        <?php echo $GLOBALS['wpdb']->prefix; ?>wpvp_results
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="run_migration" class="button-primary"
                    value="Run Migration"
                    onclick="return confirm('This will migrate all votes. Continue?');">
            </p>
        </form>

        <h2>Post-Migration Steps</h2>
        <ol>
            <li>Update AccessSchema roles for each vote</li>
            <li>Verify results for completed votes</li>
            <li>Test new voting functionality</li>
            <li>Keep old table as backup until verified</li>
        </ol>
    </div>
<?php
}
