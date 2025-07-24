<?php

/**
 * File: includes/vote-processing/process-disciplinary.php
 * Disciplinary/Punishment vote processing
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Process disciplinary/punishment votes
 * Uses cascade system where votes transfer to more severe punishments if threshold not met
 * @param array $vote_box_array Array of all votes cast
 * @param array $voting_options_array Array of voting options (ignored - uses fixed punishments)
 * @return array Processed results with winner and cascade details
 */
function wpvp_process_disciplinary($vote_box_array, $voting_options_array = null)
{
    // Fixed punishment order from LEAST to MOST severe
    $punishments = [
        'Condemnation',
        'Censure',
        'Probation',
        '1 Strike',
        '2 Strikes',
        'Temporary Ban',
        'Indefinite Ban/3 Strikes',
        'Permanent Ban'
    ];

    // Reverse for processing (we process from least to most severe)
    $punishments = array_reverse($punishments);

    $results = [
        'vote_counts' => array_fill_keys($punishments, 0),
        'voters_by_option' => array_fill_keys($punishments, []),
        'winner' => null,
        'winner_votes' => 0,
        'winner_percentage' => 0,
        'percentages' => [],
        'rounds' => [],
        'event_log' => [],
        'total_votes' => count($vote_box_array),
        'voting_choice' => 'punishment'
    ];

    if ($results['total_votes'] === 0) {
        $results['event_log'][] = 'No votes cast';
        return $results;
    }

    // Count initial first-choice votes
    foreach ($vote_box_array as $vote) {
        $first_choice = is_array($vote['userVote']) ? $vote['userVote'][0] : $vote['userVote'];
        $voter_name = $vote['userName'] ?? 'Anonymous';

        if (in_array($first_choice, $punishments)) {
            $results['vote_counts'][$first_choice]++;
            $results['voters_by_option'][$first_choice][] = $voter_name;
        }
    }

    // Store initial round
    $results['rounds'][] = [
        'round_number' => 1,
        'vote_counts' => $results['vote_counts'],
        'action' => 'Initial vote count'
    ];

    // Process cascade from least to most severe
    $current_votes = $results['vote_counts'];
    $current_voters = $results['voters_by_option'];
    $round_number = 1;

    foreach ($punishments as $index => $punishment) {
        $punishment_votes = $current_votes[$punishment];
        $punishment_percentage = ($results['total_votes'] > 0)
            ? ($punishment_votes / $results['total_votes']) * 100
            : 0;

        // Determine required threshold
        $required_threshold = ($punishment === 'Permanent Ban') ? 66.7 : 51.0;
        $threshold_display = ($punishment === 'Permanent Ban') ? '66.7' : '51';

        $results['event_log'][] = "Checking \"$punishment\": $punishment_votes votes (" .
            number_format($punishment_percentage, 1) . "%)";

        // Check if punishment meets threshold
        if ($punishment_percentage >= $required_threshold) {
            $results['winner'] = $punishment;
            $results['winner_votes'] = $punishment_votes;
            $results['winner_percentage'] = $punishment_percentage;
            $results['event_log'][] = "Winner: \"$punishment\" with $punishment_votes votes (" .
                number_format($punishment_percentage, 1) . "%) - " .
                "meets $threshold_display% threshold";
            break;
        } else {
            // Transfer votes to next more severe punishment
            if ($index + 1 < count($punishments)) {
                $next_punishment = $punishments[$index + 1];

                // Transfer votes
                $current_votes[$next_punishment] += $punishment_votes;
                $current_votes[$punishment] = 0;

                // Transfer voter lists
                $current_voters[$next_punishment] = array_merge(
                    $current_voters[$next_punishment],
                    $current_voters[$punishment]
                );
                $current_voters[$punishment] = [];

                $results['event_log'][] = "Votes from \"$punishment\" (less than $threshold_display%) " .
                    "transferred to \"$next_punishment\"";

                $round_number++;

                // Store this round
                $results['rounds'][] = [
                    'round_number' => $round_number,
                    'vote_counts' => $current_votes,
                    'transferred_from' => $punishment,
                    'transferred_to' => $next_punishment,
                    'transferred_votes' => $punishment_votes
                ];
            } else {
                // No more severe punishments available
                $results['event_log'][] = "No winner found - \"$punishment\" has highest votes but " .
                    "doesn't meet $threshold_display% threshold";
            }
        }
    }

    // Calculate final percentages
    foreach ($current_votes as $punishment => $votes) {
        $results['percentages'][$punishment] = ($results['total_votes'] > 0)
            ? round(($votes / $results['total_votes']) * 100, 2)
            : 0;
    }

    // Update final vote counts and voter lists
    $results['vote_counts'] = $current_votes;
    $results['voters_by_option'] = $current_voters;

    // If no winner, set the most severe punishment with votes as the result
    if (!$results['winner']) {
        foreach (array_reverse($punishments) as $punishment) {
            if ($current_votes[$punishment] > 0) {
                $results['no_threshold_met'] = true;
                $results['highest_punishment'] = $punishment;
                $results['highest_votes'] = $current_votes[$punishment];
                $results['highest_percentage'] = $results['percentages'][$punishment];
                break;
            }
        }
    }

    return $results;
}

