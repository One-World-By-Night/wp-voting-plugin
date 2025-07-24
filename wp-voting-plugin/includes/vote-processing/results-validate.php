<?php

/**
 * File: includes/vote-processing/results-validate.php
 * Validate vote results and data integrity
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Validate vote results
 * @param array $results Processed results
 * @param array $vote_box_array Original votes
 * @param string $voting_type Type of voting
 * @return array Validation results
 */
function wpvp_validate_results($results, $vote_box_array, $voting_type)
{
    $validation = [
        'is_valid' => true,
        'errors' => [],
        'warnings' => []
    ];

    // Basic validation
    if (empty($results)) {
        $validation['is_valid'] = false;
        $validation['errors'][] = 'Results array is empty';
        return $validation;
    }

    // Validate total votes match
    $original_vote_count = count($vote_box_array);
    $results_total_votes = $results['total_votes'] ?? 0;

    if ($original_vote_count !== $results_total_votes) {
        $validation['is_valid'] = false;
        $validation['errors'][] = sprintf(
            'Vote count mismatch: Original %d, Results %d',
            $original_vote_count,
            $results_total_votes
        );
    }

    // Type-specific validation
    switch ($voting_type) {
        case 'single':
            $validation = wpvp_validate_singleton_results($results, $validation);
            break;

        case 'irv':
            $validation = wpvp_validate_irv_results($results, $validation);
            break;

        case 'stv':
            $validation = wpvp_validate_stv_results($results, $validation);
            break;
    }

    return $validation;
}

/**
 * Validate singleton voting results
 * @param array $results Results to validate
 * @param array $validation Current validation state
 * @return array Updated validation
 */
function wpvp_validate_singleton_results($results, $validation)
{
    // Check vote counts exist
    if (empty($results['vote_counts'])) {
        $validation['is_valid'] = false;
        $validation['errors'][] = 'No vote counts in results';
        return $validation;
    }

    // Verify vote sum
    $total_counted = array_sum($results['vote_counts']);
    if ($total_counted !== $results['total_votes']) {
        $validation['is_valid'] = false;
        $validation['errors'][] = sprintf(
            'Vote sum mismatch: Counted %d, Expected %d',
            $total_counted,
            $results['total_votes']
        );
    }

    // Check percentages
    if (!empty($results['percentages'])) {
        $total_percentage = array_sum($results['percentages']);
        if (abs($total_percentage - 100) > 1) { // Allow 1% rounding error
            $validation['warnings'][] = sprintf(
                'Percentage sum is %.2f%% (expected ~100%%)',
                $total_percentage
            );
        }
    }

    // Validate winner
    if ($results['total_votes'] > 0 && empty($results['winner']) && empty($results['tie'])) {
        $validation['warnings'][] = 'No winner determined despite having votes';
    }

    return $validation;
}

/**
 * Validate IRV results
 * @param array $results Results to validate
 * @param array $validation Current validation state
 * @return array Updated validation
 */
function wpvp_validate_irv_results($results, $validation)
{
    // Check rounds exist
    if (empty($results['rounds'])) {
        $validation['is_valid'] = false;
        $validation['errors'][] = 'No rounds data in IRV results';
        return $validation;
    }

    // Validate round progression
    $previous_total = null;
    foreach ($results['rounds'] as $index => $round) {
        $round_total = array_sum($round['vote_counts']);

        // First round should have all votes
        if ($index === 0 && $round_total !== $results['total_votes']) {
            $validation['errors'][] = sprintf(
                'Round 1 vote count (%d) differs from total votes (%d)',
                $round_total,
                $results['total_votes']
            );
        }

        // Vote totals should not increase between rounds
        if ($previous_total !== null && $round_total > $previous_total) {
            $validation['errors'][] = sprintf(
                'Vote count increased from round %d to %d',
                $index,
                $index + 1
            );
        }

        $previous_total = $round_total;
    }

    // Validate winner has majority
    if ($results['winner'] && $results['winner_votes']) {
        $majority = floor($results['total_votes'] / 2) + 1;
        if ($results['winner_votes'] < $majority && empty($results['tie'])) {
            $validation['warnings'][] = sprintf(
                'Winner has %d votes but majority is %d',
                $results['winner_votes'],
                $majority
            );
        }
    }

    return $validation;
}

