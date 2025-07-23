<?php

/**
 * File: includes/vote-processing/process-rcv.php
 * Instant Runoff Voting (IRV/RCV) processing
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process Instant Runoff Voting (IRV)
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options
 * @return array Processed results with rounds and winner
 */
function wpvp_process_rcv($vote_box_array, $voting_options_array)
{
    $results = [
        'rounds' => [],
        'winner' => null,
        'winner_votes' => 0,
        'event_log' => [],
        'eliminated_candidates' => [],
        'final_vote_counts' => []
    ];

    // Initialize current votes
    $current_votes = [];
    foreach ($voting_options_array as $option) {
        $option_text = is_object($option) ? $option->text : $option;
        $current_votes[$option_text] = 0;
    }

    // Count initial first-choice votes
    foreach ($vote_box_array as $vote) {
        if (!empty($vote['userVote'][0])) {
            $first_choice = $vote['userVote'][0];
            if (isset($current_votes[$first_choice])) {
                $current_votes[$first_choice]++;
            }
        }
    }

    // Store first round
    $results['rounds'][] = [
        'round_number' => 1,
        'vote_counts' => $current_votes,
        'eliminated' => [],
        'transfers' => []
    ];

    $round_number = 1;
    $total_votes = count($vote_box_array);
    $majority_threshold = floor($total_votes / 2) + 1;

    // Deep copy vote array for manipulation
    $working_votes = array_map(function ($vote) {
        return [
            'userName' => $vote['userName'],
            'userVote' => is_array($vote['userVote']) ? $vote['userVote'] : [$vote['userVote']]
        ];
    }, $vote_box_array);

    // IRV elimination rounds
    while (!empty($current_votes) && count($current_votes) > 1) {
        // Check for majority winner
        $max_votes = max($current_votes);
        if ($max_votes >= $majority_threshold) {
            $results['winner'] = array_search($max_votes, $current_votes);
            $results['winner_votes'] = $max_votes;
            $results['event_log'][] = "Round $round_number: {$results['winner']} wins with a majority of $max_votes votes!";
            break;
        }

        // Find candidate(s) with fewest votes
        $min_votes = min($current_votes);
        $candidates_to_eliminate = array_keys($current_votes, $min_votes);

        // Log elimination
        $results['event_log'][] = "Round $round_number: Eliminating " . implode(', ', $candidates_to_eliminate) . " with $min_votes votes";

        // Eliminate candidates and redistribute votes
        $transfers = [];
        foreach ($candidates_to_eliminate as $eliminated) {
            $results['eliminated_candidates'][] = $eliminated;
            unset($current_votes[$eliminated]);

            // Redistribute votes
            foreach ($working_votes as &$vote) {
                if (!empty($vote['userVote']) && $vote['userVote'][0] === $eliminated) {
                    // Remove eliminated candidate
                    array_shift($vote['userVote']);

                    // Find next valid preference
                    $transferred = false;
                    while (!empty($vote['userVote']) && !$transferred) {
                        $next_choice = $vote['userVote'][0];

                        if (isset($current_votes[$next_choice])) {
                            $current_votes[$next_choice]++;
                            $transfers[] = [
                                'from' => $eliminated,
                                'to' => $next_choice,
                                'voter' => $vote['userName']
                            ];
                            $results['event_log'][] = "Round $round_number: Vote by {$vote['userName']} transferred from $eliminated to $next_choice";
                            $transferred = true;
                        } else {
                            // Next choice already eliminated, remove it
                            array_shift($vote['userVote']);
                        }
                    }

                    if (!$transferred) {
                        $results['event_log'][] = "Round $round_number: Vote by {$vote['userName']} exhausted (no remaining choices)";
                    }
                }
            }
        }

        $round_number++;

        // Store round results
        $results['rounds'][] = [
            'round_number' => $round_number,
            'vote_counts' => $current_votes,
            'eliminated' => $candidates_to_eliminate,
            'transfers' => $transfers
        ];

        // Check if only one candidate remains
        if (count($current_votes) === 1) {
            $results['winner'] = key($current_votes);
            $results['winner_votes'] = current($current_votes);
            $results['event_log'][] = "Round $round_number: {$results['winner']} wins as the last remaining candidate with {$results['winner_votes']} votes!";
            break;
        }

        // Check for tie among remaining candidates
        if (!empty($current_votes)) {
            $vote_values = array_values($current_votes);
            if (count(array_unique($vote_values)) === 1) {
                $results['tie'] = true;
                $results['tied_candidates'] = array_keys($current_votes);
                $results['event_log'][] = "Round $round_number: Tie between " . implode(', ', $results['tied_candidates']);
                break;
            }
        }
    }

    $results['final_vote_counts'] = $current_votes;
    $results['total_rounds'] = $round_number;

    return $results;
}

/**
 * Get RCV voting summary
 * @param array $results Processed results
 * @return string HTML summary
 */
function wpvp_get_rcv_summary($results)
{
    $html = '<div class="wpvp-rcv-summary">';
    $html .= '<h4>Instant Runoff Voting Results</h4>';

    if (!empty($results['tie'])) {
        $html .= '<p class="tie-notice">Tie between: ' . implode(', ', $results['tied_candidates']) . '</p>';
    } elseif ($results['winner']) {
        $html .= '<p class="winner">Winner: <strong>' . esc_html($results['winner']) . '</strong>';
        $html .= ' with ' . $results['winner_votes'] . ' votes after ' . $results['total_rounds'] . ' rounds</p>';
    }

    // Show elimination log
    if (!empty($results['event_log'])) {
        $html .= '<h5>Round-by-Round Results:</h5>';
        $html .= '<ul class="event-log">';
        foreach ($results['event_log'] as $event) {
            $html .= '<li>' . esc_html($event) . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';

    return $html;
}