/**
 * Get disciplinary voting summary
 * @param array $results Processed results
 * @return string HTML summary
 */
function wpvp_get_disciplinary_summary($results)
{
    $html = '<div class="wpvp-disciplinary-summary">';
    $html .= '<h4>Disciplinary Vote Results</h4>';

    if (!empty($results['winner'])) {
        $threshold = ($results['winner'] === 'Permanent Ban') ? '66.7%' : '51%';
        $html .= '<p class="winner">';
        $html .= '<strong>Decision: ' . esc_html($results['winner']) . '</strong><br>';
        $html .= 'Votes: ' . $results['winner_votes'] . ' (' .
            number_format($results['winner_percentage'], 1) . '%)<br>';
        $html .= 'Required threshold: ' . $threshold . ' ✓';
        $html .= '</p>';
    } elseif (!empty($results['no_threshold_met'])) {
        $html .= '<p class="no-winner">';
        $html .= '<strong>No punishment met required threshold</strong><br>';
        $html .= 'Highest: ' . esc_html($results['highest_punishment']) .
            ' with ' . $results['highest_votes'] . ' votes (' .
            number_format($results['highest_percentage'], 1) . '%)';
        $html .= '</p>';
    }

    // Show cascade progression
    if (count($results['rounds']) > 1) {
        $html .= '<h5>Vote Cascade:</h5>';
        $html .= '<ol class="cascade-log">';

        foreach ($results['event_log'] as $event) {
            if (strpos($event, 'transferred') !== false || strpos($event, 'Winner:') !== false) {
                $html .= '<li>' . esc_html($event) . '</li>';
            }
        }

        $html .= '</ol>';
    }

    // Final vote distribution
    $html .= '<h5>Final Vote Distribution:</h5>';
    $html .= '<table class="vote-results">';
    $html .= '<thead><tr><th>Punishment</th><th>Votes</th><th>Percentage</th><th>Threshold</th></tr></thead>';
    $html .= '<tbody>';

    // Show in severity order (most to least severe)
    $punishments_display = [
        'Permanent Ban',
        'Indefinite Ban/3 Strikes',
        'Temporary Ban',
        '2 Strikes',
        '1 Strike',
        'Probation',
        'Censure',
        'Condemnation'
    ];

    foreach ($punishments_display as $punishment) {
        $votes = $results['vote_counts'][$punishment] ?? 0;
        $percentage = $results['percentages'][$punishment] ?? 0;
        $threshold = ($punishment === 'Permanent Ban') ? '66.7%' : '51%';
        $meets_threshold = (
            ($punishment === 'Permanent Ban' && $percentage >= 66.7) ||
            ($punishment !== 'Permanent Ban' && $percentage >= 51)
        );

        $row_class = ($punishment === $results['winner']) ? 'winner-row' : '';

        $html .= '<tr class="' . $row_class . '">';
        $html .= '<td>' . esc_html($punishment) . '</td>';
        $html .= '<td>' . $votes . '</td>';
        $html .= '<td>' . number_format($percentage, 1) . '%</td>';
        $html .= '<td>' . $threshold;
        if ($votes > 0) {
            $html .= $meets_threshold ? ' ✓' : ' ✗';
        }
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    // Voters list (optional - can be toggled)
    if (!empty($results['voters_by_option'])) {
        $html .= '<details class="voters-details">';
        $html .= '<summary>View Voter Details</summary>';

        foreach ($punishments_display as $punishment) {
            $voters = $results['voters_by_option'][$punishment] ?? [];
            if (!empty($voters)) {
                $html .= '<div class="punishment-voters">';
                $html .= '<strong>' . esc_html($punishment) . ':</strong> ';
                $html .= esc_html(implode(', ', $voters));
                $html .= '</div>';
            }
        }

        $html .= '</details>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Validate disciplinary vote configuration
 * @param array $voting_options Should contain standard punishment options
 * @return array Validation result
 */
function wpvp_validate_disciplinary_options($voting_options)
{
    $required_punishments = [
        'Permanent Ban',
        'Indefinite Ban/3 Strikes',
        'Temporary Ban',
        '2 Strikes',
        '1 Strike',
        'Probation',
        'Censure',
        'Condemnation'
    ];

    $validation = [
        'valid' => true,
        'errors' => []
    ];

    // Extract option texts
    $option_texts = [];
    foreach ($voting_options as $option) {
        if (is_object($option)) {
            $option_texts[] = $option->text;
        } elseif (is_array($option)) {
            $option_texts[] = $option['text'] ?? '';
        } else {
            $option_texts[] = (string)$option;
        }
    }

    // Check all required punishments are present
    foreach ($required_punishments as $punishment) {
        if (!in_array($punishment, $option_texts)) {
            $validation['valid'] = false;
            $validation['errors'][] = "Missing required punishment option: $punishment";
        }
    }

    // Check for extra options
    foreach ($option_texts as $option) {
        if (!in_array($option, $required_punishments)) {
            $validation['warnings'][] = "Unknown punishment option: $option (will be ignored)";
        }
    }

    return $validation;
}
