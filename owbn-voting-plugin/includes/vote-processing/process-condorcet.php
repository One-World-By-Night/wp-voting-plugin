<?php

/**
 * File: includes/vote-processing/process-condorcet.php
 * Condorcet voting method processing
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process Condorcet voting method
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options
 * @return array Processed results with pairwise comparisons
 */
function wpvp_process_condorcet($vote_box_array, $voting_options_array)
{
    $results = [
        'winner' => null,
        'winner_votes' => 0,
        'pairwise_matrix' => [],
        'pairwise_winners' => [],
        'condorcet_winner' => null,
        'smith_set' => [],
        'event_log' => [],
        'vote_counts' => [],
        'percentages' => [],
        'rounds' => [],
        'voting_choice' => 'condorcet'
    ];

    // Get candidate list
    $candidates = [];
    foreach ($voting_options_array as $option) {
        $candidates[] = is_object($option) ? $option->text : $option;
    }

    $num_candidates = count($candidates);

    // Initialize pairwise comparison matrix
    foreach ($candidates as $i => $candidate_i) {
        foreach ($candidates as $j => $candidate_j) {
            if ($i !== $j) {
                $results['pairwise_matrix'][$candidate_i][$candidate_j] = 0;
            }
        }
    }

    // Process each ballot
    foreach ($vote_box_array as $vote) {
        $ranking = is_array($vote['userVote']) ? $vote['userVote'] : [$vote['userVote']];

        // Compare each pair of candidates based on this ballot
        for ($i = 0; $i < count($ranking); $i++) {
            for ($j = $i + 1; $j < count($ranking); $j++) {
                $preferred = $ranking[$i];
                $less_preferred = $ranking[$j];

                // This voter prefers $preferred over $less_preferred
                if (isset($results['pairwise_matrix'][$preferred][$less_preferred])) {
                    $results['pairwise_matrix'][$preferred][$less_preferred]++;
                }
            }
        }
    }

    // Determine pairwise winners
    foreach ($candidates as $i => $candidate_i) {
        $wins = 0;
        $losses = 0;
        $ties = 0;

        foreach ($candidates as $j => $candidate_j) {
            if ($i !== $j) {
                $votes_i_over_j = $results['pairwise_matrix'][$candidate_i][$candidate_j] ?? 0;
                $votes_j_over_i = $results['pairwise_matrix'][$candidate_j][$candidate_i] ?? 0;

                if ($votes_i_over_j > $votes_j_over_i) {
                    $wins++;
                    $results['pairwise_winners'][$candidate_i][$candidate_j] = true;
                } elseif ($votes_j_over_i > $votes_i_over_j) {
                    $losses++;
                    $results['pairwise_winners'][$candidate_i][$candidate_j] = false;
                } else {
                    $ties++;
                    $results['pairwise_winners'][$candidate_i][$candidate_j] = null;
                }

                $results['event_log'][] = "$candidate_i vs $candidate_j: $votes_i_over_j - $votes_j_over_i";
            }
        }

        $results['vote_counts'][$candidate_i] = $wins;

        // Check if this is a Condorcet winner (beats all others)
        if ($wins === count($candidates) - 1) {
            $results['condorcet_winner'] = $candidate_i;
            $results['winner'] = $candidate_i;
            $results['winner_votes'] = $wins;
            $results['event_log'][] = "Condorcet winner found: $candidate_i beats all other candidates";
        }
    }

    // If no Condorcet winner, find Smith set and use a completion method
    if (!$results['condorcet_winner']) {
        $results['smith_set'] = wpvp_find_smith_set($candidates, $results['pairwise_winners']);
        $results['event_log'][] = "No Condorcet winner. Smith set: " . implode(', ', $results['smith_set']);

        // Use Schulze method as completion
        $results = wpvp_schulze_completion($results, $candidates);
    }

    // Calculate percentages based on pairwise wins
    $total_comparisons = count($candidates) - 1;
    foreach ($results['vote_counts'] as $candidate => $wins) {
        $results['percentages'][$candidate] = round(($wins / $total_comparisons) * 100, 2);
    }

    // Create a single round for consistency
    $results['rounds'][] = [
        'round_number' => 1,
        'vote_counts' => $results['vote_counts'],
        'eliminated' => [],
        'transfers' => []
    ];

    return $results;
}

/**
 * Find the Smith set (smallest set of candidates who beat all others)
 * @param array $candidates List of candidates
 * @param array $pairwise_winners Pairwise comparison results
 * @return array Smith set members
 */
function wpvp_find_smith_set($candidates, $pairwise_winners)
{
    $smith_set = $candidates;
    $changed = true;

    while ($changed) {
        $changed = false;
        $new_smith_set = [];

        foreach ($smith_set as $candidate) {
            $beaten_by_outsider = false;

            // Check if this candidate is beaten by someone outside current Smith set
            foreach ($candidates as $other) {
                if (!in_array($other, $smith_set)) {
                    if (
                        isset($pairwise_winners[$other][$candidate]) &&
                        $pairwise_winners[$other][$candidate] === true
                    ) {
                        $beaten_by_outsider = true;
                        break;
                    }
                }
            }

            if (!$beaten_by_outsider) {
                $new_smith_set[] = $candidate;
            } else {
                $changed = true;
            }
        }

        $smith_set = $new_smith_set;
    }

    return $smith_set;
}

/**
 * Schulze method completion for when no Condorcet winner exists
 * @param array $results Current results
 * @param array $candidates List of candidates
 * @return array Updated results with Schulze winner
 */
