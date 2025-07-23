<?php

/**
 * File: includes/vote-processing/process-stv.php
 * Single Transferable Vote (STV) processing
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process Single Transferable Vote (STV)
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options
 * @param int $num_seats Number of winners to elect
 * @return array Processed results with multiple winners
 */
function wpvp_process_stv($vote_box_array, $voting_options_array, $num_seats = 1)
{
    $results = [
        'winners' => [],
        'winners_data' => [],
        'eliminated_candidates' => [],
        'quota' => 0,
        'rounds' => [],
        'event_log' => [],
        'final_vote_counts' => [],
        'num_seats' => $num_seats,
        'voting_choice' => 'stv'
    ];

    $total_votes = count($vote_box_array);

    // Calculate Droop quota
    $results['quota'] = floor($total_votes / ($num_seats + 1)) + 1;
    $results['event_log'][] = "STV Election: Electing $num_seats candidate(s)";
    $results['event_log'][] = "Droop Quota: {$results['quota']} votes needed to win";

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
        'elected' => [],
        'eliminated' => [],
        'transfers' => []
    ];

    // Deep copy vote array for manipulation
    $working_votes = array_map(function ($vote) {
        return [
            'userName' => $vote['userName'],
            'userVote' => is_array($vote['userVote']) ? $vote['userVote'] : [$vote['userVote']],
            'weight' => 1.0 // Vote weight for surplus transfers
        ];
    }, $vote_box_array);

    $round_number = 1;

    // Continue until we have enough winners or run out of candidates
    while (count($results['winners']) < $num_seats && count($current_votes) > 0) {
        $round_data = [
            'round_number' => $round_number,
            'vote_counts' => [],
            'elected' => [],
            'eliminated' => [],
            'transfers' => []
        ];

        // Check for candidates meeting quota
        $elected_this_round = [];
        foreach ($current_votes as $candidate => $votes) {
            if ($votes >= $results['quota'] && !in_array($candidate, $results['winners'])) {
                $elected_this_round[] = $candidate;
                $results['winners'][] = $candidate;
                $results['winners_data'][] = [
                    'winner' => $candidate,
                    'votes' => $votes,
                    'round' => $round_number
                ];
                $results['event_log'][] = "Round $round_number: $candidate elected with $votes votes";
                $round_data['elected'][] = $candidate;
            }
        }

        // Handle surplus votes from elected candidates
        foreach ($elected_this_round as $winner) {
            $surplus = $current_votes[$winner] - $results['quota'];

            if ($surplus > 0 && count($results['winners']) < $num_seats) {
                $transfer_value = $surplus / $current_votes[$winner];
                $results['event_log'][] = "Transferring surplus of $surplus votes from $winner (transfer value: " .
                    number_format($transfer_value, 3) . ")";

                // Transfer surplus votes
                foreach ($working_votes as &$vote) {
                    if (!empty($vote['userVote']) && $vote['userVote'][0] === $winner) {
                        // Remove elected candidate
                        array_shift($vote['userVote']);

                        // Apply transfer value
                        $vote['weight'] *= $transfer_value;

                        // Find next preference
                        while (!empty($vote['userVote'])) {
                            $next_choice = $vote['userVote'][0];

                            // Skip if already elected or eliminated
                            if (
                                !in_array($next_choice, $results['winners']) &&
                                !in_array($next_choice, $results['eliminated_candidates']) &&
                                isset($current_votes[$next_choice])
                            ) {

                                $round_data['transfers'][] = [
                                    'from' => $winner,
                                    'to' => $next_choice,
                                    'weight' => $vote['weight']
                                ];
                                break;
                            }

                            array_shift($vote['userVote']);
                        }
                    }
                }
            }

            // Remove elected candidate from current votes
            unset($current_votes[$winner]);
        }

        // If no one elected this round and we still need winners, eliminate lowest
        if (empty($elected_this_round) && count($results['winners']) < $num_seats && !empty($current_votes)) {
            // Find candidate(s) with fewest votes
            $min_votes = min($current_votes);
            $candidates_to_eliminate = array_keys($current_votes, $min_votes);

            foreach ($candidates_to_eliminate as $eliminated) {
                $results['eliminated_candidates'][] = $eliminated;
                $results['event_log'][] = "Round $round_number: Eliminating $eliminated with $min_votes votes";
                $round_data['eliminated'][] = $eliminated;

                // Redistribute votes from eliminated candidate
                foreach ($working_votes as &$vote) {
                    if (!empty($vote['userVote']) && $vote['userVote'][0] === $eliminated) {
                        // Remove eliminated candidate
                        array_shift($vote['userVote']);

                        // Find next preference
                        while (!empty($vote['userVote'])) {
                            $next_choice = $vote['userVote'][0];

                            if (
                                !in_array($next_choice, $results['winners']) &&
                                !in_array($next_choice, $results['eliminated_candidates']) &&
                                isset($current_votes[$next_choice])
                            ) {

                                $round_data['transfers'][] = [
                                    'from' => $eliminated,
                                    'to' => $next_choice,
                                    'weight' => $vote['weight']
                                ];
                                break;
                            }

                            array_shift($vote['userVote']);
                        }
                    }
                }

                unset($current_votes[$eliminated]);
            }
        }

        // Recalculate votes after transfers
        if (!empty($round_data['transfers']) || !empty($round_data['eliminated'])) {
            // Reset vote counts
            foreach ($current_votes as $candidate => $count) {
                $current_votes[$candidate] = 0;
            }

            // Recount with weights
            foreach ($working_votes as $vote) {
                if (!empty($vote['userVote'][0])) {
                    $choice = $vote['userVote'][0];
                    if (isset($current_votes[$choice])) {
                        $current_votes[$choice] += $vote['weight'];
                    }
                }
            }

            // Round vote counts for display
            foreach ($current_votes as $candidate => &$votes) {
                $votes = round($votes, 2);
            }
        }

        $round_data['vote_counts'] = $current_votes;
        $results['rounds'][] = $round_data;

        $round_number++;

        // Safety check to prevent infinite loops
        if ($round_number > 50) {
            $results['event_log'][] = "Maximum rounds reached - ending election";
            break;
        }

        // Check if we have enough candidates left
        if (count($results['winners']) + count($current_votes) <= $num_seats) {
            // Elect all remaining candidates
            foreach ($current_votes as $candidate => $votes) {
                $results['winners'][] = $candidate;
                $results['winners_data'][] = [
                    'winner' => $candidate,
                    'votes' => $votes,
                    'round' => $round_number
                ];
                $results['event_log'][] = "Round $round_number: $candidate elected (insufficient candidates remaining)";
            }
            break;
        }
    }

    $results['final_vote_counts'] = $current_votes;
    $results['total_rounds'] = $round_number;

    // For compatibility with single-winner format
    if (count($results['winners']) > 0) {
        $results['winner'] = $results['winners'][0];
        $results['winner_votes'] = $results['winners_data'][0]['votes'];
    }

    return $results;
}

