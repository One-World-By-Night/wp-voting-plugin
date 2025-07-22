<?php
// Shortcode for Elections dashboard. This is the function that runs when the shortcode is
function elections_dashboard_shortcode()
{
    // Check if the user is logged in
    if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
        wp_die('You do not have permission to access this page.');
    }
    if (is_user_logged_in()) {
        // Get the current user's username
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;
        $current_user_id = $current_user->ID;

        // Fetch data from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'voting';
        // $results = $wpdb->get_results("SELECT * FROM $table_name");
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

        $withdrawnResultData = array_filter($results, function ($item) {
            return $item->voting_stage === 'withdrawn';
        });
        $voting_options_json = json_encode($results);

        $running_votes = array_filter($results, function ($vote) {
            $votingStage = $vote->voting_stage;
            $closingDate = $vote->closing_date; // Assuming closingDate is a string in 'Y-m-d' format
            $today = strtotime(date('Y-m-d'));
            $activeStatus = $vote->active_status;

            // Check conditions
            if (
                $votingStage != 'draft' &&
                $votingStage != 'completed' &&
                $votingStage != 'withdrawn' &&
                //                 $votingStage != 'autopass' &&
                $closingDate >= $today &&
                $activeStatus == 'activeVote'
            ) {
                return true;
            }
            return false;
        });
        $completed_votes = array_filter($results, function ($vote) {
            $votingStage = $vote->voting_stage;
            $closingDate = $vote->closing_date; // Assuming closingDate is a string in 'Y-m-d' format
            $today = strtotime(date('Y-m-d'));

            // Check conditions
            // if ($votingStage === 'completed' || $closingDate < $today) {
            if ($votingStage === 'completed') {
                return true;
            }
            return false;
        });

        $draft_votes = array_filter($results, function ($vote) {
            $votingStage = $vote->voting_stage;
            $closingDate = $vote->closing_date; // Assuming closingDate is a string in 'Y-m-d' format
            $today = strtotime(date('Y-m-d'));

            // Check conditions
            if ($votingStage === 'draft') {
                return true;
            }
            return false;
        });

        $voting_options_json1 = json_encode($running_votes);

        // Display the dashboard content HTML
        ob_start();
        // Sendmail();
?>
        <section class="owbnVP custom-section election_dashboard">
            <div class="wrapper">
                <div class="tab--section">
                    <div class="tab--wrapper">
                        <div class="tabs">
                            <ul class="tab-links">
                                <li class="active"><a href="#tab1">Active Votes</a></li>
                                <li><a href="#tab3">Complete</a></li>

                                <?php if (is_user_logged_in()) { ?>

                                    <li><a href="#tab5">Withdrawn</a></li>
                                <?php } ?>
                                <?php if (is_user_logged_in() || (current_user_can('administrator'))) { ?>
                                    <!-- <li><a href="#tab5">Withdrawn</a></li> -->
                                    <!-- <li><a href="#tab4">Draft</a></li> -->
                                <?php
                                }
                                ?>
                            </ul>
                            <div class="tab-content">
                                <!-- Outputs the tab options in the admin UI. This is a copy of the code that has been moved to utils. -->
                                <div id="tab1" class="tab active">

                                    <div class="table-area">
                                        <div class="table-responsive">
                                            <table class="custom--table desktop-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Proposal Name</th>
                                                        <th>Vote Type</th>
                                                        <th>Voting Stage</th>
                                                        <th>Proposed By</th>
                                                        <th>Seconded By</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>CM Voted</th>
                                                        <th>Voted For</th>
                                                        <th>Visibility</th>
                                                        <!-- <th>Maximum Choices</th> -->
                                                        <th>Active Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php

                                                    if (!empty($running_votes)) {
                                                        foreach ($running_votes as $option) {
                                                    ?>
                                                            <tr>
                                                                <td><?php echo esc_html($option->id); ?></td>
                                                                <!-- <td><?php echo esc_html($option->proposal_name); ?></td> -->

                                                                <td>
                                                                    <?php if ($option->voting_stage == 'autopass' && current_user_can('author')) { ?>
                                                                        <a href="<?php echo esc_url(site_url('/owbn-preview-vote?id=' . $option->id)); ?>" class="edit-icon">
                                                                            <?php echo esc_html($option->proposal_name); ?>
                                                                        </a>
                                                                        <?php } else {
                                                                        $opening_date_str = strtotime($option->opening_date); // Opening date
                                                                        $closing_date_str = strtotime($option->closing_date); // Closing date

                                                                        if ($option->active_status != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
                                                                            echo esc_html($option->proposal_name);

                                                                            echo "\n <br><span style=\"font-size:10px\">Polls not active</span>";
                                                                        } else {
                                                                        ?>


                                                                            <?php
                                                                            // $vote_box_array = json_decode($option->vote_box, true);
                                                                            // $user_has_voted = false;

                                                                            // // Check if the current user has already voted
                                                                            // if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                            //     foreach ($vote_box_array as $vote) {
                                                                            //         if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                            //             $votedFor = !empty($vote['userVote']);

                                                                            //             if ($votedFor && $option->blindVoting == "BlindVotingtrue") {
                                                                            //                 echo "<p>You've voted</p>";
                                                                            //                 $user_has_voted = true;
                                                                            //                 break; // Exit the loop since the user has voted
                                                                            //             }
                                                                            //         }
                                                                            //     }
                                                                            // }

                                                                            // // If the user hasn't voted, show the participation link
                                                                            // if (!$user_has_voted) {
                                                                            //     echo '<a href="' . esc_url(site_url('/owbn-voting-box/' . $option->id . '/')) . '" class="edit-icon">Participate</a>';
                                                                            // }
                                                                            ?>


                                                                            <a href="<?php echo esc_url(site_url('/owbn-voting-box/' . $option->id . '/')); ?>" class="edit-icon">
                                                                                <?php echo esc_html($option->proposal_name); ?>
                                                                            </a>

                                                                    <?php }
                                                                    } ?>

                                                                </td>
                                                                <td><?php echo esc_html($option->vote_type); ?></td>
                                                                <td><?php echo esc_html($option->voting_stage); ?></td>
                                                                <td><?php echo esc_html($option->proposed_by); ?></td>
                                                                <td><?php echo esc_html($option->seconded_by); ?></td>
                                                                <td><?php echo esc_html($option->opening_date); ?></td>
                                                                <td><?php echo esc_html($option->closing_date); ?></td>

                                                                <td>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = 'No Vote Submitted'; // Default value if no vote time is found

                                                                    // Check if the current user has already voted
                                                                    if ($vote_box_array) {
                                                                        foreach ($vote_box_array as $vote) {
                                                                            if ($vote['userId'] == $current_user_id) {

                                                                                //    $current_user_vote = $vote['votetime']?$vote['votetime']:$current_user_vote;
                                                                                $current_user_vote = "Vote submitted";

                                                                                break; // Stop looping once we find the user's vote
                                                                            }
                                                                        }
                                                                    } else if ($option->active_status != 'activeVote' || time() <  strtotime($option->opening_date) || time() > strtotime($option->closing_date)) {
                                                                        $current_user_vote = "Not Open Yet";
                                                                    }
                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>




                                                                <td>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = '-'; // Default value if no vote time is found

                                                                    // Check if the current user has already voted
                                                                    if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                        foreach ($vote_box_array as $vote) {
                                                                            if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                                $voteDate = !empty($vote['votetime']) ? htmlspecialchars($vote['votetime']) : "N/A";
                                                                                // $votedFor = !empty($vote['userVote']) ? ($vote['userVote']) : "No selection";
                                                                                $votedFor = !empty($vote['userVote'])
                                                                                    ? (is_array($vote['userVote']) ? implode(', ', $vote['userVote']) : $vote['userVote'])
                                                                                    : "No selection";

                                                                                $current_user_vote = "
                                                                                                      <ul style='list-style: none;'>
                                                                                                                          <li style='padding: 5px;'>Vote Date: $voteDate</li>
                                                                                              <li style='padding: 5px;'>Voted For: $votedFor</li>
                                                                                                           </ul>";

                                                                                break; // Stop loop once we find the current user's vote
                                                                            }
                                                                        }
                                                                    } else if ($option->active_status != 'activeVote' || time() <  strtotime($option->opening_date) || time() > strtotime($option->closing_date)) {
                                                                        $current_user_vote = "-";
                                                                    }
                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>
                                                                <td><?php echo esc_html($option->visibility); ?></td>
                                                                <!-- <td><?php echo esc_html($option->maximum_choices); ?></td> -->
                                                                <td><?php echo esc_attr($option->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote'); ?></td>
                                                                <?php
                                                                echo '<td  class="votePerson_Action">';
                                                                if (is_user_logged_in() && (current_user_can('administrator') || ($option->created_by == $current_user->ID))) {
                                                                    // echo '<a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-voting-form/' . $option->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';

                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $option->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                                                                }
                                                                echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $option->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> </td>';
                                                                ?>

                                                                <!-- <td>
                                                                    <?php if ($option->voting_stage == 'autopass' && current_user_can('author')) { ?>
                                                                        <a href="<?php echo esc_url(site_url('/owbn-preview-vote?id=' . $option->id)); ?>" class="edit-icon">
                                                                            View
                                                                        </a>
                                                                        <?php } else {
                                                                        $opening_date_str = strtotime($option->opening_date); // Opening date
                                                                        $closing_date_str = strtotime($option->closing_date); // Closing date

                                                                        if ($option->active_status != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
                                                                            echo 'Polls not active';
                                                                        } else {
                                                                        ?>


                                                                            <?php
                                                                            // $vote_box_array = json_decode($option->vote_box, true);
                                                                            // $user_has_voted = false;

                                                                            // // Check if the current user has already voted
                                                                            // if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                            //     foreach ($vote_box_array as $vote) {
                                                                            //         if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                            //             $votedFor = !empty($vote['userVote']);

                                                                            //             if ($votedFor && $option->blindVoting == "BlindVotingtrue") {
                                                                            //                 echo "<p>You've voted</p>";
                                                                            //                 $user_has_voted = true;
                                                                            //                 break; // Exit the loop since the user has voted
                                                                            //             }
                                                                            //         }
                                                                            //     }
                                                                            // }

                                                                            // // If the user hasn't voted, show the participation link
                                                                            // if (!$user_has_voted) {
                                                                            //     echo '<a href="' . esc_url(site_url('/owbn-voting-box/' . $option->id . '/')) . '" class="edit-icon">Participate</a>';
                                                                            // }
                                                                            ?>


                                                                            <a href="<?php echo esc_url(site_url('/owbn-voting-box/' . $option->id . '/')); ?>" class="edit-icon">
                                                                                Participate
                                                                            </a>

                                                                    <?php }
                                                                    } ?>

                                                                </td> -->
                                                            </tr>
                                                        <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="11">No results found.</td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>

                                            <table class="custom--table mobile-table">
                                                <thead>
                                                    <tr>

                                                        <th>Proposal Name</th>

                                                        <th>Closing Date</th>
                                                        <th>Total Votes</th>


                                                        <!-- <th>Actions</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php

                                                    if (!empty($running_votes)) {
                                                        foreach ($running_votes as $option) {
                                                    ?>
                                                            <tr>

                                                                <!-- <td><?php echo esc_html($option->proposal_name); ?>
                                                            <br>
                                                            <?php echo esc_html($option->vote_type); ?>
                                                            </td> -->


                                                                <td>
                                                                    <?php if ($option->voting_stage == 'autopass' && current_user_can('author')) { ?>
                                                                        <a href="<?php echo esc_url(site_url('/owbn-preview-vote?id=' . $option->id)); ?>" class="edit-icon">
                                                                            <?php echo esc_html($option->proposal_name); ?>
                                                                        </a>
                                                                        <?php } else {
                                                                        $opening_date_str = strtotime($option->opening_date); // Opening date
                                                                        $closing_date_str = strtotime($option->closing_date); // Closing date

                                                                        if ($option->active_status != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
                                                                            echo esc_html($option->proposal_name);

                                                                            echo "\n <br><span style=\"font-size:10px\">Polls not active</span>";
                                                                        } else {
                                                                        ?>


                                                                            <?php
                                                                            // $vote_box_array = json_decode($option->vote_box, true);
                                                                            // $user_has_voted = false;

                                                                            // // Check if the current user has already voted
                                                                            // if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                            //     foreach ($vote_box_array as $vote) {
                                                                            //         if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                            //             $votedFor = !empty($vote['userVote']);

                                                                            //             if ($votedFor && $option->blindVoting == "BlindVotingtrue") {
                                                                            //                 echo "<p>You've voted</p>";
                                                                            //                 $user_has_voted = true;
                                                                            //                 break; // Exit the loop since the user has voted
                                                                            //             }
                                                                            //         }
                                                                            //     }
                                                                            // }

                                                                            // // If the user hasn't voted, show the participation link
                                                                            // if (!$user_has_voted) {
                                                                            //     echo '<a href="' . esc_url(site_url('/owbn-voting-box/' . $option->id . '/')) . '" class="edit-icon">Participate</a>';
                                                                            // }
                                                                            ?>


                                                                            <a href="<?php echo esc_url(site_url('/owbn-voting-box/' . $option->id . '/')); ?>" class="edit-icon">
                                                                                <?php echo esc_html($option->proposal_name); ?>
                                                                            </a>

                                                                    <?php }
                                                                    } ?>
                                                                    <br>
                                                                    <?php echo esc_html($option->vote_type); ?>


                                                                </td>


                                                                <td><?php echo esc_html($option->closing_date); ?></td>

                                                                <td>



                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    echo count($vote_box_array);
                                                                    ?>
                                                                    <br>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = 'No Vote Submitted'; // Default value if no vote time is found

                                                                    // Check if the current user has already voted
                                                                    if ($vote_box_array) {
                                                                        foreach ($vote_box_array as $vote) {
                                                                            if ($vote['userId'] == $current_user_id) {

                                                                                //    $current_user_vote = $vote['votetime']?$vote['votetime']:$current_user_vote;
                                                                                $current_user_vote = "Vote submitted";

                                                                                break; // Stop looping once we find the user's vote
                                                                            }
                                                                        }
                                                                    } else if ($option->active_status != 'activeVote' || time() <  strtotime($option->opening_date) || time() > strtotime($option->closing_date)) {
                                                                        $current_user_vote = "Not Open Yet";
                                                                    }
                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>






                                                                <!-- <td>
                                                                    <?php if ($option->voting_stage == 'autopass' && current_user_can('author')) { ?>
                                                                        <a href="<?php echo esc_url(site_url('/owbn-preview-vote?id=' . $option->id)); ?>" class="edit-icon">
                                                                            View
                                                                        </a>
                                                                        <?php } else {
                                                                        $opening_date_str = strtotime($option->opening_date); // Opening date
                                                                        $closing_date_str = strtotime($option->closing_date); // Closing date

                                                                        if ($option->active_status != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
                                                                            echo 'Polls not active';
                                                                        } else {
                                                                        ?>


                                                                            <?php
                                                                            // $vote_box_array = json_decode($option->vote_box, true);
                                                                            // $user_has_voted = false;

                                                                            // // Check if the current user has already voted
                                                                            // if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                            //     foreach ($vote_box_array as $vote) {
                                                                            //         if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                            //             $votedFor = !empty($vote['userVote']);

                                                                            //             if ($votedFor && $option->blindVoting == "BlindVotingtrue") {
                                                                            //                 echo "<p>You've voted</p>";
                                                                            //                 $user_has_voted = true;
                                                                            //                 break; // Exit the loop since the user has voted
                                                                            //             }
                                                                            //         }
                                                                            //     }
                                                                            // }

                                                                            // // If the user hasn't voted, show the participation link
                                                                            // if (!$user_has_voted) {
                                                                            //     echo '<a href="' . esc_url(site_url('/owbn-voting-box/' . $option->id . '/')) . '" class="edit-icon">Participate</a>';
                                                                            // }
                                                                            ?>


                                                                            <a href="<?php echo esc_url(site_url('/owbn-voting-box/' . $option->id . '/')); ?>" class="edit-icon">
                                                                                Participate
                                                                            </a>

                                                                    <?php }
                                                                    } ?>

                                                                </td> -->
                                                            </tr>
                                                        <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="11">No results found.</td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>


                                        </div>
                                    </div>
                                </div>

                                <div id="tab3" class="tab">
                                    <div class="table-area complete--tab">
                                        <div class="table-responsive">
                                            <table class="custom--table desktop-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>

                                                        <th>Proposal Name</th>
                                                        <th>Vote Type</th>
                                                        <th>Voting Stage</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>CM Voted</th>
                                                        <th>Voted For</th>

                                                        <th>Result</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (!empty($completed_votes)) {
                                                        foreach ($completed_votes as $option) {
                                                    ?>
                                                            <tr>
                                                                <td><?php echo esc_html($option->id); ?></td>

                                                                <td><?php echo esc_html($option->proposal_name); ?></td>
                                                                <td><?php echo esc_html($option->vote_type); ?></td>
                                                                <td><?php echo esc_html($option->voting_stage); ?></td>

                                                                <td><?php echo esc_html($option->opening_date); ?></td>
                                                                <td><?php echo esc_html($option->closing_date); ?></td>
                                                                <td>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = 'Vote closed'; // Default value if no vote time is found

                                                                    // Check if the current user has already voted
                                                                    //    if ($vote_box_array) {
                                                                    //        foreach ($vote_box_array as $vote) {
                                                                    //            if ($vote['userId'] == $current_user_id) {

                                                                    //             //    $current_user_vote = $vote['votetime']?$vote['votetime']:$current_user_vote;
                                                                    //             $current_user_vote ="Vote submitted";

                                                                    //             break; // Stop looping once we find the user's vote
                                                                    //            }
                                                                    //        }
                                                                    //    }
                                                                    //    else if($option->active_status != 'activeVote' || time() <  strtotime($option->opening_date) || time() > strtotime($option->closing_date)){
                                                                    //     $current_user_vote ="Not Open Yet";
                                                                    // }

                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>

                                                                <td>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = 'No Vote Submitted'; // Default value if no vote time is found

                                                                    // Check if the current user has already voted
                                                                    if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                        foreach ($vote_box_array as $vote) {
                                                                            if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                                $voteDate = !empty($vote['votetime']) ? htmlspecialchars($vote['votetime']) : "N/A";
                                                                                // $votedFor = !empty($vote['userVote']) ? ($vote['userVote']) : "No selection";
                                                                                $votedFor = !empty($vote['userVote'])
                                                                                    ? (is_array($vote['userVote']) ? implode(', ', $vote['userVote']) : $vote['userVote'])
                                                                                    : "No selection";


                                                                                $current_user_vote = "
                                                   <ul style='list-style: none;'>
                                                                                              <li style='padding: 5px;'>Vote Date: $voteDate</li>
                                                                          <li style='padding: 5px;'>Voted For: $votedFor</li>
                                                                                      </ul>";

                                                                                break; // Stop loop once we find the current user's vote
                                                                            }
                                                                        }
                                                                    } else if ($option->active_status != 'activeVote' || time() <  strtotime($option->opening_date) || time() > strtotime($option->closing_date)) {
                                                                        $current_user_vote = "No Vote Submitted";
                                                                    }
                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <!-- <a href="<?php echo esc_url(site_url('/owbn-voting-result?id=' . $option->id)); ?>" class="edit-icon">
                                                                    Show
                                                                </a> -->
                                                                    <a href="<?php echo esc_url(site_url('/owbn-voting-result/' . $option->id)); ?>" class="edit-icon">
                                                                        Show
                                                                    </a>

                                                                </td>
                                                            </tr>
                                                        <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="11">No results found.</td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>




                                            <table class="custom--table mobile-table">
                                                <thead>
                                                    <tr>

                                                        <th>Proposal Name</th>

                                                        <th>Closing Date</th>
                                                        <th>Total Votes</th>


                                                        <th>Result</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php

                                                    if (!empty($completed_votes)) {
                                                        foreach ($completed_votes as $option) {
                                                    ?>
                                                            <tr>

                                                                <td><?php echo esc_html($option->proposal_name); ?>

                                                                    <br>
                                                                    <?php echo esc_html($option->vote_type); ?>
                                                                </td>


                                                                <td><?php echo esc_html($option->closing_date); ?></td>

                                                                <td>



                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    echo count($vote_box_array);
                                                                    ?>
                                                                    <br>
                                                                    <?php
                                                                    $vote_box_array = json_decode($option->vote_box, true);
                                                                    $current_user_vote = 'Vote closed'; // Default value if no vote time is found


                                                                    echo $current_user_vote; // Output the vote time
                                                                    ?>
                                                                </td>



                                                                <td>
                                                                    <!-- <a href="<?php echo esc_url(site_url('/owbn-voting-result?id=' . $option->id)); ?>" class="edit-icon">
                                                                    Show
                                                                </a> -->
                                                                    <a href="<?php echo esc_url(site_url('/owbn-voting-result/' . $option->id)); ?>" class="edit-icon">
                                                                        Show
                                                                    </a>

                                                                </td>


                                                                <!-- <td>
                                                                    <?php if ($option->voting_stage == 'autopass' && current_user_can('author')) { ?>
                                                                        <a href="<?php echo esc_url(site_url('/owbn-preview-vote?id=' . $option->id)); ?>" class="edit-icon">
                                                                            View
                                                                        </a>
                                                                        <?php } else {
                                                                        $opening_date_str = strtotime($option->opening_date); // Opening date
                                                                        $closing_date_str = strtotime($option->closing_date); // Closing date

                                                                        if ($option->active_status != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
                                                                            echo 'Polls not active';
                                                                        } else {
                                                                        ?>


                                                                            <?php
                                                                            // $vote_box_array = json_decode($option->vote_box, true);
                                                                            // $user_has_voted = false;

                                                                            // // Check if the current user has already voted
                                                                            // if (!empty($vote_box_array) && is_array($vote_box_array)) {
                                                                            //     foreach ($vote_box_array as $vote) {
                                                                            //         if (isset($vote['userId']) && $vote['userId'] == $current_user_id) {
                                                                            //             $votedFor = !empty($vote['userVote']);

                                                                            //             if ($votedFor && $option->blindVoting == "BlindVotingtrue") {
                                                                            //                 echo "<p>You've voted</p>";
                                                                            //                 $user_has_voted = true;
                                                                            //                 break; // Exit the loop since the user has voted
                                                                            //             }
                                                                            //         }
                                                                            //     }
                                                                            // }

                                                                            // // If the user hasn't voted, show the participation link
                                                                            // if (!$user_has_voted) {
                                                                            //     echo '<a href="' . esc_url(site_url('/owbn-voting-box/' . $option->id . '/')) . '" class="edit-icon">Participate</a>';
                                                                            // }
                                                                            ?>


                                                                            <a href="<?php echo esc_url(site_url('/owbn-voting-box/' . $option->id . '/')); ?>" class="edit-icon">
                                                                                Participate
                                                                            </a>

                                                                    <?php }
                                                                    } ?>

                                                                </td> -->
                                                            </tr>
                                                        <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="11">No results found.</td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php if (is_user_logged_in() || (current_user_can('administrator'))) { ?>
                                    <!-- <div id="tab4" class="tab">
                                    <div class="table-area">
                                        <table class="custom--table">
                                            <thead>
                                                <tr>
                                                    <th>Proposal Name</th>
                                                    <th>Vote Type</th>
                                                    <th>Voting Stage</th>
                                                    <th>Opening Date</th>
                                                    <th>Closing Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (!empty($draft_votes)) {
                                                    foreach ($draft_votes as $option) {
                                                ?>
                                                        <tr>
                                                            <td><?php echo esc_html($option->proposal_name); ?></td>
                                                            <td><?php echo esc_html($option->vote_type); ?></td>
                                                            <td><?php echo esc_html($option->voting_stage); ?></td>

                                                            <td><?php echo esc_html($option->opening_date); ?></td>
                                                            <td><?php echo esc_html($option->closing_date); ?></td>

                                                            <td>
                                                                <a href="<?php echo esc_url(site_url('/owbn-voting-form?id=' . $option->id)); ?>" class="edit-icon">
                                                                    <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                                                </a>

                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="11">No results found.</td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div> -->

                                    <!-- <div id="tab5" class="tab">
                                        <div class="table-area">
                                            <table class="custom--table">
                                                <thead>
                                                    <tr>
                                                        <th>Proposal Name</th>
                                                        <th>Vote Type</th>
                                                        <th>Voting Stage</th>
                                                        <th>Proposed By</th>
                                                        <th>Seconded By</th>
                                                        <th>WithdrawnAt</th>
                                                        <th>Opening Date</th>
                                                        <th>Closing Date</th>
                                                        <th>Visibility</th>
                                                        <th>Active Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (0 < count($withdrawnResultData)) {
                                                        foreach ($withdrawnResultData as $row) {

                                                            echo '<tr>';
                                                            echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                                            echo '<td>' . esc_html($row->vote_type) . '</td>';
                                                            echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                                            echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                                            echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                                            echo '<td>' . esc_html($row->withdrawn_time) . '</td>';

                                                            echo '<td>' . esc_html($row->opening_date) . '</td>';
                                                            echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                            echo '<td>' . esc_html($row->visibility) . '</td>';
                                                            echo '<td>' . esc_attr($row->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote')  . '</td>';
                                                            echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                </a> ';
                                                            echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                               </a>';
                                                            echo '<a href="' . esc_url(site_url('/owbn-preview-vote?id=' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
                                                               </a> </td>';
                                                            // echo '<td><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil" aria-hidden="true"></i></a> </td>';
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="11">No voting proposals found.</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div> -->
                                <?php
                                }
                                ?>



                                <?php if (is_user_logged_in()) { ?>
                                    <div id="tab5" class="tab">
                                        <div class="table-area">
                                            <div class="table-responsive">
                                                <table class="custom--table desktop-table">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>

                                                            <th>Proposal Name</th>
                                                            <th>Vote Type</th>
                                                            <th>Voting Stage</th>
                                                            <th>Proposed By</th>
                                                            <th>Seconded By</th>
                                                            <th>WithdrawnAt</th>
                                                            <th>Opening Date</th>
                                                            <th>Closing Date</th>
                                                            <th>CM Voted</th>

                                                            <th>Visibility</th>
                                                            <th>Active Status</th>
                                                            <th>Result</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if (0 < count($withdrawnResultData)) {
                                                            foreach ($withdrawnResultData as $row) {

                                                                echo '<tr>';
                                                                echo '<td>' . esc_html($row->id) . '</td>';

                                                                echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                                                echo '<td>' . esc_html($row->vote_type) . '</td>';
                                                                echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                                                echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                                                echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                                                echo '<td>' . esc_html($row->withdrawn_time) . '</td>';

                                                                echo '<td>' . esc_html($row->opening_date) . '</td>';
                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';

                                                                $vote_box_array = json_decode($row->vote_box, true);
                                                                $current_user_vote = 'Vote withdrawned'; // Default value if no vote time is found

                                                                // Check if the current user has already voted
                                                                // if ($vote_box_array) {
                                                                //     foreach ($vote_box_array as $vote) {
                                                                //         if ($vote['userId'] == $current_user_id) {
                                                                //             // $current_user_vote = !empty($vote['votetime']) ? esc_html($vote['votetime']) : $current_user_vote;
                                                                //             $current_user_vote ="Vote submitted";
                                                                //             break; // Stop looping once we find the user's vote
                                                                //         }
                                                                //     }
                                                                // }
                                                                // else if($row->active_status != 'activeVote' || time() <  strtotime($row->opening_date) || time() > strtotime($row->closing_date)){
                                                                //     $current_user_vote ="Not Open Yet";
                                                                // }


                                                                // Echo the table row with the correct vote time
                                                                echo '<td>' . $current_user_vote . '</td>';

                                                                echo '<td>' . esc_html($row->visibility) . '</td>';
                                                                echo '<td>' . esc_attr($row->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote')  . '</td>';

                                                                //     echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                //     </a> ';
                                                                //     echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                //    </a>';
                                                                //     echo '<a href="' . esc_url(site_url('/owbn-preview-vote?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
                                                                //    </a> </td>';
                                                                echo '<td><a href="' . esc_url(site_url('/owbn-voting-result/' . $option->id . '/')) . '" class="edit-icon">Show</a> </td>';
                                                                echo '</tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="11">No voting proposals found.</td></tr>';
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>



                                                <table class="custom--table mobile-table">
                                                    <thead>
                                                        <tr>

                                                            <th>Proposal Name</th>

                                                            <th>Closing Date</th>
                                                            <th>Total Votes</th>


                                                            <th>Result</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if (0 < count($withdrawnResultData)) {
                                                            foreach ($withdrawnResultData as $row) {

                                                                echo '<tr>';


                                                                echo '<td>' . esc_html($row->proposal_name) . '<br>' . esc_html($row->vote_type) . '</td>';


                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';

                                                                $vote_box_array = json_decode($row->vote_box, true);
                                                                $current_user_vote = 'Vote withdrawned'; // Default value if no vote time is found

                                                                $vote_box_array = json_decode($option->vote_box, true);

                                                                // Echo the table row with the correct vote time
                                                                echo '<td>'  . count($vote_box_array) . '<br>' . $current_user_vote . '</td>';





                                                                echo '<td><a href="' . esc_url(site_url('/owbn-voting-result/' . $option->id . '/')) . '" class="edit-icon">Show</a> </td>';
                                                                echo '</tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="11">No voting proposals found.</td></tr>';
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script>
            var phpFilesName = <?php echo $voting_options_json1; ?>;
            var phpFilesName2 = <?php echo $voting_options_json; ?>;
            // console.log(phpFilesName)
            // console.log(phpFilesName2)

            function deleteVote(id) {
                if (confirm('Are you sure you want to delete this vote?')) {
                    // If user confirms deletion, send AJAX request to delete-vote.php
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            // Refresh the page after successful deletion
                            window.location.reload();
                        } else {
                            console.error('Error deleting vote');
                        }
                    };
                    xhr.send('action=delete_vote&id=' + id);
                }
            }
        </script>


        <script>
            jQuery(document).ready(function() {
                function handleTabSwitch(e) {
                    e.preventDefault();
                    var currentAttrValue = jQuery(this).attr('href');
                    jQuery('.tab' + currentAttrValue).fadeIn(400).siblings().hide();
                    jQuery(this).parent('li').addClass('active').siblings().removeClass('active');
                }

                jQuery('.tab-links a').on('click', handleTabSwitch);

                jQuery('#addTab').on('click', function() {
                    var newTabIndex = jQuery('.tab-links li').length + 1;
                    var newTabId = 'tab' + newTabIndex;
                    jQuery('.tab-links').append('<li><a href="#' + newTabId + '">Tab ' + newTabIndex + '</a></li>');
                    jQuery('.tab-content').append('<div id="' + newTabId + '" class="tab"><p>Content for Tab ' +
                        newTabIndex + '</p></div>');
                    jQuery('.tab-links a').off('click').on('click', handleTabSwitch);
                });
            });
        </script>

<?php
        return ob_get_clean();
    } else {
        // User is not logged in, redirect to staff login page
        wp_redirect(site_url('/staff-login'));
        exit;
    }
}
add_shortcode('elections_dashboard', 'elections_dashboard_shortcode');