/**
 * Validate STV results
 * @param array $results Results to validate
 * @param array $validation Current validation state
 * @return array Updated validation
 */
function wpvp_validate_stv_results($results, $validation)
{
    // Check for quota
    if (empty($results['quota'])) {
        $validation['errors'][] = 'No quota calculated for STV';
    }

    // Validate winners count
    if (isset($results['num_seats'])) {
        $winners_count = count($results['winners'] ?? []);
        if ($winners_count > $results['num_seats']) {
            $validation['errors'][] = sprintf(
                'Too many winners: %d elected but only %d seats',
                $winners_count,
                $results['num_seats']
            );
        }
    }

    // Check surplus transfers
    if (!empty($results['surplus_transfers'])) {
        foreach ($results['surplus_transfers'] as $transfer) {
            if ($transfer['surplus'] < 0) {
                $validation['errors'][] = 'Negative surplus transfer detected';
            }
        }
    }

    return $validation;
}

/**
 * Audit vote data for anomalies
 * @param array $vote_box_array Vote data
 * @return array Audit results
 */
function wpvp_audit_votes($vote_box_array)
{
    $audit = [
        'total_votes' => count($vote_box_array),
        'duplicate_voters' => [],
        'invalid_votes' => [],
        'timestamp_issues' => []
    ];

    $voter_names = [];

    foreach ($vote_box_array as $index => $vote) {
        // Check for duplicate voters
        $voter = $vote['userName'] ?? 'Unknown';
        if (isset($voter_names[$voter])) {
            $audit['duplicate_voters'][] = $voter;
        }
        $voter_names[$voter] = true;

        // Check for invalid vote structure
        if (empty($vote['userVote'])) {
            $audit['invalid_votes'][] = [
                'index' => $index,
                'reason' => 'Empty vote'
            ];
        }

        // Check timestamps if available
        if (isset($vote['timestamp'])) {
            $timestamp = strtotime($vote['timestamp']);
            if ($timestamp === false) {
                $audit['timestamp_issues'][] = [
                    'index' => $index,
                    'timestamp' => $vote['timestamp']
                ];
            }
        }
    }

    return $audit;
}

/**
 * Generate validation report
 * @param array $validation Validation results
 * @param array $audit Audit results
 * @return string HTML report
 */
function wpvp_generate_validation_report($validation, $audit)
{
    $html = '<div class="wpvp-validation-report">';
    $html .= '<h3>Validation Report</h3>';

    // Overall status
    $status_class = $validation['is_valid'] ? 'valid' : 'invalid';
    $status_text = $validation['is_valid'] ? 'VALID' : 'INVALID';
    $html .= '<p class="status ' . $status_class . '">Status: ' . $status_text . '</p>';

    // Errors
    if (!empty($validation['errors'])) {
        $html .= '<h4>Errors:</h4>';
        $html .= '<ul class="errors">';
        foreach ($validation['errors'] as $error) {
            $html .= '<li>' . esc_html($error) . '</li>';
        }
        $html .= '</ul>';
    }

    // Warnings
    if (!empty($validation['warnings'])) {
        $html .= '<h4>Warnings:</h4>';
        $html .= '<ul class="warnings">';
        foreach ($validation['warnings'] as $warning) {
            $html .= '<li>' . esc_html($warning) . '</li>';
        }
        $html .= '</ul>';
    }

    // Audit results
    if ($audit) {
        $html .= '<h4>Audit Results:</h4>';
        $html .= '<ul>';
        $html .= '<li>Total votes: ' . $audit['total_votes'] . '</li>';

        if (!empty($audit['duplicate_voters'])) {
            $html .= '<li>Duplicate voters: ' . count($audit['duplicate_voters']) . '</li>';
        }

        if (!empty($audit['invalid_votes'])) {
            $html .= '<li>Invalid votes: ' . count($audit['invalid_votes']) . '</li>';
        }

        $html .= '</ul>';
    }

    $html .= '</div>';

    return $html;
}
