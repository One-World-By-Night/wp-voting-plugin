<?php

/**
 * File: includes/vote-processing/results-save.php
 * Save results using three-table strategy
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Save vote results to the results table
 * @param int $vote_id Vote ID
 * @param array $results Processed results from vote processing
 * @return bool Success status
 */
function wpvp_save_vote_results($vote_id, $results)
{
    global $wpdb;

    $start_time = microtime(true);

    // Get total unique voters
    $total_voters = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}wpvp_ballots WHERE vote_id = %d",
        $vote_id
    ));

    // Prepare data for storage
    $insert_data = [
        'vote_id' => $vote_id,
        'total_votes' => $results['total_votes'] ?? 0,
        'total_voters' => $total_voters,

        // Final results
        'final_results' => wp_json_encode([
            'vote_counts' => $results['vote_counts'] ?? $results['final_vote_counts'] ?? [],
            'percentages' => $results['percentages'] ?? [],
            'rankings' => wpvp_calculate_rankings($results)
        ]),

        // Winner information
        'winner_data' => wp_json_encode([
            'winner' => $results['winner'] ?? null,
            'winner_votes' => $results['winner_votes'] ?? 0,
            'winners' => $results['winners'] ?? [], // For STV multiple winners
            'tie' => $results['tie'] ?? false,
            'tied_candidates' => $results['tied_candidates'] ?? []
        ]),

        // Round-by-round data (for IRV/STV)
        'rounds_data' => wp_json_encode($results['rounds'] ?? []),

        // Additional statistics
        'statistics' => wp_json_encode([
            'voting_type' => $results['voting_choice'] ?? 'single',
            'eliminated_order' => $results['eliminated_candidates'] ?? [],
            'event_log' => $results['event_log'] ?? [],
            'quota' => $results['quota'] ?? null, // For STV
            'majority_threshold' => $results['majority_threshold'] ?? null // For IRV
        ]),

        'calculated_at' => current_time('mysql'),
        'calculation_time' => microtime(true) - $start_time,
        'validation_status' => 'pending',
        'validation_notes' => ''
    ];

    // Validate results if validation data exists
    if (!empty($results['validation'])) {
        $insert_data['validation_status'] = $results['validation']['is_valid'] ? 'valid' : 'invalid';
        $insert_data['validation_notes'] = wp_json_encode($results['validation']);
    }

    // Use INSERT ... ON DUPLICATE KEY UPDATE for idempotency
    $query = $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}wpvp_results 
        (vote_id, total_votes, total_voters, final_results, winner_data, rounds_data, 
         statistics, calculated_at, calculation_time, validation_status, validation_notes)
        VALUES (%d, %d, %d, %s, %s, %s, %s, %s, %f, %s, %s)
        ON DUPLICATE KEY UPDATE
        total_votes = VALUES(total_votes),
        total_voters = VALUES(total_voters),
        final_results = VALUES(final_results),
        winner_data = VALUES(winner_data),
        rounds_data = VALUES(rounds_data),
        statistics = VALUES(statistics),
        calculated_at = VALUES(calculated_at),
        calculation_time = VALUES(calculation_time),
        validation_status = VALUES(validation_status),
        validation_notes = VALUES(validation_notes)",
        $vote_id,
        $insert_data['total_votes'],
        $insert_data['total_voters'],
        $insert_data['final_results'],
        $insert_data['winner_data'],
        $insert_data['rounds_data'],
        $insert_data['statistics'],
        $insert_data['calculated_at'],
        $insert_data['calculation_time'],
        $insert_data['validation_status'],
        $insert_data['validation_notes']
    );

    $result = $wpdb->query($query);

    if ($result === false) {
        error_log('Failed to save vote results for vote ID: ' . $vote_id . ' Error: ' . $wpdb->last_error);
        return false;
    }

    // Update vote status to completed
    $wpdb->update(
        $wpdb->prefix . 'wpvp_votes',
        ['voting_stage' => 'completed'],
        ['id' => $vote_id],
        ['%s'],
        ['%d']
    );

    // Clear cache
    wp_cache_delete('vote_results_' . $vote_id, 'wpvp');
    wp_cache_delete('vote_data_' . $vote_id, 'wpvp');

    return true;
}

/**
 * Calculate rankings from vote counts
 * @param array $results Processed results
 * @return array Rankings
 */
function wpvp_calculate_rankings($results)
{
    $vote_counts = $results['vote_counts'] ?? $results['final_vote_counts'] ?? [];

    if (empty($vote_counts)) {
        return [];
    }

    // Sort by votes descending
    arsort($vote_counts);

    $rankings = [];
    $rank = 1;
    $previous_count = null;
    $skip = 0;

    foreach ($vote_counts as $option => $count) {
        if ($previous_count !== null && $count < $previous_count) {
            $rank += $skip;
            $skip = 0;
        }

        $rankings[$rank][] = $option;
        $skip++;
        $previous_count = $count;
    }

    return $rankings;
}

/**
 * Get saved vote results
 * @param int $vote_id Vote ID
 * @return array|false Results or false if not found
 */
