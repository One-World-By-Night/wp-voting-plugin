<?php
// function voting_remark()
// {
// function get_all_vote()
// {
//     global $wpdb;
//     $query = "SELECT id FROM {$wpdb->prefix}voting";
//     return $wpdb->get_results($query); // Returns an array of objects
// }

// $all_votes = get_all_vote();
// error_log(print_r($all_votes, true));

// foreach ($all_votes as $all_vote) {

function Closingbody($user, $image_url, $vote,$all_users)
{
    $link="https://chronicles.delimp.net/owbn-voting-box/$vote->id";

    $vote_data = $vote->id !== null ? get_vote_data_by_id($vote->id) : null;
    $vote_box = $vote_data['vote_box'];

    $all_voted_users = []; // Initialize to prevent errors
    $vote_box_array = json_decode($vote_box, true);
    if (is_array($vote_box_array)) {
        foreach ($vote_box_array as $option) {
            $all_voted_users[] = $option['userId'];
        }
    }

    $users_not_voted = array_filter($all_users, function ($user) use ($all_voted_users) {
        return !in_array($user->ID, $all_voted_users);
    });

    // Generate "Votes NOT Received" List
    $users_not_voted_list = '';
    if (!empty($users_not_voted)) {
        $users_not_voted_list .= '<ul>';
        foreach ($users_not_voted as $usernew) {
            $user_url = site_url() . '/chronicles/' . esc_html($usernew->user_login);
            $users_not_voted_list .= '<li><a href="' . $user_url . '">' . esc_html($usernew->display_name) . '</a></li>';
        }
        $users_not_voted_list .= '</ul>';
    } else {
        $users_not_voted_list = '<p>No pending votes.</p>';
    }


    $emailBody = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Template</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
            }
            .email-container {
                width: 100%;
                background-color: #ffffff;
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                text-align: center;
                background-color: black;
                color: white;
                padding: 15px 0;
                border-radius: 8px;
            }
            .email-content {
                margin-top: 20px;
            }
            .email-footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
            .email-button {
                display: inline-block;
                background-color: black;
                color: white !important;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>

    <div class="email-container">
        <div class="email-header">

        
                <img src="' . esc_url($image_url) . '" alt="Logo" style="max-width: 150px;">
            
        </div>

        <div class="email-content">
            <p>Dear ' . htmlspecialchars($user->display_name) . ',</p>
            <p>We wanted to remind you that voting for the following vote will close tomorrow:</p>
            <p><strong>Vote: ' . htmlspecialchars($vote->proposal_name) . '</strong></p>
            <p>Voting will close tomorrow, ' . htmlspecialchars($vote->closing_date) . '.</p>
            <p>Make sure to cast your vote before it closes:</p>
                        <a href="' . $link . '" class="email-button" target="_blank">View Details</a>

        </div>

 </div>
        <div class="email-container" style="font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9;">
    <h3 style="color: #333; text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px;">Votes NOT Received</h3>
    <ul style="list-style-type: none; padding: 0; margin: 15px 0;">
       
         <li style="background: #fff; margin: 8px 0; padding: 10px; border-radius: 5px; border: 1px solid #ddd; color:black !important;">
                <strong> ' . $users_not_voted_list . ' </strong>
            </li>
              </ul>
</div>
       
    </div>

    </body>
    </html>';

    return $emailBody;
}



function Openingbody($user, $image_url, $vote)
{

    $link="https://chronicles.delimp.net/owbn-voting-box/$vote->id";
    $emailBody = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Template</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f9f9f9;
            }
            .email-container {
                width: 100%;
                background-color: #ffffff;
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                text-align: center;
                background-color: black;
                color: white;
                padding: 15px 0;
                border-radius: 8px;
            }
            .email-content {
                margin-top: 20px;
            }
            .email-footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
            .email-button {
                display: inline-block;
                background-color: black;
                color: white !important;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>

    <div class="email-container">
        <div class="email-header">
            <img src="' . esc_url($image_url) . '" alt="Logo" style="max-width: 150px;">
           
        </div>

        <div class="email-content">
            <p>Dear ' . htmlspecialchars($user->display_name) . ',</p>
           <p>We are excited to inform you that voting for the following votes has started today:</p>

            <p><strong>Vote: ' . htmlspecialchars($vote->proposal_name) . '</strong></p>
            <p>Voting start, ' . htmlspecialchars($vote->opening_date) . '.</p>
           
        </div>


        <p>To participate, click the button below:</p>
               <a href="' . $link . '" class="email-button" target="_blank">View Details</a>
            </div>

          
        </div>

        </body>
        </html>';

    return $emailBody;
}








