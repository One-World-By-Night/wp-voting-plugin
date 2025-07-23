<?php

/**
 * File: includes/vote-processing/process-vote.php
 * Main vote processing orchestrator
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process votes based on voting type
 * @param array $vote_data Vote configuration data
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options
 * @return array Processed results
 */
function wpvp_process_votes($vote_data, $vote_box_array, $voting_options_array)
{
    $voting_choice = $vote_data['voting_choice'] ?? 'single';
    $results = [];

    switch ($voting_choice) {
        case 'single':
            $results = wpvp_process_singleton($vote_box_array, $voting_options_array);
            break;

        case 'irv':
            $results = wpvp_process_rcv($vote_box_array, $voting_options_array);
            break;

        case 'stv':
            $num_seats = intval($vote_data['number_of_winner'] ?? 1);
            $results = wpvp_process_stv($vote_box_array, $voting_options_array, $num_seats);
            break;

        case 'condorcet':
            $results = wpvp_process_condorcet($vote_box_array, $voting_options_array);
            break;

        case 'punishment':
            $results = wpvp_process_punishment($vote_box_array, $voting_options_array);
            break;

        default:
            $results = [
                'error' => 'Invalid voting type',
                'voting_choice' => $voting_choice
            ];
    }

    // Add metadata
    $results['total_votes'] = count($vote_box_array);
    $results['voting_choice'] = $voting_choice;
    $results['timestamp'] = current_time('mysql');

    return $results;
}

/**
 * Calculate vote percentages
 * @param array $vote_counts Vote counts per option
 * @param int $total_votes Total number of votes
 * @return array Vote percentages
 */
function wpvp_calculate_percentages($vote_counts, $total_votes)
{
    $percentages = [];

    if ($total_votes > 0) {
        foreach ($vote_counts as $option => $count) {
            $percentages[$option] = round(($count / $total_votes) * 100, 2);
        }
    }

    return $percentages;
}

/**
 * Sort candidates by vote count
 * @param array $vote_counts Vote counts per candidate
 * @param string $order 'DESC' or 'ASC'
 * @return array Sorted vote counts
 */
function wpvp_sort_by_votes($vote_counts, $order = 'DESC')
{
    if ($order === 'DESC') {
        arsort($vote_counts);
    } else {
        asort($vote_counts);
    }

    return $vote_counts;
}