function wpvp_get_saved_results($vote_id)
{
    global $wpdb;

    // Check cache first
    $cached = wp_cache_get('vote_results_' . $vote_id, 'wpvp');
    if ($cached !== false) {
        return $cached;
    }

    // Get from database
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpvp_results WHERE vote_id = %d",
        $vote_id
    ), ARRAY_A);

    if (!$row) {
        return false;
    }

    // Decode JSON fields
    $results = [
        'vote_id' => $row['vote_id'],
        'total_votes' => $row['total_votes'],
        'total_voters' => $row['total_voters'],
        'final_results' => json_decode($row['final_results'], true),
        'winner_data' => json_decode($row['winner_data'], true),
        'rounds_data' => json_decode($row['rounds_data'], true),
        'statistics' => json_decode($row['statistics'], true),
        'calculated_at' => $row['calculated_at'],
        'calculation_time' => $row['calculation_time'],
        'validation_status' => $row['validation_status'],
        'validation_notes' => json_decode($row['validation_notes'], true)
    ];

    // Extract commonly used fields for backward compatibility
    $results['winner'] = $results['winner_data']['winner'] ?? null;
    $results['winner_votes'] = $results['winner_data']['winner_votes'] ?? 0;
    $results['vote_counts'] = $results['final_results']['vote_counts'] ?? [];
    $results['percentages'] = $results['final_results']['percentages'] ?? [];
    $results['rounds'] = $results['rounds_data'] ?? [];

    // Cache for 1 hour
    wp_cache_set('vote_results_' . $vote_id, $results, 'wpvp', HOUR_IN_SECONDS);

    return $results;
}

/**
 * Export results to CSV
 * @param int $vote_id Vote ID
 * @return string CSV content
 */
function wpvp_export_results_csv($vote_id)
{
    global $wpdb;

    // Get vote data
    $vote = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpvp_votes WHERE id = %d",
        $vote_id
    ), ARRAY_A);

    if (!$vote) {
        return '';
    }

    // Get results
    $results = wpvp_get_saved_results($vote_id);
    if (!$results) {
        return '';
    }

    $csv = [];

    // Header information
    $csv[] = ['Vote Results Export'];
    $csv[] = ['Vote Name', $vote['proposal_name']];
    $csv[] = ['Total Votes', $results['total_votes']];
    $csv[] = ['Total Voters', $results['total_voters']];
    $csv[] = ['Calculated At', $results['calculated_at']];
    $csv[] = [];

    // Results
    $csv[] = ['Option', 'Votes', 'Percentage', 'Rank'];

    $rankings = $results['final_results']['rankings'] ?? [];
    $flat_rankings = [];
    foreach ($rankings as $rank => $options) {
        foreach ($options as $option) {
            $flat_rankings[$option] = $rank;
        }
    }

    foreach ($results['vote_counts'] as $option => $count) {
        $csv[] = [
            $option,
            $count,
            ($results['percentages'][$option] ?? 0) . '%',
            $flat_rankings[$option] ?? '-'
        ];
    }

    // Convert to CSV string
    $output = '';
    foreach ($csv as $row) {
        $output .= implode(',', array_map('wpvp_csv_escape', $row)) . "\n";
    }

    return $output;
}

/**
 * Escape CSV values
 * @param string $value Value to escape
 * @return string Escaped value
 */
function wpvp_csv_escape($value)
{
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
}

/**
 * Delete results for a vote
 * @param int $vote_id Vote ID
 * @return bool Success
 */
function wpvp_delete_results($vote_id)
{
    global $wpdb;

    $deleted = $wpdb->delete(
        $wpdb->prefix . 'wpvp_results',
        ['vote_id' => $vote_id],
        ['%d']
    );

    // Clear cache
    wp_cache_delete('vote_results_' . $vote_id, 'wpvp');

    return $deleted !== false;
}

/**
 * Get results summary for multiple votes
 * @param array $vote_ids Array of vote IDs
 * @return array Summary data
 */
function wpvp_get_results_summary($vote_ids)
{
    global $wpdb;

    if (empty($vote_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($vote_ids), '%d'));

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, v.proposal_name, v.voting_type 
        FROM {$wpdb->prefix}wpvp_results r
        JOIN {$wpdb->prefix}wpvp_votes v ON r.vote_id = v.id
        WHERE r.vote_id IN ($placeholders)
        ORDER BY r.calculated_at DESC",
        ...$vote_ids
    ), ARRAY_A);

    $summary = [];

    foreach ($results as $row) {
        $winner_data = json_decode($row['winner_data'], true);

        $summary[] = [
            'vote_id' => $row['vote_id'],
            'proposal_name' => $row['proposal_name'],
            'voting_type' => $row['voting_type'],
            'total_votes' => $row['total_votes'],
            'total_voters' => $row['total_voters'],
            'winner' => $winner_data['winner'] ?? 'No winner',
            'calculated_at' => $row['calculated_at'],
            'validation_status' => $row['validation_status']
        ];
    }

    return $summary;
}
