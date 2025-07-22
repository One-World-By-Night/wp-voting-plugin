<?php


function voting_result_shortcode()

{

    get_header();

    $id = get_query_var('id', 'default_value');



    if (!$id) {
        wp_die('Page not accessable');
    }
    $vote_data = isset($id) !== null ? get_vote_data_by_id($id) : null;
    if (empty($vote_data)) {
        wp_die('Vote not found');
    }
    // if ($vote_data['voting_stage'] != 'completed' && (!current_user_can('administrator'))) {
    //     wp_die('Voting is not completed');
    // }
    // if ($vote_data['voting_stage'] != 'completed' && (!is_user_logged_in()) && $vote_data['voting_stage'] != 'withdrawn') {
    //     wp_die('Voting is not completed');
    // }

    // if (($vote_data['voting_stage'] != 'completed' && !is_user_logged_in()) || ($vote_data['voting_stage'] != 'withdrawn' && !is_user_logged_in())) {
    //     wp_die('Voting is not completed');
    // }

    // if (!is_user_logged_in() && $vote_data['voting_stage'] != 'completed' && $vote_data['voting_stage'] != 'withdrawn') {
    //     wp_die('Voting is not completed');
    // }
    if (!is_user_logged_in()  && $vote_data['voting_stage'] == 'withdrawn') {
        wp_die('Voting is not completed');
    }

    //  if ( $vote_data['vote_type'] != 'withdrawn') {
    //         wp_die('Voting is not completed');
    //     }
    // if ((current_user_can('administrator') &&  $vote_data['voting_stage'] != 'completed') && $vote_data['vote_type'] == 'Disciplinary') {


    //     wp_die('Voting is not completed');
    // }
    if (is_user_logged_in()) {
        if (
            ($vote_data['voting_stage'] != 'completed') &&
            (
                $vote_data['vote_type'] == 'Disciplinary' ||
                $vote_data['vote_type'] == 'Coordinator Elections (Full Term)' ||
                $vote_data['vote_type'] == 'Coordinator Elections (Special)'
            )
        ) {
            wp_die('Voting is not completed');
        }
    }


    // Check if the user is logged in and if the user has the 'administrator' or 'author' role
    if ($vote_data['visibility'] == 'council' && (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author')))) {
        wp_die('You do not have permission to access this page.');
    } else {
        $vote_id = isset($id) ? intval($id) : null;
        $total_eligible_voters = 0;
        $all_admin_users = [];
        $all_author_users = [];
        $all_users = [];
        $all_voted_users = [];

        if (is_multisite()) {
            // Multisite: Collect all admins and authors from all sites
            $sites = get_sites();

            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);

                $admin_users = get_users(array('role' => 'administrator'));
                // $author_users = get_users(array('role' => 'author'));
                $author_users = get_users(array('role' => 'armember'));


                $all_admin_users = array_merge($all_admin_users, $admin_users);
                $all_author_users = array_merge($all_author_users, $author_users);
                $all_users = array_merge($all_users, $admin_users, $author_users);

                restore_current_blog();
            }

            $total_eligible_voters = count($all_admin_users) + count($all_author_users);
        } else {
            // Single site: Collect admins and authors
            $admin_users = get_users(array('role' => 'administrator'));
            // $author_users = get_users(array('role' => 'author'));
            $author_users = get_users(array('role' => 'armember'));


            $all_admin_users = $admin_users;
            $all_author_users = $author_users;
            $all_users = array_merge($admin_users, $author_users);
            $total_eligible_voters = count($admin_users) + count($author_users);
        }

        // Retrieve vote data based on the ID
        $vote_data = $vote_id !== null ? get_vote_data_by_id($vote_id) : null;

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

        // If vote data is found, populate form fields for editing
        if ($vote_data) {
            $proposal_name = $vote_data['proposal_name'];
            $remark = $vote_data['remark'];
            $created_by = $vote_data['created_by'];

            $number_of_winner = $vote_data['number_of_winner'];
            $content = $vote_data['content'];
            $voting_choice = $vote_data['voting_choice'];
            $vote_box = $vote_data['vote_box'];
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

        // Get users who have not voted
        $users_not_voted = array_filter($all_users, function ($user) use ($all_voted_users) {
            return !in_array($user->ID, $all_voted_users);
        });

 

        $voteData = [];
        foreach ($vote_box_array as $vote) {
            if (is_array($vote['userVote'])) {
                $votedFor = implode(", ", $vote['userVote']); // Convert array to readable text
            } else {
                $votedFor = $vote['userVote']; // Single vote case
            }

            if (!isset($voteData[$votedFor])) {
                $voteData[$votedFor] = 0;
            }
            $voteData[$votedFor]++;
        }

        $voteDataJson = json_encode($voteData, JSON_UNESCAPED_UNICODE);

        ob_start();
         // SendResultclosemail();
        // Sendmail();
      
       

        // Your HTML content or function calls to generate the preview content
?>
        <section class="owbnVP vote--result">
            <div class="vote--wrapper">
                <div class="vote--row">
                    <div class="col">
                        <div class="vote--result--area">
                            <div class="page-tittle">
                                <h1><?php echo esc_attr($proposal_name); ?></h1>
                            </div>


                            <?php if (!empty($remark)): ?>
                                <div class="page-tittle">
                                    Result Remark:<?php echo esc_html($remark); ?>

                                </div>
                            <?php endif; ?>


                            <div class="proposedby">
                                <div class="proposal--type">
                                    <span><b>Proposed By : </b></span>
                                    <span><?php echo esc_attr($vote_data['proposed_by']); ?></span>
                                </div>
                                <div class="proposal--type">
                                    <span><b>Seconded By:</b></span>
                                    <span><?php echo esc_attr($vote_data['seconded_by']); ?> </span>
                                </div>
                            </div>
                            <div class="vote--panel">
                                <div class="vote--type">
                                    <h2>Open Votes</h2>
                                </div>
                                <div class="row-wrapper">
                                    <div class="proposal--type"><span><b>Proposal Type :
                                            </b></span><span><?php echo esc_attr($vote_type); ?></span></div>
                                    <div class="proposal--type">
                                        <span><b>Opened :
                                            </b></span>
                                        <span>
                                            <?php echo date('m/d/Y h:iA', strtotime(esc_attr($opening_date))); ?>
                                            <?php echo get_timezone_abbreviation(); ?>
                                        </span>
                                    </div>
                                    <div class="proposal--type">
                                        <span><b>Closing :
                                            </b>
                                        </span>
                                        <span>
                                            <?php echo date('m/d/Y h:iA', strtotime(esc_attr($closing_date))); ?>
                                            <?php echo get_timezone_abbreviation(); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="vote-editor-body">
                                <?php echo wp_kses_post($content); ?>
                                <div class="document-area">
                                    <h4 class="ballot-heading">Ballot Options</h4>
                                    <!-- <p>File / Document: No file attachments for this vote.</p> -->
                                    <?php
                                    if ($voting_options_json) {
                                        // Decode JSON back into PHP array/object


                                        if ($voting_options_array) {
                                            foreach ($voting_options_array as $row) {
                                                // Debugging: Check if $row->text exists and is not empty
                                                if (isset($row->text)) {
                                                    echo '<p>' . esc_html($row->text) . '</p>';
                                                }
                                            }
                                        }
                                    }
                                   ?>
                                    <p><?php echo count($vote_box_array); ?> out of <?php echo $total_eligible_voters; ?>
                                        eligible voters cast their ballot</p>
                                </div>

                            </div>

                            <!-- Files -->
                            <!-- <div class="proposal--name file--document">
                                <h5>File / Document</h5>
                               
                                <div class="table--box">
                                    <table class="file--information--table" id="fileInfoTable">
                                        <thead>
                                            <tr>
                                                <th>File </th>
                                                <th>Description</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($vote_id) {
                                                if (!empty($files_name)) {
                                                    foreach ($files_name as $option) {
                                                        //!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))
                                                        if ((current_user_can('author') && $option['display'] == 1) || current_user_can('administrator')) {
                                                            //  print_r($option);

                                            ?>
                                                        <tr>
                                                            <td>
                                                                <div class="file--name">
                                                                    <p><a target="_blank" href="<?php if (isset($option['id'])) {
                                                                                                    echo wp_get_attachment_url($option['id']);
                                                                                                } ?>"><?php echo esc_html($option['fileName']); ?><span>(<?php echo esc_html($option['fileSize']); ?>
                                                                                bytes)</span></a></p>
                                                                </div>
                                                            </td>
                                                            <td>
                                                            <div><?php echo esc_html($option['description']); ?></div>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>

                                            <?php }
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div> -->



                            <canvas id="voteChart"></canvas>

                            <?php 
if($vote_data['voting_stage']=='completed'){
    // echo $vote_data['voting_stage'];
?>

                            <?php if ($voting_choice === 'single') : ?>
                                <div class="result--box progress-bar">
                                    <div class="progress-wrappeer">
                                        <?php
                                        if ($voting_options_array) {
                                            // Total number of votes
                                            $total_votes = count($vote_box_array);

                                            // Initialize vote count and voter details for each option
                                            $vote_counts = array();
                                            $voters_for_option = array();

                                            foreach ($voting_options_array as $option) {
                                                $vote_counts[$option->text] = 0;
                                                $voters_for_option[$option->text] = []; // Store voters for each option
                                            }

                                            // Count votes for each option and track who voted for it
                                            foreach ($vote_box_array as $vote) {
                                                $voted_option = $vote['userVote'];
                                                $voter_name = $vote['userName'];
                                                if (isset($vote_counts[$voted_option])) {
                                                    $vote_counts[$voted_option]++;
                                                    $voters_for_option[$voted_option][] = $voter_name;
                                                }
                                            }

                                            // Sort the options by vote count in descending order
                                            usort($voting_options_array, function ($a, $b) use ($vote_counts) {
                                                return $vote_counts[$b->text] <=> $vote_counts[$a->text];
                                            });

                                            // Predefined colors for progress bars
                                            $colors = ['#42224F', '#5A3D99', '#732EBF', '#8B3DD6', '#A14DF3'];

                                            // Function to generate a random color for additional options
                                            function random_color()
                                            {
                                                return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                                            }

                                            // Output voting results
                                            foreach ($voting_options_array as $index => $option) {
                                                $votes = isset($vote_counts[$option->text]) ? $vote_counts[$option->text] : 0;
                                                $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;
                                                $color = $index < count($colors) ? $colors[$index] : random_color();
                                        ?>
                                                <div class="poll">
                                                    <div class="label"><?php echo esc_html($option->text); ?></div>
                                                    <div class="bar">
                                                        <span class="barline" style="width:<?php echo $percentage; ?>%; background:<?php echo $color; ?>;"></span>
                                                    </div>
                                                    <div class="percent"><?php echo round($percentage, 2); ?>% (<?php echo $votes; ?> votes)</div>
                                                </div>
                                        <?php
                                            }

                                            // Display the description after the results
                                            echo '<ul class="round-description">';
                                            echo '<h4>Results (Using Normal)</h4>';

                                            // Check for the winner or a tie
                                            if (!empty($voting_options_array)) {
                                                $winner_votes = $vote_counts[$voting_options_array[0]->text];
                                                $winners = array_filter($voting_options_array, function ($option) use ($vote_counts, $winner_votes) {
                                                    return $vote_counts[$option->text] === $winner_votes;
                                                });

                                                if (count($winners) > 1) {
                                                    echo '<b>It\'s a tie between: ';
                                                    echo implode(', ', array_map(function ($option) {
                                                        return esc_html($option->text);
                                                    }, $winners));
                                                    echo '</b>';
                                                } else {
                                                    $winner_option = $winners[0];
                                                    echo '<b>Winner: ' . esc_html($winner_option->text) . ' with ' . esc_html($winner_votes) . ' votes</b>';
                                                }
                                            }

                                            foreach ($voting_options_array as $option) {
                                                $votes = isset($vote_counts[$option->text]) ? $vote_counts[$option->text] : 0;
                                                $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;
                                                echo '<li>Option "' . esc_html($option->text) . '" received ' . esc_html($votes) . ' votes (' . round($percentage, 2) . '%)</li>';
                                            }
                                            foreach ($vote_box_array as $vote) {
                                                $userName = $vote['userName'];  // Get the voter's name
                                                $userVote = $vote['userVote'];  // Get the voter's vote

                                                // Display the user and their vote
                                                echo "<li>voted by {$userName} voted for: {$userVote}</li>";
                                            }


                                            echo '</ul>'; // Close round-description div
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>


                            <?php if ($voting_choice !== 'single') { ?>
                                <div class='result--box'>
                                    <div class="">

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
                                            echo '<div class="table--area"><table><thead><tr><th></th>';

                                            // Create table headers for each voting option
                                            foreach ($voting_options_array as $option) {
                                                echo '<th>' . esc_html($option->text) . '</th>';
                                            }

                                            echo '</tr></thead><tbody>';

                                            // Generate rows for the pairwise comparison results
                                            foreach ($voting_options_array as $row_option) {
                                                echo '<tr><td>' . esc_html($row_option->text) . '</td>';
                                                foreach ($voting_options_array as $col_option) {
                                                    // Show N/A for self-comparison
                                                    if ($row_option->text === $col_option->text) {
                                                        echo '<td>N/A</td>';
                                                    } else {
                                                        // Display the number of votes each candidate received in pairwise comparison
                                                        echo '<td>' . esc_html($pairwise[$row_option->text][$col_option->text]) . '</td>';
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

                                            // Display who voted for whom (only the first choice)
                                            echo '<ul>';
                                            // Display first-choice votes
                                            foreach ($first_choice_counts as $candidate => $count) {
                                                echo "<li>Option “" . esc_html($candidate) . "” received " . esc_html($count) . " first-choice votes.</li>";
                                            }
                                            foreach ($vote_box_array as $vote) {
                                                $userName = $vote['userName'];
                                                $firstChoice = $vote['userVote'][0]; // Get the first choice vote
                                                echo "<li>voted by $userName voted for: $firstChoice</li>";
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
                                            echo '</div>';
                                            ?>
                                        <?php endif; ?>








                                        <?php if ($voting_choice === 'irv') : ?>
                                            <?php
                                            // Initialize vote counts and other variables
                                            $rounds = [];
                                            $current_votes = [];
                                            $event_log = [];
                                            $winner = null;
                                            $election_invalid = false; // Track if the election is invalid

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
                                            $round_number = 1;
                                            while (true) {
                                                if (empty($current_votes)) {
                                                    break;
                                                }

                                                // Check if any candidate has more than 50% of the votes
                                                $total_votes = array_sum($current_votes);
                                                $max_votes = max($current_votes);
                                                $winner = array_search($max_votes, $current_votes);

                                                // Check for winner
                                                if ($max_votes > $total_votes / 2) {
                                                    $event_log[] = "Round $round_number: Winner \"$winner\" with $max_votes votes.";
                                                    break;
                                                }

                                                // Check if there's a tie (more than one candidate with the same minimum votes)
                                                $min_votes = min($current_votes);
                                                $eliminated_candidates = array_keys($current_votes, $min_votes);

                                                if (count($eliminated_candidates) == count($current_votes)) {
                                                    $election_invalid = true;
                                                    $event_log[] = "Election invalid. All options have the same number of votes.";
                                                    break;
                                                }

                                                // Log the elimination event for all candidates with the fewest votes
                                                foreach ($eliminated_candidates as $eliminated) {
                                                    $event_log[] = "Round $round_number: Option \"$eliminated\" had $min_votes votes for 1st choice and was eliminated.";
                                                    unset($current_votes[$eliminated]);
                                                }

                                                // Redistribute votes for eliminated candidates
                                                foreach ($vote_box_array as $vote) {
                                                    if (!empty($vote['userVote'])) {
                                                        foreach ($eliminated_candidates as $eliminated) {
                                                            if ($vote['userVote'][0] === $eliminated) {
                                                                array_shift($vote['userVote']); // Eliminate the first choice
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
                                                    }
                                                }

                                                // Store the round results
                                                $rounds[] = $current_votes;
                                                $round_number++; // Increment the round number
                                            }

                                            // Output the results in a table
                                            echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                            for ($i = 1; $i <= count($rounds); $i++) {
                                                echo "<th>$i</th>";
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
                                                echo '<tr><td>' . esc_html($candidate) . '</td>';
                                                foreach ($rounds as $round) {
                                                    echo '<td>' . (isset($round[$candidate]) ? esc_html($round[$candidate]) : 0) . '</td>';
                                                }
                                                echo '</tr>';
                                            }

                                            echo '  </tbody></table></div>';

                                            // Display the event log
                                            echo '<ul class="round-description">';
                                            echo '<h4>Results (Using IRV)</h4>';

                                            // Output the winner or election invalid message
                                            if ($election_invalid) {
                                                echo '<p class="fw-bold">Election invalid. No valid winner could be determined.</p>';
                                            } elseif ($winner) {
                                                echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($max_votes) . ' votes</p>';
                                            }

                                            // Log the round events with round number
                                            foreach ($event_log as $event) {
                                                echo '<li>' . esc_html($event) . '</li>';
                                            }

                                            echo '</ul>';
                                            ?>
                                        <?php endif; ?>






                                        <?php if ($voting_choice === 'stv') : ?>

                                            <?php
                                            // Number of seats to be filled
                                            // $num_seats = 1;
                                            $num_seats = $number_of_winner ? $number_of_winner : 1;

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
                                                // if (empty($current_votes)) {
                                                //     $results_log[] = "No candidates left to count votes.";

                                                //     break; // Exit the loop if no candidates remain
                                                // }


                                                if (empty($current_votes)) {
                                                    // Check if no candidates are left to count votes
                                                    // Prepare the message to indicate no candidates are left
                                                    $results_log[] = "No candidates left to count votes.";
                                                    $vote_counts = [];
                                                    error_log('vote_box_array');
                                                    error_log(print_r($vote_box_array, true));
                                                    error_log('vote_box_array_new');
                                                    error_log(print_r($vote_box_array_new, true));

                                                    foreach ($vote_box_array as $vote) {
                                                        error_log(print_r($vote, true));
                                                        $first_choice = $vote['userVote'][0];
                                                        if (!isset($vote_counts[$first_choice])) {
                                                            $vote_counts[$first_choice] = ['votes' => 0];
                                                        }
                                                        $vote_counts[$first_choice]['votes']++;
                                                    }


                                                    $max_votes = !empty($vote_counts) ? max(array_column($vote_counts, 'votes')) : 0;


                                                    // Optionally check for ties among remaining candidates before breaking the loop
                                                    $tied_candidates = array_filter($vote_counts, function ($vote) use ($max_votes) {
                                                        return $vote['votes'] === $max_votes;
                                                    });

                                                    if (count($tied_candidates) === 1) {
                                                        $winner = key($tied_candidates);  // Get the name of the winning candidate
                                                        $results_log[] = "The winner is $winner with $max_votes votes.";
                                                    } else if (count($tied_candidates) > 1) {
                                                        // Handle tie situation with multiple tied candidates
                                                        error_log("tetsing owbn result message");
                                                        error_log(print_r($tied_candidates, true));
                                                        $tied_names = implode(" and ", array_keys($tied_candidates));
                                                        $results_log[] = "This would end up with a tie vote between $tied_names. The Head Coordinator breaks the tie.";
                                                    } else {
                                                        // Handle the case where there are no tied candidates, election concluded with no remaining candidates
                                                        $results_log[] = "Election concluded with no remaining candidates. No Winner, There are no remaining candidates to declare a winner, and the Head Coordinator will break the tie.";
                                                        error_log("No candidates found in the tie check.");
                                                    }

                                                    // if (count($tied_candidates) === 1) {
                                                    //     $winner = key($tied_candidates);  // Get the name of the winning candidate
                                                    //     $results_log[] = "The winner is $winner with $max_votes votes.";
                                                    // } else {
                                                    //     // Handle tie situation
                                                    //     $tied_names = implode(" and ", array_keys($tied_candidates));
                                                    //     $results_log[] = "This would end up with a tie vote between $tied_names. The Head Coordinator breaks the tie.";
                                                    // }

                                                    // Exit the loop if no candidates remain or if there is a tie
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
                                                        foreach ($vote_box_array as $vote) {
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
                                            echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                            for ($i = 1; $i <= count($rounds); $i++) {
                                                echo "<th>$i</th>";
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
                                                echo '<tr><td>' . esc_html($candidate) . '</td>';
                                                foreach ($rounds as $round_data) {
                                                    echo '<td>' . (isset($round_data[$candidate]) ? esc_html($round_data[$candidate]) : 0) . '</td>';
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
                                            ?>

                                        <?php endif; ?>



                                        <!-- working code and well structured -->
                                        <?php
                                        // if ($voting_choice === 'punishment') :
                                        //     // List of punishments
                                        //     $punishments = [
                                        //         'Permanent Ban',
                                        //         'Indefinite Ban/3 Strikes',
                                        //         'Temporary Ban',
                                        //         '2 Strikes',
                                        //         '1 Strike',
                                        //         'Probation',
                                        //         'Censure',
                                        //         'Condemnation',
                                        //     ];

                                        //     // Initialize vote counts for the first round
                                        //     $current_votes = array_fill_keys($punishments, 0);
                                        //     $rounds = [];  // Store each round's vote count

                                        //     // Count initial votes for first choice
                                        //     foreach ($vote_box_array as $vote) {
                                        //         if (!empty($vote['userVote'])) {
                                        //             $first_choice = $vote['userVote'][0]; // Get the first choice
                                        //             if (isset($current_votes[$first_choice])) {
                                        //                 $current_votes[$first_choice]++;
                                        //             }
                                        //         }
                                        //     }

                                        //     // Store first round result
                                        //     $rounds[] = $current_votes;

                                        //     // Total votes and majority threshold
                                        //     $total_votes = count($vote_box_array);
                                        //     $majority_threshold = $total_votes * 0.51;

                                        //     // Check if the first punishment has less than 51% and transfer votes if necessary
                                        //     $first_punishment = key($current_votes);
                                        //     $first_punishment_votes = $current_votes[$first_punishment];
                                        //     // $first_punishment_percentage = ($first_punishment_votes / $total_votes) * 100;
                                        //     if ($total_votes > 0) {
                                        //         $first_punishment_percentage = ($first_punishment_votes / $total_votes) * 100;
                                        //     } else {
                                        //         $first_punishment_percentage = 0; // Set percentage to 0 if no votes
                                        //     }

                                        //     // If first punishment has less than 51%, transfer votes to the second punishment
                                        //     if ($first_punishment_percentage < 51) {
                                        //         $second_punishment = $punishments[1]; // The next punishment in the list
                                        //         $current_votes[$second_punishment] += $first_punishment_votes; // Add the votes to the second punishment
                                        //         $current_votes[$first_punishment] = 0; // Reset the first punishment's votes

                                        //         $event_log[] = "Votes from \"$first_punishment\" (less than 51%) are transferred to \"$second_punishment\".";

                                        //         // Store the result after transferring votes
                                        //         $rounds[] = $current_votes;

                                        //         // Check if the second punishment now has more than 51% of the votes
                                        //         $second_punishment_votes = $current_votes[$second_punishment];
                                        //         if ($total_votes > 0) {
                                        //             $second_punishment_percentage = ($first_punishment_votes / $total_votes) * 100;
                                        //         } else {
                                        //             $second_punishment_percentage = 0; // Set percentage to 0 if no votes
                                        //         }

                                        //         $second_punishment_percentage = ($second_punishment_votes / $total_votes) * 100;

                                        //         if ($second_punishment_percentage > 51) {
                                        //             $winner = $second_punishment;
                                        //             $event_log[] = "Winner: \"$winner\" with $second_punishment_votes votes ($second_punishment_percentage%)";
                                        //         }
                                        //     }

                                        //     // Output the results in a table
                                        //     echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                        //     for ($i = 1; $i <= count($rounds); $i++) {
                                        //         echo "<th>Round $i</th>";
                                        //     }
                                        //     echo '  </tr></thead><tbody>';

                                        //     // Display the rounds' results
                                        //     foreach ($punishments as $punishment) {
                                        //         echo '<tr><td>' . esc_html($punishment) . '</td>';
                                        //         foreach ($rounds as $round) {
                                        //             $round_total_votes = array_sum($round); // Calculate the total votes in the current round
                                        //             if ($round_total_votes > 0 && isset($round[$punishment])) {
                                        //                 $percentage = number_format(($round[$punishment] / $round_total_votes) * 100, 2); // Using 2 decimal points
                                        //             } else {
                                        //                 $percentage = 0; // If no votes, percentage is 0
                                        //             }
                                        //             echo '<td>' . esc_html($percentage) . '%</td>';
                                        //         }
                                        //         echo '</tr>';
                                        //     }

                                        //     echo '</tbody></table></div>';

                                        //     // Display the event log
                                        //     echo '<ul class="round-description">';
                                        //     echo '<h4>Results</h4>';
                                        //     if ($winner) {
                                        //         echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($current_votes[$winner]) . ' votes (' . esc_html(number_format($second_punishment_percentage, 2)) . '%)</p>';
                                        //     } else {
                                        //         echo '<p class="fw-bold">No winner determined.</p>';
                                        //     }

                                        //     foreach ($event_log as $event) {
                                        //         echo '<li>' . esc_html($event) . '</li>';
                                        //     }

                                        //     echo '</ul>';
                                        // endif;
                                        ?>
                                        <?php
                                        // if ($voting_choice === 'punishment') :
                                        //     // List of punishments
                                        //     $punishments = [
                                        //         'Permanent Ban',
                                        //         'Indefinite Ban/3 Strikes',
                                        //         'Temporary Ban',
                                        //         '2 Strikes',
                                        //         '1 Strike',
                                        //         'Probation',
                                        //         'Censure',
                                        //         'Condemnation',
                                        //     ];

                                        //     // Initialize vote counts for the first round
                                        //     $current_votes = array_fill_keys($punishments, 0);
                                        //     $rounds = [];  // Store each round's vote count
                                        //     $event_log = []; // Log events during the rounds
                                        //     $total_votes = count($vote_box_array);  // Total votes
                                        //     $majority_threshold = $total_votes * 0.51; // Majority threshold

                                        //     // Count initial votes for the first choice
                                        //     foreach ($vote_box_array as $vote) {
                                        //         if (!empty($vote['userVote'])) {
                                        //             $first_choice = $vote['userVote'][0]; // Get the first choice
                                        //             if (isset($current_votes[$first_choice])) {
                                        //                 $current_votes[$first_choice]++;
                                        //             }
                                        //         }
                                        //     }

                                        //     // Store first round result
                                        //     $rounds[] = $current_votes;

                                        //     // Start with the first punishment and check if it meets the 51% threshold
                                        //     $punishment_index = 0;
                                        //     $winner = null;

                                        //     while ($punishment_index < count($punishments)) {
                                        //         $current_punishment = $punishments[$punishment_index];
                                        //         $current_punishment_votes = $current_votes[$current_punishment];

                                        //         // Calculate the percentage for the current punishment
                                        //         $current_punishment_percentage = ($total_votes > 0) ? ($current_punishment_votes / $total_votes) * 100 : 0;

                                        //         // Check if the current punishment has more than 51% of the votes
                                        //         if ($current_punishment_percentage > 51) {
                                        //             $winner = $current_punishment;
                                        //             $event_log[] = "Winner: \"$winner\" with $current_punishment_votes votes ($current_punishment_percentage%)";
                                        //             break; // Exit the loop if a winner is found
                                        //         } else {
                                        //             // If the current punishment doesn't meet the majority, transfer its votes to the next punishment
                                        //             if ($punishment_index + 1 < count($punishments)) {
                                        //                 $next_punishment = $punishments[$punishment_index + 1]; // Get the next punishment
                                        //                 $current_votes[$next_punishment] += $current_punishment_votes; // Add votes to the next punishment
                                        //                 $current_votes[$current_punishment] = 0; // Reset the current punishment's votes

                                        //                 $event_log[] = "Votes from \"$current_punishment\" (less than 51%) are transferred to \"$next_punishment\".";

                                        //                 // Store the result after transferring votes
                                        //                 $rounds[] = $current_votes;

                                        //                 // Move to the next punishment
                                        //                 $punishment_index++;
                                        //             } else {
                                        //                 // If we've gone through all punishments and no one has won, end the loop
                                        //                 $event_log[] = "No winner found after all punishments have been considered.";
                                        //                 break;
                                        //             }
                                        //         }
                                        //     }

                                        //     // Output the results in a table
                                        //     echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                        //     for ($i = 1; $i <= count($rounds); $i++) {
                                        //         echo "<th>Round $i</th>";
                                        //     }
                                        //     echo '  </tr></thead><tbody>';

                                        //     // Display the rounds' results
                                        //     foreach ($punishments as $punishment) {
                                        //         echo '<tr><td>' . esc_html($punishment) . '</td>';
                                        //         foreach ($rounds as $round) {
                                        //             $round_total_votes = array_sum($round); // Calculate the total votes in the current round
                                        //             $percentage = ($round_total_votes > 0 && isset($round[$punishment])) ? number_format(($round[$punishment] / $round_total_votes) * 100, 2) : 0;
                                        //             echo '<td>' . esc_html($percentage) . '%</td>';
                                        //         }
                                        //         echo '</tr>';
                                        //     }

                                        //     echo '</tbody></table></div>';

                                        //     // Display the event log
                                        //     echo '<ul class="round-description">';
                                        //     echo '<h4>Results</h4>';
                                        //     if ($winner) {
                                        //         echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($current_votes[$winner]) . ' votes (' . esc_html(number_format($current_punishment_percentage, 2)) . '%)</p>';
                                        //     } else {
                                        //         echo '<p class="fw-bold">No winner determined.</p>';
                                        //     }

                                        //     foreach ($event_log as $event) {
                                        //         echo '<li>' . esc_html($event) . '</li>';
                                        //     }

                                        //     echo '</ul>';
                                        // endif;
                                        ?>

                                        <!-- testing votedby -->



                                        <?php
                                        // if ($voting_choice === 'punishment') :
                                        //     // List of punishments
                                        //     $punishments = [
                                        //         'Permanent Ban',
                                        //         'Indefinite Ban/3 Strikes',
                                        //         'Temporary Ban',
                                        //         '2 Strikes',
                                        //         '1 Strike',
                                        //         'Probation',
                                        //         'Censure',
                                        //         'Condemnation',
                                        //     ];

                                        //     // Initialize vote counts for the first round
                                        //     $current_votes = array_fill_keys($punishments, 0);
                                        //     $user_votes = array_fill_keys($punishments, []); // To store users' names per punishment
                                        //     $rounds = [];  // Store each round's vote count
                                        //     $event_log = []; // Log events during the rounds
                                        //     $total_votes = count($vote_box_array);  // Total votes
                                        //     $majority_threshold = $total_votes * 0.51; // Majority threshold

                                        //     // Count initial votes for the first choice and store user names
                                        //     foreach ($vote_box_array as $vote) {
                                        //         if (!empty($vote['userVote'])) {
                                        //             $first_choice = $vote['userVote'][0]; // Get the first choice
                                        //             $user_name = isset($vote['userName']) ? $vote['userName'] : 'Anonymous'; // User's name (assuming it's available)
                                        //             if (isset($current_votes[$first_choice])) {
                                        //                 $current_votes[$first_choice]++;
                                        //                 $user_votes[$first_choice][] = $user_name; // Store the user name for the vote
                                        //             }
                                        //         }
                                        //     }

                                        //     // Store first round result
                                        //     $rounds[] = $current_votes;

                                        //     // Start with the first punishment and check if it meets the 51% threshold
                                        //     $punishment_index = 0;
                                        //     $winner = null;

                                        //     while ($punishment_index < count($punishments)) {
                                        //         $current_punishment = $punishments[$punishment_index];
                                        //         $current_punishment_votes = $current_votes[$current_punishment];

                                        //         // Calculate the percentage for the current punishment
                                        //         $current_punishment_percentage = ($total_votes > 0) ? ($current_punishment_votes / $total_votes) * 100 : 0;

                                        //         // Check if the current punishment has more than 51% of the votes
                                        //         if ($current_punishment_percentage > 51) {
                                        //             $winner = $current_punishment;
                                        //             $event_log[] = "Winner: \"$winner\" with $current_punishment_votes votes ($current_punishment_percentage%)";
                                        //             break; // Exit the loop if a winner is found
                                        //         } else {
                                        //             // If the current punishment doesn't meet the majority, transfer its votes to the next punishment
                                        //             if ($punishment_index + 1 < count($punishments)) {
                                        //                 $next_punishment = $punishments[$punishment_index + 1]; // Get the next punishment
                                        //                 $current_votes[$next_punishment] += $current_punishment_votes; // Add votes to the next punishment
                                        //                 $current_votes[$current_punishment] = 0; // Reset the current punishment's votes
                                        //                 $user_votes[$next_punishment] = array_merge($user_votes[$next_punishment], $user_votes[$current_punishment]); // Transfer users
                                        //                 $user_votes[$current_punishment] = []; // Clear the current punishment's user list

                                        //                 $event_log[] = "Votes from \"$current_punishment\" (less than 51%) are transferred to \"$next_punishment\".";

                                        //                 // Store the result after transferring votes
                                        //                 $rounds[] = $current_votes;

                                        //                 // Move to the next punishment
                                        //                 $punishment_index++;
                                        //             } else {
                                        //                 // If we've gone through all punishments and no one has won, end the loop
                                        //                 $event_log[] = "No winner found after all punishments have been considered.";
                                        //                 break;
                                        //             }
                                        //         }
                                        //     }

                                        //     // Output the results in a table
                                        //     echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                        //     for ($i = 1; $i <= count($rounds); $i++) {
                                        //         echo "<th>Round $i</th>";
                                        //     }
                                        //     echo '  </tr></thead><tbody>';

                                        //     // Display the rounds' results
                                        //     foreach ($punishments as $punishment) {
                                        //         echo '<tr><td>' . esc_html($punishment) . '</td>';
                                        //         foreach ($rounds as $round) {
                                        //             $round_total_votes = array_sum($round); // Calculate the total votes in the current round
                                        //             $percentage = ($round_total_votes > 0 && isset($round[$punishment])) ? number_format(($round[$punishment] / $round_total_votes) * 100, 2) : 0;
                                        //             echo '<td>' . esc_html($percentage) . '%</td>';
                                        //         }
                                        //         echo '</tr>';
                                        //     }

                                        //     echo '</tbody></table></div>';

                                        //     // Display the event log
                                        //     echo '<ul class="round-description">';
                                        //     echo '<h4>Results</h4>';
                                        //     if ($winner) {
                                        //         echo '<p class="fw-bold">Winner: ' . esc_html($winner) . ' with ' . esc_html($current_votes[$winner]) . ' votes (' . esc_html(number_format($current_punishment_percentage, 2)) . '%)</p>';
                                        //     } else {
                                        //         echo '<p class="fw-bold">No winner determined.</p>';
                                        //     }

                                        //     foreach ($event_log as $event) {
                                        //         echo '<li>' . esc_html($event) . '</li>';
                                        //     }

                                        //     // Display the users who voted for each punishment
                                        //     echo '<h4>Voters for Each Punishment</h4>';
                                        //     foreach ($user_votes as $punishment => $users) {
                                        //         echo '<p><strong>' . esc_html($punishment) . ':</strong> ';
                                        //         echo implode(', ', $users) ?: 'No votes';
                                        //         echo '</p>';
                                        //     }

                                        //     echo '</ul>';
                                        // endif;
                                        ?>




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
                                            echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                            for ($i = 1; $i <= count($rounds); $i++) {
                                                echo "<th>Round $i</th>";
                                            }
                                            echo '  </tr></thead><tbody>';

                                            // Display the rounds' results
                                            foreach ($punishments as $punishment) {
                                                echo '<tr><td>' . esc_html($punishment) . '</td>';
                                                foreach ($rounds as $round) {
                                                    $round_total_votes = array_sum($round); // Calculate the total votes in the current round
                                                    $percentage = ($round_total_votes > 0 && isset($round[$punishment])) ? number_format(($round[$punishment] / $round_total_votes) * 100, 2) : 0;
                                                    echo '<td>' . esc_html($percentage) . '%</td>';
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
                                        endif;
                                        ?>









                                   
                                       


                                    



                                    </div>

                                </div>
                            <?php } ?>
                            <?php
}
?>


                            <div class="table--part--box">

                                <?php

                                // echo '##########################################';
                                // echo '</pre>';
                                // print_r($vote_box_array);
                                // echo '</pre>';
                                ?>
                                <!-- vote details -->
                                <?php if ($voting_choice === 'condorcet'   || $voting_choice === 'single') { ?>
                                    <div class="table--area">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Chronicle</th>
                                                    <th>Voted for</th>
                                                    <th>Comment</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (is_array($vote_box_array)) {
                                                    foreach ($vote_box_array as $option) {
                                                        //print_r($option);
                                                        $user = get_user_by('id', $option['userId']);

                                                ?>
                                                        <tr>
                                                            <td><a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>
															<?php //echo get_author_posts_url($option['userId']); 
                                                            ?>">
                                                                    <?php echo esc_html($option['userName']); ?></a>
                                                            </td>
                                                            <td>
                                                                <?php

                                                                if ($voting_choice == 'single') {
                                                                    echo esc_html($option['userVote']);
                                                                } else {
                                                                    if (is_array($option['userVote'])) {
                                                                        echo '<ol>'; // Start ordered list
                                                                        foreach ($option['userVote'] as $vote) {
                                                                            echo '<li>' . esc_html($vote) . '</li>';
                                                                        }
                                                                        echo '</ol>'; // End ordered list
                                                                    } else {
                                                                        echo esc_html($option['userVote']); // Fallback in case userVote is not an array
                                                                    }
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo esc_html($option['userComment']); ?></td>
                                                        </tr>
                                                <?php
                                                    }
                                                } else {
                                                    // Handle the case where $vote_box_array is not a valid array
                                                    echo '<tr><td colspan="2">Invalid vote data.</td></tr>';
                                                }
                                                ?>


                                            </tbody>
                                        </table>

                                    </div>
                                    <!-- vote recieved and not receved users -->
                                    <div class="table--area voteReceive_notReceive">
                                        <table class="styled-table">
                                            <thead>
                                                <tr>
                                                    <th>Votes Received</th>
                                                    <th>Votes NOT Received</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <?php if (is_array($vote_box_array_new)) {   ?>
                                                            <ul>
                                                                <?php foreach ($vote_box_array_new as $option) {
                                                                    $user = get_user_by('id', $option['userId']);

                                                                ?>
                                                                    <li><a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>"><?php echo esc_html($user->display_name); ?></a></a></li>
                                                                <?php } ?>
                                                            </ul>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php if (is_array($users_not_voted)) {  ?>
                                                            <ul>
                                                                <?php foreach ($users_not_voted as $user) { ?>
                                                                    <li><a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>"><?php echo esc_html($user->display_name); ?></a></li>
                                                                <?php } ?>
                                                            </ul>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                    </div>
                                <?php } elseif ($voting_choice === 'irv' || $voting_choice === 'stv' || $voting_choice === 'punishment') { ?>
                                    <div class="table--area">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Chronicle</th>
                                                    <th>Voted for</th>
                                                    <th>Comment</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php

                                                if (is_array($vote_box_array_new)) {
                                                    foreach ($vote_box_array_new as $option) {
                                                        if (is_array($option)) { // Check if $option is an array
                                                            $user = get_user_by('id', $option['userId']);
                                                ?>
                                                            <tr>
                                                                <td>
                                                                    <a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>">
                                                                        <?php echo esc_html($option['userName']); ?>
                                                                    </a>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    if ($voting_choice == 'single') {
                                                                        echo esc_html($option['userVote']);
                                                                    } else {
                                                                        if (is_array($option['userVote'])) {
                                                                            echo '<ol>'; // Start ordered list
                                                                            foreach ($option['userVote'] as $vote) {
                                                                                echo '<li>' . esc_html($vote) . '</li>';
                                                                            }
                                                                            echo '</ol>'; // End ordered list
                                                                        } else {
                                                                            echo esc_html($option['userVote']); // Fallback in case userVote is not an array
                                                                        }
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td><?php echo esc_html($option['userComment']); ?></td>
                                                            </tr>
                                                <?php
                                                        } else {
                                                            echo '<tr><td colspan="3">Invalid vote option format. Expected array, got: ' . gettype($option) . '</td></tr>';
                                                        }
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="3">Invalid vote data. Expected array, got: ' . gettype($vote_box_array) . '</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Vote received and not received users -->
                                    <div class="table--area voteReceive_notReceive ">
                                        <table class="styled-table">
                                            <thead>
                                                <tr>
                                                    <th>Votes Received</th>
                                                    <th>Votes NOT Received</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <?php if (is_array($vote_box_array_new)) { ?>
                                                            <ul>
                                                                <?php foreach ($vote_box_array_new as $option) {
                                                                    if (is_array($option)) { // Check if $option is an array
                                                                        $user = get_user_by('id', $option['userId']);
                                                                ?>
                                                                        <li><a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>"><?php echo esc_html($user->display_name); ?></a></li>
                                                                <?php
                                                                    } else {
                                                                        echo '<li>Invalid vote option format. Expected array, got: ' . gettype($option) . '</li>';
                                                                    }
                                                                } ?>
                                                            </ul>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php if (is_array($users_not_voted)) { ?>
                                                            <ul>
                                                                <?php foreach ($users_not_voted as $user) { ?>
                                                                    <li><a href="<?php echo site_url(); ?>/chronicles/<?php echo esc_html($user->user_login); ?>"><?php echo esc_html($user->display_name); ?></a></li>
                                                                <?php } ?>
                                                            </ul>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                <?php } ?>









                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>


        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // document.addEventListener("DOMContentLoaded", function() {
        //     const ctx = document.getElementById('voteChart').getContext('2d');

        //     // Get PHP data and parse JSON
        //      const voteData = <?php
                                    //      echo $vote_data_json; 
                                    //     
                                    ?>;


        //     const labels = Object.keys(voteData); // Vote options
        //     const dataValues = Object.values(voteData); // Vote counts
        //     const maxValue = Math.ceil(Math.max(...dataValues) / 5) * 5; // Round to nearest multiple of 5

        //     new Chart(ctx, {
        //         type: 'bar',
        //         data: {
        //             labels: labels,
        //             datasets: [
        //                 {
        //                     type: 'bar',
        //                     label: 'Votes Received',
        //                     data: dataValues,
        //                     backgroundColor: '#b32017',
        //                     borderColor: '#ddd',
        //                     borderWidth: 1,
        //                     barThickness: 30,
        //                     barPercentage: 0.6, // Adjusted bar width
        //                     categoryPercentage: 0.6 // Adjusted space between bars
        //                 },
        //                 {
        //                     type: 'line',
        //                     label: 'Vote Trend',
        //                     data: dataValues,
        //                     borderColor: '#b32017',
        //                     borderWidth: 2,
        //                     fill: false,
        //                     pointBackgroundColor: '#b32017',
        //                     pointRadius: 6, // Slightly larger points
        //                     pointHoverRadius: 8 // Enlarged on hover
        //                 }
        //             ]
        //         },
        //         options: {
        //             responsive: true,
        //             plugins: {
        //                 legend: {
        //                     labels: {
        //                         color: "#ffffff", // Change legend text color
        //                         font: {
        //                             size: 14,
        //                             weight: "bold"
        //                         }
        //                     }
        //                 }
        //             },
        //             scales: {
        //                 x: {
        //                     ticks: {
        //                         color: "#ffffff", // X-axis label color
        //                         font: {
        //                             size: 12
        //                         }
        //                     },
        //                     grid: {
        //                         color: "rgba(255, 255, 255, 0.2)", // X-axis grid color
        //                         drawBorder: true
        //                     },
        //                     categoryPercentage: 0.6,
        //                     barPercentage: 0.6
        //                 },
        //                 y: {
        //                     beginAtZero: true,
        //                     ticks: {
        //                         color: "#ffffff", // Y-axis label color
        //                         font: {
        //                             size: 12
        //                         },
        //                         stepSize: 2, // Set y-axis step size to 5
        //                         max: maxValue
        //                     },
        //                     grid: {
        //                         color: "rgba(255, 255, 255, 0.2)", // Y-axis grid color
        //                         drawBorder: true
        //                     }
        //                 }
        //             }
        //         }
        //     });
        // });

        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('voteChart').getContext('2d');

            // Get PHP data and parse JSON
            // const voteData = <?php
                                // echo $vote_data_json; 
                                ?>;
            const voteData = JSON.parse('<?php echo $voteDataJson; ?>');

            const labels = Object.keys(voteData); // Vote options
            const dataValues = Object.values(voteData); // Vote counts
            const maxValue = Math.ceil(Math.max(...dataValues) / 5) * 5; // Round to nearest multiple of 5

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            type: 'bar',
                            label: 'Votes Received',
                            data: dataValues,
                            backgroundColor: '#b32017',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            barThickness: 30,
                            barPercentage: 0.6, // Adjusted bar width
                            categoryPercentage: 0.6 // Adjusted space between bars
                        },
                        {
                            type: 'line',
                            label: 'Vote Trend',
                            data: dataValues,
                            borderColor: '#b32017',
                            borderWidth: 2,
                            fill: false,
                            pointBackgroundColor: '#b32017',
                            pointRadius: 6, // Slightly larger points
                            pointHoverRadius: 8 // Enlarged on hover
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: "#ffffff", // Change legend text color
                                font: {
                                    size: 14,
                                    weight: "bold"
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: "#ffffff", // X-axis label color
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: "rgba(255, 255, 255, 0.2)", // X-axis grid color
                                drawBorder: true
                            },
                            categoryPercentage: 0.6,
                            barPercentage: 0.6
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: "#ffffff", // Y-axis label color
                                font: {
                                    size: 12
                                },
                                stepSize: 2, // Set y-axis step size to 5
                                max: maxValue
                            },
                            grid: {
                                color: "rgba(255, 255, 255, 0.2)", // Y-axis grid color
                                drawBorder: true
                            }
                        }
                    }
                }
            });
        });
    </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var votingOptions = <?php echo $voting_options_json; ?>;
                var votingc = '<?php echo $voting_choice; ?>';

                console.log(votingc)

            })
            document.addEventListener('DOMContentLoaded', function() {
                const messageDiv = document.getElementById('responseMessage');
                if (messageDiv) {
                    setTimeout(() => {
                        messageDiv.style.display = 'none'; // Hide the message after 5 seconds
                    }, 5000);
                }
            });
        </script>
<?php

        return ob_get_clean();
    }
}
add_shortcode('voting_result', 'voting_result_shortcode');