function Remarksdata($vote_id)
{
    
    error_log("Processing  ID: " . $vote_id);

    $link="https://chronicles.delimp.net/owbn-voting-result/$vote_id";

    $vote_data = $vote_id !== null ? get_vote_data_by_id($vote_id) : '';

    // Initialize variables
    $content = '';
    $voting_choice = '';
    $voting_options_json = json_encode([]);
    $vote_box_json = json_encode([]);
    $vote_type = '';
    $opening_date = '';
    $closing_date = '';
    $current_user_vote = '';
    $current_user_comment = '';
    $vote_id='';
    $remark='';
    $proposal_name='';
    $vote_box=[];
    $voting_options='';
    $voting_options_json='';
    // If vote data is found, populate form fields for editing
    if ($vote_data) {
        $vote_id = $vote_data['id'];
        $remark = $vote_data['remark'];
        $proposal_name = $vote_data['proposal_name'];
        $content = $vote_data['content'];
        $voting_choice = $vote_data['voting_choice'];
        // $add_comment = $vote_data['add_commnet'];
        $vote_box = $vote_data['vote_box'];
        $created_by = $vote_data['created_by'];
        $voting_options = unserialize($vote_data['voting_options']);
        $vote_type = $vote_data['vote_type'];
        $opening_date = $vote_data['opening_date'];
        $closing_date = $vote_data['closing_date'];
        $files_name = unserialize($vote_data['files_name']);
        // Encode vote data as JSON
        $voting_options_json = json_encode($voting_options);
        $vote_box_json = json_encode($vote_box);

        // Decode the JSON back to a PHP array

    }
    $vote_box_array = json_decode($vote_box, true);
    $vote_box_array_new = json_decode($vote_box, true);
    $voting_options_array = json_decode($voting_options_json);
    // Get voted user IDs
    if (is_array($vote_box_array)) {
        foreach ($vote_box_array as $option) {
            $all_voted_users[] = $option['userId'];
        }
    }
    echo $voting_choice;

    ob_start();
 if ($voting_choice === 'single') : ?>
        <div class="result--box progress-bar" style="width: 100%; max-width: 600px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); margin: 20px auto; text-align: center;">
            <div class="progress-wrappeer">
            <h5 style="font-size: 22px; font-weight: 600; margin-bottom: 10px; color: #333;">
            Vote Closed At: <?php echo htmlspecialchars($closing_date) . ' 00:00:00'; ?>
                <h1 style="font-size: 22px; font-weight: 600; margin-bottom: 10px; color: #333;">
                    Vote: <?php echo htmlspecialchars($proposal_name) . '-' . htmlspecialchars($vote_id); ?>
                </h1>
                
                <?php
                // Display remark if available
                if (!empty($remark)) {
                    echo '<p style="font-size: 16px; color: #555;">Remark: ' . htmlspecialchars($remark) . '</p>';
                }
                
                if ($voting_options_array) {
                    $total_votes = count($vote_box_array);
                    $vote_counts = [];
                    $voters_for_option = [];
    
                    foreach ($voting_options_array as $option) {
                        $vote_counts[$option->text] = 0;
                        $voters_for_option[$option->text] = [];
                    }
    
                    foreach ($vote_box_array as $vote) {
                        $voted_option = $vote['userVote'];
                        $voter_name = $vote['userName'];
                        if (isset($vote_counts[$voted_option])) {
                            $vote_counts[$voted_option]++;
                            $voters_for_option[$voted_option][] = $voter_name;
                        }
                    }
    
                    // Sort by vote count
                    usort($voting_options_array, function ($a, $b) use ($vote_counts) {
                        return $vote_counts[$b->text] <=> $vote_counts[$a->text];
                    });
    
                    // Predefined colors
                    $colors = ['#42224F', '#5A3D99', '#732EBF', '#8B3DD6', '#A14DF3'];
    
                    // Display voting results
                    foreach ($voting_options_array as $index => $option) {
                        $votes = $vote_counts[$option->text] ?? 0;
                        $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;
                        $color = $index < count($colors) ? $colors[$index] : sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                ?>
                        <div class="poll" style="margin-bottom: 15px;">
                            <div class="label" style="font-weight: bold; margin-bottom: 5px; color: #222;">
                                <?php echo esc_html($option->text); ?>
                            </div>
                            <div class="bar" style="background: #ddd; border-radius: 5px; overflow: hidden; height: 20px; position: relative;">
                                <span class="barline" style="display: block; height: 100%; border-radius: 5px; width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>; transition: width 0.8s ease-in-out;"></span>
                            </div>
                            <div class="percent" style="font-size: 14px; margin-top: 5px; color: #555;">
                                <?php echo round($percentage, 2); ?>% (<?php echo $votes; ?> votes)
                            </div>
                        </div>
                <?php
                    }
    
                    // Display round results
                    echo '<ul class="round-description" style="margin-top: 20px; padding: 10px; background: #f9f9f9; border-radius: 5px; text-align: left;">';
                    echo '<h4 style="font-size: 18px; margin-bottom: 10px; color: #444;">Results (Using Normal)</h4>';
    
                    // Check for a winner or tie
                    if (!empty($voting_options_array)) {
                        $winner_votes = $vote_counts[$voting_options_array[0]->text];
                        $winners = array_filter($voting_options_array, function ($option) use ($vote_counts, $winner_votes) {
                            return $vote_counts[$option->text] === $winner_votes;
                        });
    
                        if (count($winners) > 1) {
                            echo '<b style="color: #d9534f;">It\'s a tie between: ';
                            echo implode(', ', array_map(fn($option) => esc_html($option->text), $winners));
                            echo '</b>';
                        } else {
                            $winner_option = $winners[0];
                            echo '<b style="color: #5cb85c;">Winner: ' . esc_html($winner_option->text) . ' with ' . esc_html($winner_votes) . ' votes</b>';
                        }
                    }
    
                    foreach ($voting_options_array as $option) {
                        $votes = $vote_counts[$option->text] ?? 0;
                        $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;
                        echo '<li style="list-style: none; margin: 5px 0; padding: 5px; background: #eee; border-radius: 5px; color: #333;">Option "' . esc_html($option->text) . '" received ' . esc_html($votes) . ' votes (' . round($percentage, 2) . '%)</li>';

                    }
    

                    echo '</ul>';
                 echo  '<a href="' . $link . '" class="email-button" target="_blank">View Result</a>';

                } else {
                    echo '<p style="color: #d9534f;">No voting options available.</p>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
    


    <?php if ($voting_choice !== 'single') { ?>
        <div class='result--box'>
            <div class="">
            <h5 style="font-size: 22px; font-weight: 600; margin-bottom: 10px; color: #333;">
            Vote Closed At: <?php echo htmlspecialchars($closing_date) . ' 00:00:00'; ?>
            <h1>Vote: <?php echo htmlspecialchars($proposal_name) . '-' . htmlspecialchars($vote_id); ?></h1>
            <?php
            // Check if remark exists and render the content dynamically
            $remarkContent = !empty($remark) ? '<p>Remark: ' . htmlspecialchars($remark) . '</p>' : '';
            echo $remarkContent;
            ?>

                <?php if ($voting_choice === 'condorcet') : ?>
                    <?php
                    // Initialize the pairwise comparison array
                    $pairwise = [];
                    foreach ($voting_options_array as $option) {
                        foreach ($voting_options_array as $other_option) {
                            if ($option->text !== $other_option->text) {
                                $pairwise[$option->text][$other_option->text] = 0; // Initialize vote count
                            }
                        }
                    }


                    // Count pairwise wins based on user votes
                    foreach ($vote_box_array as $vote) {
                        if (isset($vote['userVote']) && is_array($vote['userVote'])) {
                            $preferences = $vote['userVote']; // Get the user's ranked preferences
                            foreach ($preferences as $i => $candidate) {
                                for ($j = $i + 1; $j < count($preferences); $j++) {
                                    // Increment the pairwise count for the preferred candidate
                                    $pairwise[$candidate][$preferences[$j]]++; // Candidate preferred over preferences[$j]
                                }
                            }
                        }
                    }

                    // Determine the Condorcet winner
                    $condorcet_winner = null; // Initialize winner variable
                    $potential_winners = []; // To handle tie or cycle cases
                    foreach ($voting_options_array as $option) {
                        $candidate = $option->text;
                        $is_winner = true; // Assume the candidate is a winner until proven otherwise
                        foreach ($voting_options_array as $other_option) {
                            $other_candidate = $other_option->text;
                            // Check if the candidate wins against every other candidate
                            if ($candidate !== $other_candidate && $pairwise[$other_candidate][$candidate] > $pairwise[$candidate][$other_candidate]) {
                                $is_winner = false; // Candidate loses to another
                                break;
                            }
                        }
                        if ($is_winner) {
                            $potential_winners[] = $candidate; // Add potential winner
                        }
                    }

                    // Handle cases for single or multiple potential winners
                    if (count($potential_winners) === 1) {
                        $condorcet_winner = $potential_winners[0]; // Only one winner
                    } elseif (count($potential_winners) > 1) {
                        // Multiple winners detected (tie or cycle)
                        $condorcet_winner = null; // No clear winner due to cycle
                    }

                    // Display pairwise comparison results in a table format
                    echo '<div class="table--area"><table style="width:100%;text-align: left; border-collapse: collapse;"><thead><tr><th style="border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;"></th>';

                    // Create table headers for each voting option
                    foreach ($voting_options_array as $option) {
                        echo '<th style="border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;">' . esc_html($option->text) . '</th>';
                    }

                    echo '</tr></thead><tbody>';

                    // Generate rows for the pairwise comparison results
                    foreach ($voting_options_array as $row_option) {
                        echo '<tr><td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($row_option->text) . '</td>';
                        foreach ($voting_options_array as $col_option) {
                            // Show N/A for self-comparison
                            if ($row_option->text === $col_option->text) {
                                echo '<td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">N/A</td>';
                            } else {
                                // Display the number of votes each candidate received in pairwise comparison
                                echo '<td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($pairwise[$row_option->text][$col_option->text]) . '</td>';
                            }
                        }
                        echo '</tr>';
                    }

                    echo '</tbody></table></div>';

                    // Display the Condorcet winner
                    echo '<div class="round-description"><h4>Results (Using Condorcet)</h4><ul>';
                    if ($condorcet_winner) {
                        echo '<li>Winner: <strong>' . esc_html($condorcet_winner) . '</strong></li>';
                    } elseif (count($potential_winners) > 1) {
                        echo '<li>Multiple candidates are tied (cycle detected): <strong>' . implode(', ', array_map('esc_html', $potential_winners)) . '</strong></li>';
                    } else {
                        echo '<li>No Condorcet Winner (possible cycle)</li>'; // Indicate a cycle if no winner is found
                    }
                    echo '</ul></div>';

                    // Display first-choice votes
                    echo '<div class="round-description">';
                    // Count first-choice votes
                    $first_choice_counts = [];
                    foreach ($vote_box_array as $vote) {
                        if (isset($vote['userVote']) && is_array($vote['userVote'])) {
                            $first_choice = $vote['userVote'][0]; // First choice is the first element in the array
                            if (!isset($first_choice_counts[$first_choice])) {
                                $first_choice_counts[$first_choice] = 0;
                            }
                            $first_choice_counts[$first_choice]++;
                        }
                    }

                    echo '<ul>';
                    // Display first-choice votes
                    foreach ($first_choice_counts as $candidate => $count) {
                        echo "<li>Option “" . esc_html($candidate) . "” received " . esc_html($count) . " first-choice votes.</li>";
                    }

                    // Check if there is a Condorcet winner to display as the round winner
                    if ($condorcet_winner) {
                        $winner_votes = $first_choice_counts[$condorcet_winner] ?? 0;
                        echo '<li>Option “' . esc_html($condorcet_winner) . '” wins with a majority of ' . esc_html($winner_votes) . ' votes!</li>';
                    } elseif (count($potential_winners) > 1) {
                        echo '<li>Tie between options: ' . implode(', ', array_map('esc_html', $potential_winners)) . '.</li>';
                    } else {
                        echo '<li>No clear winner from first-choice votes.</li>';
                    }
                    echo '</ul>';

                    echo  '<a href="' . $link . '" class="email-button" target="_blank">View Result</a>';
                    echo '</div>';
                    ?>
                <?php endif; ?>







                <?php if ($voting_choice === 'irv') : ?>
                    <?php
                    // Initialize vote counts
                    $rounds = [];
                    $current_votes = [];
                    $event_log = []; // Initialize an array for event logging
                    $winner = null; // Initialize a variable for the winner

                    // Initialize counts for the first round
                    foreach ($voting_options_array as $option) {
                        $current_votes[$option->text] = 0;
                    }

                    // Count initial votes
                    foreach ($vote_box_array as $vote) {
                        if (!empty($vote['userVote'])) {
                            $first_choice = $vote['userVote'][0];
                            if (isset($current_votes[$first_choice])) {
                                $current_votes[$first_choice]++;
                            }
                        }
                    }

                    // Store the first round results
                    $rounds[] = $current_votes;

                    // Run the elimination process
                    $round_number = 1; // Initialize round number
                    while (true) {
                        if (empty($current_votes)) {
                            break;
                        }

                        // Check if any candidate has more than 50% of the votes
                        $total_votes = array_sum($current_votes);
                        $max_votes = max($current_votes);
                        $winner = array_search($max_votes, $current_votes);

                        // Log if a winner is found
                        if ($max_votes > $total_votes / 2) {
                            $event_log[] = "Round $round_number: Winner \"$winner\" with $max_votes votes.";
                            break;
                        }

                        // Find the candidate with the fewest votes
                        $min_votes = min($current_votes);
                        $eliminated = array_search($min_votes, $current_votes);

                        // Log the elimination event
                        $event_log[] = "Round $round_number: Option \"$eliminated\" had $min_votes votes for 1st choice and was eliminated.";

                        // Remove the candidate with the fewest votes
                        unset($current_votes[$eliminated]);

                        // Redistribute votes
                        foreach ($vote_box_array as &$vote) {
                            if (!empty($vote['userVote']) && $vote['userVote'][0] === $eliminated) {
                                // Shift the eliminated choice and find the next one
                                array_shift($vote['userVote']);
                                $next_choice = !empty($vote['userVote']) ? $vote['userVote'][0] : null;

                                // Log the redistribution attempt
                                if ($next_choice && isset($current_votes[$next_choice])) {
                                    $current_votes[$next_choice]++;
                                    $event_log[] = "Round $round_number: Vote by {$vote['userName']} for \"$eliminated\" eliminated. Checking next option: \"$next_choice\".";
                                } else {
                                    $event_log[] = "Round $round_number: Vote by {$vote['userName']} for \"$eliminated\" eliminated. No further choices.";
                                }
                            }
                        }

                        // Store the round results
                        $rounds[] = $current_votes;
                        $round_number++; // Increment the round number
                    }

                    // Output the results in a table
                    echo '<div class="table--area"><table style="width:100%;text-align: left; border-collapse: collapse;"><thead><tr><th style="border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;">Rounds</th>';
                    for ($i = 1; $i <= count($rounds); $i++) {
                        echo "<th style='border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;'>$i</th>";
                    }
                    echo '  </tr></thead><tbody>';

                    $total_votes_per_candidate = [];
                    foreach ($voting_options_array as $option) {
                        $total_votes_per_candidate[$option->text] = 0; // Initialize total votes
                        foreach ($rounds as $round) {
                            if (isset($round[$option->text])) {
                                $total_votes_per_candidate[$option->text] += $round[$option->text]; // Accumulate votes
                            }
                        }
                    }

                    // Sort candidates by total votes in descending order
                    arsort($total_votes_per_candidate);

                    // Display the sorted results
                    foreach ($total_votes_per_candidate as $candidate => $total_votes) {
                        echo '<tr><td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($candidate) . '</td>';
                        foreach ($rounds as $round) {
                            echo '<td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . (isset($round[$candidate]) ? esc_html($round[$candidate]) : 0) . '</td>';
                        }
                        echo '</tr>';
                    }

                    echo '  </tbody></table></div>';

                    // Display the event log
                    echo '<ul class="round-description">';
                    echo '<h4>Results (Using IRV)</h4>';

                    // Output the winner at the top
                    if ($winner) {
                        echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($max_votes) . ' votes</p>';
                    }

                    // Log the round events with round number
                    foreach ($event_log as $event) {
                        echo '<li>' . esc_html($event) . '</li>';
                    }

                    echo '</ul>';
                    echo  '<a href="' . $link . '" class="email-button" target="_blank">View Result</a>';
                    ?>
                <?php endif; ?>





                <?php if ($voting_choice === 'stv') : ?>

                    <?php
                    // Number of seats to be filled
                    $num_seats = 1;

                    // Calculate the quota using the Droop formula
                    $total_votes = count($vote_box_array);
                    $quota = floor($total_votes / ($num_seats + 1)) + 1;

                    // Initialize vote counts, rounds, and logs
                    $rounds = [];
                    $results_log = [];
                    $current_votes = [];

                    // Initialize votes for each candidate
                    foreach ($voting_options_array as $option) {
                        $current_votes[$option->text] = 0;
                    }

                    // Count initial first-choice votes
                    foreach ($vote_box_array as $vote) {
                        $first_choice = $vote['userVote'][0];
                        if (isset($current_votes[$first_choice])) {
                            $current_votes[$first_choice]++;
                        }
                    }

                    // Store the first round results
                    $rounds[] = $current_votes;

                    // STV elimination and transfer process
                    $round = 1;
                    $eliminated_candidates = [];
                    $winners = [];

                    while (count($winners) < $num_seats) {
                        // Log the current vote count for each candidate in the round
                        foreach ($current_votes as $candidate => $votes) {
                            if ($votes == 0 && !in_array($candidate, $eliminated_candidates)) {
                                $results_log[] = "Round $round: Option \"$candidate\" had zero votes for 1st choice and is eliminated.";
                                $eliminated_candidates[] = $candidate; // Mark candidate as eliminated
                                unset($current_votes[$candidate]);
                            } else {
                                $results_log[] = "Round $round: Option \"$candidate\" received $votes first-choice votes.";
                            }
                        }

                        // Check if there are any remaining candidates
                        if (empty($current_votes)) {
                            // $results_log[] = "No candidates left to count votes.";
                            // break; // Exit the loop if no candidates remain

                            $results_log[] = "No candidates left to count votes.";
                            $vote_counts = [];
                            foreach ($vote_box_array as $vote) {
                                $first_choice = $vote['userVote'][0];
                                if (!isset($vote_counts[$first_choice])) {
                                    $vote_counts[$first_choice] = ['votes' => 0];
                                }
                                $vote_counts[$first_choice]['votes']++;
                            }

                            $tied_candidates = array_filter($vote_counts, function ($vote) use ($max_votes) {
                                return $vote['votes'] === $max_votes;
                            });

                            if (count($tied_candidates) === 1) {
                                $winner = key($tied_candidates);  // Get the name of the winning candidate
                                $results_log[] = "The winner is $winner with $max_votes votes.";
                            } else if (count($tied_candidates) > 1) {
                                // Handle tie situation with multiple tied candidates
                                error_log(print_r($tied_candidates, true));
                                $tied_names = implode(" and ", array_keys($tied_candidates));
                                $results_log[] = "This would end up with a tie vote between $tied_names. The Head Coordinator breaks the tie.";
                            } else {
                                // Handle the case where there are no tied candidates, election concluded with no remaining candidates
                                $results_log[] = "Election concluded with no remaining candidates. No Winner, There are no remaining candidates to declare a winner, and the Head Coordinator will break the tie.";
                                error_log("No candidates found in the tie check.");
                            }
                            break;


                        }

                        // Check if any candidate has reached the quota
                        $max_votes = max($current_votes);
                        if ($max_votes >= $quota) {
                            $winner = array_search($max_votes, $current_votes);
                            $winners[] = $winner;
                            $results_log[] = "<strong>Winner: \"$winner\" wins with $max_votes votes in Round $round!</strong>";

                            // Transfer surplus votes if necessary
                            $surplus_votes = $max_votes - $quota;
                            if ($surplus_votes > 0) {
                                foreach ($vote_box_array as &$vote) {
                                    if ($vote['userVote'][0] === $winner) {
                                        array_shift($vote['userVote']); // Remove the winner from the vote
                                        if (!empty($vote['userVote'])) {
                                            $next_choice = $vote['userVote'][0];
                                            if (isset($current_votes[$next_choice])) {
                                                // Transfer surplus votes proportionally
                                                $current_votes[$next_choice] += $surplus_votes; // Adjust based on your surplus handling logic
                                                $results_log[] = "Round $round: Surplus votes from \"$winner\" transferred to \"$next_choice\".";
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            // Find the candidate with the fewest votes and eliminate
                            $min_votes = min($current_votes);
                            $eliminated = array_keys($current_votes, $min_votes);

                            // Log eliminated candidates and their vote reallocation
                            foreach ($eliminated as $candidate) {
                                $results_log[] = "Eliminated: $candidate (votes reallocated: $min_votes) in Round $round.";
                                $eliminated_candidates[] = $candidate; // Mark candidate as eliminated
                                unset($current_votes[$candidate]);

                                // Redistribute votes to the next available choice
                                foreach ($vote_box_array as &$vote) {
                                    if ($vote['userVote'][0] === $candidate) {
                                        array_shift($vote['userVote']); // Remove eliminated candidate
                                        $new_choice = $vote['userVote'][0] ?? null; // Get next choice
                                        if ($new_choice && isset($current_votes[$new_choice])) {
                                            $current_votes[$new_choice]++;
                                            $results_log[] = "Round $round: Vote by \"{$vote['userName']}\" reallocated to \"$new_choice\".";
                                        } else {
                                            $results_log[] = "Round $round: Vote by \"{$vote['userName']}\" for \"$candidate\" not reallocated; no valid choices left.";
                                        }
                                    }
                                }
                            }
                        }

                        // Store the round results and move to the next round
                        $rounds[] = $current_votes;
                        $round++;
                    }

                    // Output the round-by-round results in a table
                    echo '<div class="table--area"><table style="width:100%;text-align: left; border-collapse: collapse;"><thead><tr><th style="border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;">Rounds</th>';
                    for ($i = 1; $i <= count($rounds); $i++) {
                        echo "<th style='border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;'>$i</th>";
                    }
                    echo '</tr></thead><tbody>';

                    // Accumulate total votes per candidate across all rounds
                    $total_votes_per_candidate = [];
                    foreach ($voting_options_array as $option) {
                        $total_votes_per_candidate[$option->text] = 0;
                        foreach ($rounds as $round_data) {
                            $total_votes_per_candidate[$option->text] += $round_data[$option->text] ?? 0;
                        }
                    }

                    // Sort candidates by total votes and display in descending order
                    arsort($total_votes_per_candidate);
                    foreach ($total_votes_per_candidate as $candidate => $total_votes) {
                        echo '<tr><td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($candidate) . '</td>';
                        foreach ($rounds as $round_data) {
                            echo '<td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . (isset($round_data[$candidate]) ? esc_html($round_data[$candidate]) : 0) . '</td>';
                        }
                        echo '</tr>';
                    }

                    echo '</tbody></table></div>';
                    echo "<p class='quota--color'>Quota: $quota</p>";

                    // Display results in a log format
                    echo '<div class="round-description"><h4>Results (Using STV)</h4><ul>';
                    foreach ($winners as $winner) {
                        echo "<li><strong>Winner: $winner</strong></li>"; // Output the winners
                    }
                    foreach ($results_log as $log) {
                        echo "<li>$log</li>";
                    }
                    echo '</ul></div>';
                    echo  '<a href="' . $link . '" class="email-button" target="_blank">View Result</a>';
                    ?>

                <?php endif; ?>


 <?php
if ($voting_choice === 'punishment') :
    // List of punishments
    $punishments = [
        'Permanent Ban',
        'Indefinite Ban/3 Strikes',
        'Temporary Ban',
        '2 Strikes',
        '1 Strike',
        'Probation',
        'Censure',
        'Condemnation',
    ];

    // Initialize vote counts for the first round
    $current_votes = array_fill_keys($punishments, 0);
    $user_votes = array_fill_keys($punishments, []); // To store users' names per punishment
    $rounds = [];  // Store each round's vote count
    $event_log = []; // Log events during the rounds
    $total_votes = count($vote_box_array);  // Total votes
    $majority_threshold = $total_votes * 0.51; // Majority threshold

    // Count initial votes for the first choice and store user names
    foreach ($vote_box_array as $vote) {
        if (!empty($vote['userVote'])) {
            $first_choice = $vote['userVote'][0]; // Get the first choice
            $user_name = isset($vote['userName']) ? $vote['userName'] : 'Anonymous'; // User's name (assuming it's available)
            if (isset($current_votes[$first_choice])) {
                $current_votes[$first_choice]++;
                $user_votes[$first_choice][] = $user_name; // Store the user name for the vote
            }
        }
    }

    // Store first round result
    $rounds[] = $current_votes;

    // Start with the first punishment and check if it meets the 51% threshold
    $punishment_index = 0;
    $winner = null;

    while ($punishment_index < count($punishments)) {
        $current_punishment = $punishments[$punishment_index];
        $current_punishment_votes = $current_votes[$current_punishment];

        // Calculate the percentage for the current punishment
        $current_punishment_percentage = ($total_votes > 0) ? ($current_punishment_votes / $total_votes) * 100 : 0;

        // Check if the current punishment has more than 51% of the votes

        $required_threshold = ($current_punishment === 'Permanent Ban') ? 66.7 : 51.0;
        $required_threshold1 = ($current_punishment === 'Permanent Ban') ? 66.7 : 51;


        if ($current_punishment_percentage >= $required_threshold) {
        // if ($current_punishment_percentage > 51) {
            $winner = $current_punishment;
            $event_log[] = "Winner: \"$winner\" with $current_punishment_votes votes ($current_punishment_percentage%)";
            break; // Exit the loop if a winner is found
        } else {
            // If the current punishment doesn't meet the majority, transfer its votes to the next punishment
            if ($punishment_index + 1 < count($punishments)) {
                $next_punishment = $punishments[$punishment_index + 1]; // Get the next punishment
                $current_votes[$next_punishment] += $current_punishment_votes; // Add votes to the next punishment
                $current_votes[$current_punishment] = 0; // Reset the current punishment's votes
                $user_votes[$next_punishment] = array_merge($user_votes[$next_punishment], $user_votes[$current_punishment]); // Transfer users
                $user_votes[$current_punishment] = []; // Clear the current punishment's user list

                // $event_log[] = "Votes from \"$current_punishment\" (less than 51%) are transferred to \"$next_punishment\".";
                $event_log[] = "Votes from \"$current_punishment\" (less than $required_threshold1%) are transferred to \"$next_punishment\".";


                // Store the result after transferring votes
                $rounds[] = $current_votes;

                // Move to the next punishment
                $punishment_index++;
            } else {
                // If we've gone through all punishments and no one has won, end the loop
                $event_log[] = "No winner found after all punishments have been considered.";
                break;
            }
        }
    }

    // Output the results in a table
    echo '<div class="table--area"><table style="width:100%;text-align: left; border-collapse: collapse;"><thead><tr><th style="border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;">Rounds</th>';
    for ($i = 1; $i <= count($rounds); $i++) {
        echo "<th style='border: 1px solid #ddd; background: #b32017; padding: 15px; color: #fff;'>Round $i</th>";
    }
    echo '  </tr></thead><tbody>';

    // Display the rounds' results
    foreach ($punishments as $punishment) {
        echo '<tr><td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($punishment) . '</td>';
        foreach ($rounds as $round) {
            $round_total_votes = array_sum($round); // Calculate the total votes in the current round
            $percentage = ($round_total_votes > 0 && isset($round[$punishment])) ? number_format(($round[$punishment] / $round_total_votes) * 100, 2) : 0;
            echo '<td style="border: 1px solid #ddd; background: #fff; padding: 15px; color: #333;">' . esc_html($percentage) . '%</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';

    // Display the event log
    echo '<ul class="round-description">';
    echo '<h4>Results</h4>';
    if ($winner) {
        echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($current_votes[$winner]) . ' votes (' . esc_html(number_format($current_punishment_percentage, 2)) . '%)</p>';
        echo '<p><strong>Voted by:</strong> ' . implode(', ', $user_votes[$winner]) . '</p>'; // Voted by the winner's voters
    } else {
        echo '<p class="fw-bold">No winner determined.</p>';
    }

    foreach ($event_log as $event) {
        echo '<li>' . esc_html($event) . '</li>';
    }

    // Display the voters for each punishment
    echo '<h4>Voters for Each Punishment</h4>';
    foreach ($user_votes as $punishment => $users) {
        echo '<p><strong>' . esc_html($punishment) . ':</strong> ';
        echo implode(', ', $users) ?: 'No votes';
        echo '</p>';
    }

    echo '</ul>';
    echo  '<a href="' . $link . '" class="email-button" target="_blank">View Result</a>';
endif;
?>


            </div>

        </div>
    <?php }
    $emailbdy = ob_get_clean(); 
    
    ?>


    
<?php

 return array(
    'email_body' => $emailbdy,
    'vote_box_data' => $vote_box_array_new
);
}
// }
// add_shortcode('voting_remark', 'voting_remark_shortcode');
?>