/**
 * Get STV voting summary
 * @param array $results Processed results
 * @return string HTML summary
 */
function wpvp_get_stv_summary($results)
{
    $html = '<div class="wpvp-stv-summary">';
    $html .= '<h4>Single Transferable Vote Results</h4>';
    $html .= '<p>Seats to fill: ' . $results['num_seats'] . '<br>';
    $html .= 'Quota: ' . $results['quota'] . ' votes</p>';

    // Show winners
    if (!empty($results['winners'])) {
        $html .= '<h5>Elected Candidates:</h5>';
        $html .= '<ol class="winners-list">';

        foreach ($results['winners_data'] as $winner_data) {
            $html .= '<li><strong>' . esc_html($winner_data['winner']) . '</strong>';
            $html .= ' - ' . round($winner_data['votes'], 1) . ' votes';
            $html .= ' (Round ' . $winner_data['round'] . ')</li>';
        }

        $html .= '</ol>';
    } else {
        $html .= '<p>No candidates elected.</p>';
    }

    // Show round-by-round summary
    if (!empty($results['event_log'])) {
        $html .= '<h5>Election Process:</h5>';
        $html .= '<ul class="event-log">';

        foreach ($results['event_log'] as $event) {
            $html .= '<li>' . esc_html($event) . '</li>';
        }

        $html .= '</ul>';
    }

    // Show eliminated candidates
    if (!empty($results['eliminated_candidates'])) {
        $html .= '<h5>Eliminated Candidates:</h5>';
        $html .= '<p>' . esc_html(implode(', ', $results['eliminated_candidates'])) . '</p>';
    }

    $html .= '</div>';

    return $html;
}