function wpvp_schulze_completion($results, $candidates)
{
    $num_candidates = count($candidates);

    // Initialize strongest path matrix
    $strongest_paths = [];

    // Initialize with direct pairwise comparisons
    foreach ($candidates as $i => $candidate_i) {
        foreach ($candidates as $j => $candidate_j) {
            if ($i !== $j) {
                $votes_i_over_j = $results['pairwise_matrix'][$candidate_i][$candidate_j] ?? 0;
                $votes_j_over_i = $results['pairwise_matrix'][$candidate_j][$candidate_i] ?? 0;

                if ($votes_i_over_j > $votes_j_over_i) {
                    $strongest_paths[$candidate_i][$candidate_j] = $votes_i_over_j;
                } else {
                    $strongest_paths[$candidate_i][$candidate_j] = 0;
                }
            }
        }
    }

    // Floyd-Warshall algorithm to find strongest paths
    foreach ($candidates as $k => $candidate_k) {
        foreach ($candidates as $i => $candidate_i) {
            foreach ($candidates as $j => $candidate_j) {
                if ($i !== $j && $i !== $k && $j !== $k) {
                    $path_through_k = min(
                        $strongest_paths[$candidate_i][$candidate_k] ?? 0,
                        $strongest_paths[$candidate_k][$candidate_j] ?? 0
                    );

                    $direct_path = $strongest_paths[$candidate_i][$candidate_j] ?? 0;

                    if ($path_through_k > $direct_path) {
                        $strongest_paths[$candidate_i][$candidate_j] = $path_through_k;
                    }
                }
            }
        }
    }

    // Determine Schulze winner
    $schulze_scores = [];
    foreach ($candidates as $candidate) {
        $schulze_scores[$candidate] = 0;
    }

    foreach ($candidates as $i => $candidate_i) {
        foreach ($candidates as $j => $candidate_j) {
            if ($i !== $j) {
                $path_i_to_j = $strongest_paths[$candidate_i][$candidate_j] ?? 0;
                $path_j_to_i = $strongest_paths[$candidate_j][$candidate_i] ?? 0;

                if ($path_i_to_j > $path_j_to_i) {
                    $schulze_scores[$candidate_i]++;
                }
            }
        }
    }

    // Find winner with highest Schulze score
    $max_score = max($schulze_scores);
    $schulze_winners = array_keys($schulze_scores, $max_score);

    if (count($schulze_winners) === 1) {
        $results['winner'] = $schulze_winners[0];
        $results['winner_votes'] = $max_score;
        $results['event_log'][] = "Schulze winner: {$schulze_winners[0]} with score $max_score";
    } else {
        // Tie among Schulze winners
        $results['tie'] = true;
        $results['tied_candidates'] = $schulze_winners;
        $results['event_log'][] = "Schulze method tie between: " . implode(', ', $schulze_winners);
    }

    $results['schulze_scores'] = $schulze_scores;

    return $results;
}

/**
 * Get Condorcet voting summary
 * @param array $results Processed results
 * @return string HTML summary
 */
function wpvp_get_condorcet_summary($results)
{
    $html = '<div class="wpvp-condorcet-summary">';
    $html .= '<h4>Condorcet Method Results</h4>';

    if ($results['condorcet_winner']) {
        $html .= '<p class="winner">';
        $html .= '<strong>Condorcet Winner: ' . esc_html($results['condorcet_winner']) . '</strong><br>';
        $html .= 'Defeats all other candidates in pairwise comparisons';
        $html .= '</p>';
    } else {
        $html .= '<p>No Condorcet winner (no candidate beats all others)</p>';

        if (!empty($results['smith_set'])) {
            $html .= '<p>Smith Set: ' . esc_html(implode(', ', $results['smith_set'])) . '</p>';
        }

        if ($results['winner']) {
            $html .= '<p class="winner">';
            $html .= '<strong>Schulze Winner: ' . esc_html($results['winner']) . '</strong>';
            $html .= '</p>';
        }
    }

    // Pairwise comparison matrix
    $html .= '<h5>Pairwise Comparisons:</h5>';
    $html .= '<table class="pairwise-matrix">';
    $html .= '<thead><tr><th></th>';

    $candidates = array_keys($results['pairwise_matrix']);
    foreach ($candidates as $candidate) {
        $html .= '<th>' . esc_html($candidate) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($candidates as $row_candidate) {
        $html .= '<tr><th>' . esc_html($row_candidate) . '</th>';

        foreach ($candidates as $col_candidate) {
            if ($row_candidate === $col_candidate) {
                $html .= '<td class="diagonal">-</td>';
            } else {
                $votes = $results['pairwise_matrix'][$row_candidate][$col_candidate] ?? 0;
                $opponent_votes = $results['pairwise_matrix'][$col_candidate][$row_candidate] ?? 0;

                $class = '';
                if ($votes > $opponent_votes) {
                    $class = 'winner';
                } elseif ($votes < $opponent_votes) {
                    $class = 'loser';
                }

                $html .= '<td class="' . $class . '">' . $votes . '</td>';
            }
        }

        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    // Win summary
    $html .= '<h5>Pairwise Win Summary:</h5>';
    $html .= '<table class="vote-results">';
    $html .= '<thead><tr><th>Candidate</th><th>Wins</th><th>Win %</th></tr></thead>';
    $html .= '<tbody>';

    arsort($results['vote_counts']);
    foreach ($results['vote_counts'] as $candidate => $wins) {
        $percentage = $results['percentages'][$candidate] ?? 0;
        $row_class = ($candidate === $results['winner']) ? 'winner-row' : '';

        $html .= '<tr class="' . $row_class . '">';
        $html .= '<td>' . esc_html($candidate) . '</td>';
        $html .= '<td>' . $wins . '</td>';
        $html .= '<td>' . $percentage . '%</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    return $html;
}
