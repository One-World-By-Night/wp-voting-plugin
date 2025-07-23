<?php

/**
 * File: includes/vote-processing/process-singleton.php
 * Single choice vote processing
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process single choice votes
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options
 * @return array Processed results with winner and vote counts
 */
function wpvp_process_singleton($vote_box_array, $voting_options_array)
{
    $results = [
        'vote_counts' => [],
        'voters_by_option' => [],
        'winner' => null,
        'winner_votes' => 0,
        'percentages' => [],
        'rounds' => []
    ];

    // Initialize vote counts and voter tracking
    foreach ($voting_options_array as $option) {
        $option_text = is_object($option) ? $option->text : $option;
        $results['vote_counts'][$option_text] = 0;
        $results['voters_by_option'][$option_text] = [];
    }

    // Count votes and track voters
    foreach ($vote_box_array as $vote) {
        $voted_option = $vote['userVote'];
        $voter_name = $vote['userName'] ?? 'Anonymous';

        if (isset($results['vote_counts'][$voted_option])) {
            $results['vote_counts'][$voted_option]++;
            $results['voters_by_option'][$voted_option][] = $voter_name;
        }
    }

    // Calculate percentages
    $total_votes = count($vote_box_array);
    $results['percentages'] = wpvp_calculate_percentages($results['vote_counts'], $total_votes);

    // Sort by vote count descending
    $results['vote_counts'] = wpvp_sort_by_votes($results['vote_counts']);

    // Determine winner
    if (!empty($results['vote_counts'])) {
        reset($results['vote_counts']);
        $results['winner'] = key($results['vote_counts']);
        $results['winner_votes'] = current($results['vote_counts']);

        // Check for ties
        $max_votes = $results['winner_votes'];
        $tied_candidates = [];

        foreach ($results['vote_counts'] as $candidate => $votes) {
            if ($votes == $max_votes) {
                $tied_candidates[] = $candidate;
            }
        }

        if (count($tied_candidates) > 1) {
            $results['tie'] = true;
            $results['tied_candidates'] = $tied_candidates;
            $results['winner'] = null; // No clear winner
        }
    }

    // Single round for simple voting
    $results['rounds'][] = [
        'round_number' => 1,
        'vote_counts' => $results['vote_counts'],
        'eliminated' => [],
        'transfers' => []
    ];

    return $results;
}

/**
 * Get voting summary for single choice
 * @param array $results Processed results
 * @return string HTML summary
 */
function wpvp_get_singleton_summary($results)
{
    $html = '<div class="wpvp-singleton-summary">';
    $html .= '<h4>Voting Results</h4>';

    if (!empty($results['tie'])) {
        $html .= '<p class="tie-notice">Tie between: ' . implode(', ', $results['tied_candidates']) . '</p>';
    } elseif ($results['winner']) {
        $html .= '<p class="winner">Winner: <strong>' . esc_html($results['winner']) . '</strong>';
        $html .= ' with ' . $results['winner_votes'] . ' votes';
        $html .= ' (' . $results['percentages'][$results['winner']] . '%)</p>';
    }

    $html .= '<table class="vote-results">';
    $html .= '<thead><tr><th>Option</th><th>Votes</th><th>Percentage</th></tr></thead>';
    $html .= '<tbody>';

    foreach ($results['vote_counts'] as $option => $votes) {
        $percentage = $results['percentages'][$option] ?? 0;
        $html .= '<tr>';
        $html .= '<td>' . esc_html($option) . '</td>';
        $html .= '<td>' . $votes . '</td>';
        $html .= '<td>' . $percentage . '%</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    return $html;
}
