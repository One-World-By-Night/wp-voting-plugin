<?php
function voting_result_shortcode()
{
    if (!isset($_GET['id'])) {
        wp_die('Page not accessable');
    }
    $vote_data = isset($_GET['id']) !== null ? get_vote_data_by_id($_GET['id']) : null;
    if (empty($vote_data)) {
        wp_die('Vote not found');
    }
    if ($vote_data['voting_stage'] != 'completed') {
        wp_die('Voting is not completed');
    }

    // Check if the user is logged in and if the user has the 'administrator' or 'author' role
    if ($vote_data['visibility'] == 'council' && (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author')))) {
        wp_die('You do not have permission to access this page.');
    } else {
        $vote_id = isset($_GET['id']) ? intval($_GET['id']) : null;
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
                $author_users = get_users(array('role' => 'author'));

                $all_admin_users = array_merge($all_admin_users, $admin_users);
                $all_author_users = array_merge($all_author_users, $author_users);
                $all_users = array_merge($all_users, $admin_users, $author_users);

                restore_current_blog();
            }

            $total_eligible_voters = count($all_admin_users) + count($all_author_users);
        } else {
            // Single site: Collect admins and authors
            $admin_users = get_users(array('role' => 'administrator'));
            $author_users = get_users(array('role' => 'author'));

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

        ob_start();

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

                            <!-- single vote -->
                            <!-- <?php if ($voting_choice === 'single') : ?>
                                <div class="result--box progress-bar">
                                    <div class="progress-wrappeer">
                                        <?php
                                        if ($voting_options_array) {
                                            // Total number of votes
                                            $total_votes = count($vote_box_array);

                                            // Initialize vote count for each option
                                            $vote_counts = array();

                                            foreach ($voting_options_array as $option) {
                                                $vote_counts[$option->text] = 0;
                                            }

                                            // Count votes for each option
                                            foreach ($vote_box_array as $vote) {
                                                if (isset($vote_counts[$vote['userVote']])) {
                                                    $vote_counts[$vote['userVote']]++;
                                                }
                                            }

                                            // Sort the options by vote count in descending order (winner on top)
                                            usort($voting_options_array, function ($a, $b) use ($vote_counts) {
                                                return $vote_counts[$b->text] <=> $vote_counts[$a->text];
                                            });

                                            $colors = ['#42224F', '#5A3D99', '#732EBF', '#8B3DD6', '#A14DF3'];

                                            // Function to generate a random color
                                            function random_colorr()
                                            {
                                                return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                                            }

                                            // Output voting results
                                            foreach ($voting_options_array as $index => $option) {
                                                $votes = isset($vote_counts[$option->text]) ? $vote_counts[$option->text] : 0;
                                                $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;
                                                $color = $index < count($colors) ? $colors[$index] : random_colorr();
                                        ?>
                                                <div class="poll">
                                                    <div class="label"><?php echo esc_html($option->text); ?></div>
                                                    <div class="bar"><span class="barline" style="width:<?php echo $percentage; ?>%; background:<?php echo $color; ?>;"></span>
                                                    </div>
                                                    <div class="percent"><?php echo round($percentage, 2); ?>% (<?php echo $votes; ?> votes)
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?> -->


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
                                                $voter_name = $vote['userName']; // Assuming each vote has a 'userName' field
                                                if (isset($vote_counts[$voted_option])) {
                                                    $vote_counts[$voted_option]++;
                                                    $voters_for_option[$voted_option][] = $voter_name; // Add voter to the option
                                                }
                                            }

                                            // Sort the options by vote count in descending order (winner on top)
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

                                            // Output voting results first
                                            foreach ($voting_options_array as $index => $option) {
                                                // Get the number of votes and calculate the percentage
                                                $votes = isset($vote_counts[$option->text]) ? $vote_counts[$option->text] : 0;
                                                $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;

                                                // Assign color to each bar
                                                $color = $index < count($colors) ? $colors[$index] : random_color();
                                        ?>
                                                <div class="poll">
                                                    <!-- Option label -->
                                                    <div class="label"><?php echo esc_html($option->text); ?></div>

                                                    <!-- Progress bar showing percentage -->
                                                    <div class="bar">
                                                        <span class="barline" style="width:<?php echo $percentage; ?>%; background:<?php echo $color; ?>;"></span>
                                                    </div>

                                                    <!-- Display percentage and vote count -->
                                                    <div class="percent"><?php echo round($percentage, 2); ?>% (<?php echo $votes; ?> votes)</div>
                                                </div>
                                        <?php
                                            }

                                            // Display the description after the results
                                            echo '<ul class="round-description">';
                                            echo '<h4>Results (Using Normal)</h4>';

                                            foreach ($voting_options_array as $option) {
                                                // Get the number of votes and calculate the percentage
                                                $votes = isset($vote_counts[$option->text]) ? $vote_counts[$option->text] : 0;
                                                $percentage = ($total_votes > 0) ? ($votes / $total_votes) * 100 : 0;

                                                // Get the list of voters for this option
                                                $voters = isset($voters_for_option[$option->text]) ? implode(', ', $voters_for_option[$option->text]) : 'No voters';

                                                // Description for each option
//                                                 echo '<li>Option "' . esc_html($option->text) . '" received ' . esc_html($votes) . ' votes (' . round($percentage, 2) . '%) from voters: ' . esc_html($voters) . '.</li>';
                                                echo '<li>Option "' . esc_html($option->text) . '" received ' . esc_html($votes) . ' votes (' . round($percentage, 2) . '%)</li>';
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
                                        <!-- condorcet -->
                                        <!-- <?php if ($voting_choice === 'condorcet') : ?>
                                    <?php

                                                    // Initialize pairwise comparison array
                                                    $pairwise = [];
                                                    foreach ($voting_options_array as $option) {
                                                        foreach ($voting_options_array as $other_option) {
                                                            if ($option->text !== $other_option->text) {
                                                                $pairwise[$option->text][$other_option->text] = 0;
                                                            }
                                                        }
                                                    }

                                                    // Perform pairwise comparisons
                                                    foreach ($vote_box_array as $vote) {
                                                        $preferences = $vote['userVote'];
                                                        foreach ($preferences as $i => $candidate) {
                                                            for ($j = $i + 1; $j < count($preferences); $j++) {
                                                                $pairwise[$candidate][$preferences[$j]]++;
                                                            }
                                                        }
                                                    }

                                                    // Determine the Condorcet winner
                                                    $condorcet_winner = null;
                                                    foreach ($voting_options_array as $option) {
                                                        $candidate = $option->text;
                                                        $is_winner = true;
                                                        foreach ($voting_options_array as $other_option) {
                                                            $other_candidate = $other_option->text;
                                                            if ($candidate !== $other_candidate && $pairwise[$other_candidate][$candidate] > $pairwise[$candidate][$other_candidate]) {
                                                                $is_winner = false;
                                                                break;
                                                            }
                                                        }
                                                        if ($is_winner) {
                                                            $condorcet_winner = $candidate;
                                                            break;
                                                        }
                                                    }

                                                    // If "England" is the winner, move it to the top of the array
                                                    if ($condorcet_winner === 'England') {
                                                        usort($voting_options_array, function ($a, $b) {
                                                            return $a->text === 'England' ? -1 : ($b->text === 'England' ? 1 : 0);
                                                        });
                                                    }

                                                    // Display the winner on top
                                                    echo '<div class="result--box"><h4>Condorcet Winner</h4><ul>';
                                                    if ($condorcet_winner) {
                                                        echo '<li>Winner: <strong>' . esc_html($condorcet_winner) . '</strong></li>';
                                                    } else {
                                                        echo '<li>No Condorcet Winner (possible cycle)</li>';
                                                    }
                                                    echo '</ul></div>';

                                                    // Display pairwise comparison results in table format
                                                    echo '<div class="table--area"><table><thead><tr><th></th>';

                                                    foreach ($voting_options_array as $option) {
                                                        echo '<th>' . esc_html($option->text) . '</th>';
                                                    }

                                                    echo '      </tr></thead><tbody>';

                                                    foreach ($voting_options_array as $row_option) {
                                                        // Highlight the winner row if it exists
                                                        $row_class = ($condorcet_winner && $row_option->text === $condorcet_winner) ?: '';
                                                        echo '<tr ' . $row_class . '><td>' . esc_html($row_option->text) . '</td>';
                                                        foreach ($voting_options_array as $col_option) {
                                                            if ($row_option->text === $col_option->text) {
                                                                echo '<td>N/A</td>';
                                                            } else {
                                                                echo '<td>' . esc_html($pairwise[$row_option->text][$col_option->text]) . '</td>';
                                                            }
                                                        }
                                                        echo '</tr>';
                                                    }

                                                    echo '      </tbody></table></div>';
                                    ?>
                                <?php endif; ?> -->
                                        <!-- irv -->
                                        <!-- <?php if ($voting_choice === 'irv') : ?>
                                    <?php


                                                    // Initialize vote counts
                                                    $rounds = [];
                                                    $current_votes = [];

                                                    // Initialize counts for the first round
                                                    foreach ($voting_options_array as $option) {
                                                        $current_votes[$option->text] = 0;
                                                    }

                                                    // Count initial votes
                                                    foreach ($vote_box_array as $vote) {
                                                        $first_choice = $vote['userVote'][0];
                                                        if (isset($current_votes[$first_choice])) {
                                                            $current_votes[$first_choice]++;
                                                        }
                                                    }

                                                    // Store the first round results
                                                    $rounds[] = $current_votes;

                                                    // Run the elimination process
                                                    while (true) {
                                                        if (empty($current_votes)) {
                                                            break;
                                                        }

                                                        // Check if any candidate has more than 50% of the votes
                                                        $total_votes = array_sum($current_votes);
                                                        $max_votes = max($current_votes);
                                                        $winner = array_search($max_votes, $current_votes);

                                                        if ($max_votes > $total_votes / 2) {
                                                            break;
                                                        }

                                                        // Find the candidate with the fewest votes
                                                        $min_votes = min($current_votes);
                                                        $eliminated = array_search($min_votes, $current_votes);

                                                        // Remove the candidate with the fewest votes
                                                        unset($current_votes[$eliminated]);

                                                        // Redistribute votes
                                                        foreach ($vote_box_array as &$vote) {
                                                            if ($vote['userVote'][0] === $eliminated) {
                                                                array_shift($vote['userVote']);
                                                                $new_choice = $vote['userVote'][0];
                                                                if (isset($current_votes[$new_choice])) {
                                                                    $current_votes[$new_choice]++;
                                                                }
                                                            }
                                                        }

                                                        // Store the round results
                                                        $rounds[] = $current_votes;
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

                                                    // foreach ($voting_options_array as $option) {
                                                    //     echo '<tr><td>' . esc_html($option->text) . '</td>';
                                                    //     foreach ($rounds as $round) {
                                                    //         echo '<td>' . (isset($round[$option->text]) ? esc_html($round[$option->text]) : 0) . '</td>';
                                                    //     }
                                                    //     echo '</tr>';
                                                    // }

                                                    echo '  </tbody></table></div>';

                                    ?>


                                <?php endif; ?> -->
                                        <!-- stv -->
                                        <!-- <?php if ($voting_choice === 'stv') : ?>
                                    <?php


                                                    // Number of seats to be filled
                                                    $num_seats = 1;

                                                    // Calculate the quota
                                                    $total_votes = count($vote_box_array);
                                                    $quota = floor($total_votes / ($num_seats + 1)) + 1;

                                                    echo "<p class='quota--color'>Quota: $quota</p>";

                                                    // Initialize rounds and result logging
                                                    $rounds = [];
                                                    $results_log = [];

                                                    $current_votes = [];
                                                    foreach ($voting_options_array as $option) {
                                                        $current_votes[$option->text] = 0;
                                                    }

                                                    // Count initial votes
                                                    foreach ($vote_box_array as $vote) {
                                                        $first_choice = $vote['userVote'][0];
                                                        if (isset($current_votes[$first_choice])) {
                                                            $current_votes[$first_choice]++;
                                                        }
                                                    }

                                                    // Store the first round results
                                                    $rounds[] = $current_votes;

                                                    // STV elimination and transfer process
                                                    while (count($rounds) <= $total_votes) {
                                                        if (empty($current_votes)) {
                                                            break;
                                                        }

                                                        // Check if any candidate has reached the quota
                                                        $max_votes = max($current_votes);
                                                        if ($max_votes >= $quota) {
                                                            $winner = array_search($max_votes, $current_votes);
                                                            $results_log[] = "Elected: $winner";
                                                            break;
                                                        }

                                                        // Find the candidate with the fewest votes
                                                        $min_votes = min($current_votes);
                                                        $eliminated = array_keys($current_votes, $min_votes);

                                                        // Log the eliminated candidates
                                                        $results_log[] = 'Eliminated: ' . implode(', ', $eliminated);

                                                        // Remove the eliminated candidate(s) and redistribute their votes
                                                        foreach ($eliminated as $candidate) {
                                                            unset($current_votes[$candidate]);

                                                            // Redistribute votes
                                                            foreach ($vote_box_array as &$vote) {
                                                                if ($vote['userVote'][0] === $candidate) {
                                                                    array_shift($vote['userVote']);
                                                                    $new_choice = $vote['userVote'][0];
                                                                    if (isset($current_votes[$new_choice])) {
                                                                        $current_votes[$new_choice]++;
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        // Store the round results
                                                        $rounds[] = $current_votes;
                                                    }

                                                    // Display results
                                                    echo '<div class="result--box">';
                                                    echo '<h4>Results (Using STV)</h4>';
                                                    echo '<ul>';
                                                    foreach ($results_log as $round_number => $result) {
                                                        echo '<li>Round ' . ($round_number + 1) . ': ' . $result . '</li>';
                                                    }
                                                    echo '</ul>';
                                                    echo '</div>';

                                                    // Output the results in a table
                                                    echo '<div class="table--area"><table><thead><tr><th>Rounds</th>';
                                                    for ($i = 1; $i <= count($rounds); $i++) {
                                                        echo "<th>$i</th>";
                                                    }
                                                    echo '  </tr></thead><tbody>';

                                                    // foreach ($voting_options_array as $option) {
                                                    //     echo '<tr><td>' . esc_html($option->text) . '</td>';
                                                    //     foreach ($rounds as $round) {
                                                    //         echo '<td>' . (isset($round[$option->text]) ? esc_html($round[$option->text]) : 0) . '</td>';
                                                    //     }
                                                    //     echo '</tr>';
                                                    // }


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
                                    ?>
                                <?php endif; ?> -->

                                        <?php //echo '########################################';
                                        ?>

                                        <?php if ($voting_choice === 'condorcet') : ?>
                                            <?php
                                            // Define the round number, set this dynamically as per your application logic
                                            $round_number = 1; // You can modify this if you have a way to increment or manage rounds

                                            // Initialize the pairwise comparison array
                                            $pairwise = [];

                                            // Loop through each voting option to create pairwise comparison entries
                                            foreach ($voting_options_array as $option) {
                                                foreach ($voting_options_array as $other_option) {
                                                    // Only compare different candidates
                                                    if ($option->text !== $other_option->text) {
                                                        $pairwise[$option->text][$other_option->text] = 0; // Initialize vote count
                                                    }
                                                }
                                            }

                                            // Perform pairwise comparisons based on user votes
                                            foreach ($vote_box_array as $vote) {
                                                if (isset($vote['userVote']) && is_array($vote['userVote'])) {
                                                    $preferences = $vote['userVote']; // Get the user's vote preferences
                                                    foreach ($preferences as $i => $candidate) {
                                                        for ($j = $i + 1; $j < count($preferences); $j++) {
                                                            // Increment the pairwise count based on preferences
                                                            $pairwise[$candidate][$preferences[$j]]++;
                                                        }
                                                    }
                                                }
                                            }

                                            // Determine the Condorcet winner
                                            $condorcet_winner = null; // Initialize winner variable
                                            foreach ($voting_options_array as $option) {
                                                $candidate = $option->text;
                                                $is_winner = true; // Assume the candidate is a winner until proven otherwise
                                                foreach ($voting_options_array as $other_option) {
                                                    $other_candidate = $other_option->text;
                                                    // Check if the candidate is preferred over every other candidate
                                                    if ($candidate !== $other_candidate && $pairwise[$other_candidate][$candidate] > $pairwise[$candidate][$other_candidate]) {
                                                        $is_winner = false; // Candidate loses to another
                                                        break;
                                                    }
                                                }
                                                if ($is_winner) {
                                                    $condorcet_winner = $candidate; // Found a Condorcet winner
                                                    break;
                                                }
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
                                            } else {
                                                echo '<li>No Condorcet Winner (possible cycle)</li>'; // Indicate a cycle if no winner is found
                                            }
                                            echo '</ul></div>';

                                            // Display the round results
                                            echo '<div class="round-description"><h4>Round ' . esc_html($round_number) . ':</h4>'; // Dynamically display round number

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
                                            } else {
                                                // Determine the highest first-choice vote count to declare a winner if no Condorcet winner
                                                if (!empty($first_choice_counts)) {
                                                    $max_votes = max($first_choice_counts);
                                                    $winners = array_keys($first_choice_counts, $max_votes);
                                                    if (count($winners) === 1) {
                                                        echo '<li>Option “' . esc_html($winners[0]) . '” wins with a majority of ' . esc_html($max_votes) . ' votes!</li>';
                                                    } else {
                                                        echo '<li>No clear winner was determined from first-choice votes.</li>';
                                                    }
                                                } else {
                                                    echo '<li>No votes were cast.</li>'; // Handle case where no votes are present
                                                }
                                            }
                                            echo '</ul>';
                                            echo '</div>';


                                            ?>
                                        <?php endif; ?>


                                        <?php if ($voting_choice === 'irv') : ?>
                                            <?php
                                            // Initialize vote counts
                                            $rounds = [];
                                            $current_votes = [];
                                            $event_log = []; // Initialize an array for event logging

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
                                                    $event_log[] = "Winner: \"$winner\" with $max_votes votes.";
                                                    break;
                                                }

                                                // Find the candidate with the fewest votes
                                                $min_votes = min($current_votes);
                                                $eliminated = array_search($min_votes, $current_votes);

                                                // Log the elimination event
                                                $event_log[] = "Round $round_number: Option \"$eliminated\" had $min_votes votes for 1st choice and therefore is eliminated.";

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
                                                            $event_log[] = "Vote by {$vote['userName']} for option \"$eliminated\" was eliminated. Checking next preferred option: \"$next_choice\".";
                                                        } else {
                                                            $event_log[] = "Vote by {$vote['userName']} for option \"$eliminated\" was eliminated, no further choices available.";
                                                        }
                                                    }
                                                }

                                                // Store the round results
                                                $rounds[] = $current_votes;
                                                $round_number++;
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
                                            foreach ($event_log as $event) {
                                                echo '<li>' . esc_html($event) . '</li>';
                                            }
                                            echo '</ul>';
                                            ?>
                                        <?php endif; ?>

                                        <?php if ($voting_choice === 'stv') : ?>

                                            <?php
                                            // Number of seats to be filled
                                            $num_seats = 1;

                                            // Calculate the quota
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
                                            while (count($rounds) <= $total_votes) {
                                                if (empty($current_votes)) break;

                                                // Log the round header
                                                $results_log[] = "Round $round:";

                                                // Check if any candidate has reached the quota
                                                foreach ($current_votes as $candidate => $votes) {
                                                    $results_log[] = "Option \"$candidate\" received $votes first-choice votes.";
                                                }

                                                $max_votes = max($current_votes);
                                                if ($max_votes >= $quota) {
                                                    $winner = array_search($max_votes, $current_votes);
                                                    $results_log[] = "Option \"$winner\" wins with a majority of $max_votes votes!";
                                                    break;
                                                }

                                                // Find the candidate with the fewest votes and eliminate
                                                $min_votes = min($current_votes);
                                                $eliminated = array_keys($current_votes, $min_votes);
                                                $results_log[] = 'Eliminated: ' . implode(', ', $eliminated) . " (votes: $min_votes)";

                                                foreach ($eliminated as $candidate) {
                                                    unset($current_votes[$candidate]);

                                                    // Redistribute votes to the next available choice
                                                    foreach ($vote_box_array as &$vote) {
                                                        if ($vote['userVote'][0] === $candidate) {
                                                            array_shift($vote['userVote']); // Remove eliminated candidate
                                                            $new_choice = $vote['userVote'][0] ?? null; // Get next choice
                                                            if ($new_choice && isset($current_votes[$new_choice])) {
                                                                $current_votes[$new_choice]++;
                                                                $results_log[] = "Vote by \"{$vote['userName']}\" reallocated to \"$new_choice\".";
                                                            }
                                                        }
                                                    }
                                                }

                                                // Store the round results
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
                                            foreach ($results_log as $log) {
                                                echo "<li>$log</li>";
                                            }
                                            echo '</ul></div>';
                                            ?>

                                        <?php endif; ?>

                                    </div>

                                </div>
                            <?php } ?>
                            <div class="table--part--box">






                                <?php

                                // echo '##########################################<pre>';
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
                                <?php } elseif ($voting_choice === 'irv' || $voting_choice === 'stv') { ?>
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
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var votingOptions = <?php echo $voting_options_json; ?>;
                var votingc = '<?php echo $voting_choice; ?>';

                console.log(votingc)

            })
        </script>
<?php

        return ob_get_clean();
    }
}
add_shortcode('voting_result', 'voting_result_shortcode');
