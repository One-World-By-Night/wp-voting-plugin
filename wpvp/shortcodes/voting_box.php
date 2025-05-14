<?php
function voting_box_shortcode()
{
    get_header();

    $id = get_query_var('id', 'default_value');
    // Check if the user is logged in and if the user has the 'administrator' or 'author' role
    if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
        wp_die('You do not have permission to access this page.');
    }

    $current_user = wp_get_current_user();

    // Get the current user ID
    $current_user_id = $current_user->ID;

    // Get the current user's display name
    $current_user_name = $current_user->display_name;

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


    $vote_id = $id ? intval($id) : null;

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
    $current_user_vote1 = '';

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
        $blindVoting = $vote_data['blindVoting'];
        $vote_box_array_new = json_decode($vote_box, true);
        $users_not_voted = array_filter($all_users, function ($user) use ($all_voted_users) {
            return !in_array($user->ID, $all_voted_users);
        });


        // Encode
        $voting_options_json = json_encode($voting_options);
        // Decode
        $vote_box_array = json_decode($vote_box, true);

        // Check if the current user has already voted
        if ($vote_box_array) {
            foreach ($vote_box_array as $vote) {
                if ($vote['userId'] == $current_user_id) {
                    $current_user_vote = $vote['userVote'];
                    $current_user_vote1 = $vote['userVote'];

                    $current_user_comment = $vote['userComment'];
                    break;
                }
            }
        }


        $other_users_comments = [];

        if ($vote_box_array) {
            foreach ($vote_box_array as $vote) {
                if ($vote['userId'] != $current_user_id) { // Exclude current user
                    $other_users_comments[] = [
                        'userId' => $vote['userId'],
                        'userName' => $vote['userName'],

                        'comment' => $vote['userComment']
                    ];
                }
            }
        }




        // $vote_counts = [];

        // if (!empty($vote_box_array) && is_array($vote_box_array)) {
        //     foreach ($vote_box_array as $vote) {
        //         if (!empty($vote['userVote'])) {
        //             if (is_array($vote['userVote'])) {
        //                 $votedFor = json_encode($vote['userVote']); // Convert array to JSON string
        //             } else {
        //                 $votedFor = htmlspecialchars($vote['userVote'], ENT_QUOTES, 'UTF-8');
        //             }

        //             if (!isset($vote_counts[$votedFor])) {
        //                 $vote_counts[$votedFor] = 0;
        //             }
        //             $vote_counts[$votedFor]++;
        //         }
        //     }
        // }

        // // Convert to JSON for JavaScript
        // $vote_data_json = json_encode($vote_counts, JSON_UNESCAPED_UNICODE);


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

        // Print other users' comments for debugging


        if ($voting_choice !== 'single') {
            $current_user_vote = json_encode($current_user_vote);
        }
    } else {
        wp_die('Vote box is not valid');
    }
    $opening_date_str = strtotime($opening_date); // Opening date
    $closing_date_str = strtotime($closing_date); // Closing date

    if ($vote_data['active_status'] != 'activeVote' || time() < $opening_date_str || time() > $closing_date_str) {
        wp_die('Voting is not open.');
    }


    ob_start();
?>
    <section class="owbnVP bg--black voting_box">
        <div class="section-wrapper">
            <div class="row ">
                <div class="user_dashboard_vote">
                    <div class="user-title">
                        <h1>Vote For: <?php echo esc_attr($proposal_name); ?> </h1>
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
                    <div class="proposal-type">
                        <span class="vote-roposal"><strong>Proposal Type :
                            </strong><?php echo esc_attr($vote_type); ?></span>
                        <span class="vote-open"><strong>Opened : </strong><?php echo date('m/d/Y h:iA', strtotime(esc_attr($opening_date))); ?>
                            <?php echo get_timezone_abbreviation(); ?></span>
                        <span class="vote-close"><strong>Closing : </strong><?php echo date('m/d/Y h:iA', strtotime(esc_attr($closing_date))); ?>
                            <?php echo get_timezone_abbreviation(); ?></span>
                    </div>



                    <!-- Files -->
                    <?php
                    if ($vote_id) { ?>
                        <div class="proposal--name file--document">
                            <h5>File / Document</h5>
                            <div class="table--box">
                                <table class="file--information--table" id="fileInfoTable">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($files_name)) {
                                            foreach ($files_name as $option) {
                                                if ((current_user_can('author') && $option['display'] == 1) || current_user_can('administrator')) { ?>
                                                    <tr>
                                                        <td>
                                                            <div class="file--name">
                                                                <p>
                                                                    <a target="_blank" href="<?php echo isset($option['id']) ? wp_get_attachment_url($option['id']) : '#'; ?>">
                                                                        <?php echo esc_html($option['fileName']); ?>
                                                                        <span>(<?php echo esc_html($option['fileSize']); ?> bytes)</span>
                                                                    </a>
                                                                </p>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div><?php echo esc_html($option['description']); ?></div>
                                                        </td>
                                                    </tr>
                                        <?php }
                                            }
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="proposal-paragraph">
                        <h5>Vote Description</h5>
                        <?php echo wp_kses_post($content); ?>
                    </div>
                    <br>
                    <?php
                    if ($blindVoting == "BlindVotingtrue") {
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
                        <!-- <div class="table--area voteReceive_notReceive">
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

                        </div> -->

                        <canvas id="voteChart"></canvas>



                        <div class="proposal-paragraph">
                            <h5>Other Chronicle Comments</h5>
                            <?php
                            if (!empty($other_users_comments)) {

                                echo "<ul>";
                                foreach ($other_users_comments as $comment) {
                                    if (!empty($comment['comment'])) { // Sirf non-empty comments show karo
                                        echo "<li><strong>" . htmlspecialchars($comment['userName']) . ":</strong> " . htmlspecialchars($comment['comment']) . "</li>";
                                    }
                                }
                                echo "</ul>";
                            } else {
                                echo "No comments available.";
                            }
                            ?>
                        </div>




                    <?php
                    }
                    ?>

                    <div class="vote-info">
                        <div class="spacer"></div>







                        <h4>Ballot Options</h4>
                        <form class="custom-form" id="votingForm">
                            <div>
                                <div id="votingOptionsContainer">

                                </div>
                            </div>

                            <!-- <div id="votingOptionsContainer">
                              
                              </div> -->

                            <div style="width: 100%">

                                <div>
                                    <h4 style="margin: 20px 0;">Comment: </h4>
                                    <textarea id="owbn-user-comment" name="owbn-comment"><?php echo $current_user_comment ? esc_textarea($current_user_comment) : ''; ?></textarea>
                                </div>
                                <div class="save--btn-box mb--30 custom--btns">
                                    <button type="button" class="cust--btn submitbtn votebtn" onclick="submitVote(<?php echo $vote_id; ?>, <?php echo $current_user_id; ?>, '<?php echo $current_user_name; ?>', '<?php echo $voting_choice; ?>')">
                                        Let's Vote
                                    </button>
                                </div>
                            </div>
                        </form>

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


    <!-- <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var votingChoice = "<?php echo $voting_choice; ?>";
            var userId = "<?php echo $current_user_id; ?>";
            var userName = "<?php echo $current_user_name; ?>";
            var votingOptions = <?php echo $voting_options_json; ?>;
            var voteBoxPhp = <?php echo $vote_box; ?>;
            console.log(voteBoxPhp)
            console.log(votingChoice)

            voteBox = voteBoxPhp;


            var votingOptionsContainer = document.getElementById("votingOptionsContainer");
            if (votingChoice !== 'single') {
                var selectedValuesContainer = document.createElement('div');
                selectedValuesContainer.id = 'selectedValuesContainer';
                votingOptionsContainer.after(selectedValuesContainer);
            }




            var singleValue = "";
            var multipleValues = [];

            var singleUserVote = votingChoice === 'single' ? <?php echo json_encode($current_user_vote); ?> : "";
            console.log(singleUserVote)
            var multipleUserVote = votingChoice !== 'single' ? JSON.parse('<?php echo $current_user_vote; ?>') : [];
            console.log(multipleUserVote)

            if (votingChoice === "single") {

                if (singleUserVote !== 'null') {
                    singleChoice = singleUserVote;
                    singleValue = singleUserVote;
                    console.log(singleValue)
                }
                votingOptions.forEach(function(option) {
                    var label = document.createElement("label");
                    const selected = singleUserVote && option.text === singleUserVote ? 'checked' : '';
                    label.innerHTML = '<input type="radio" name="option" class="radiobtn" value="' + option
                        .text + '" ' + selected + '> ' + option.text + '<br>';
                    votingOptionsContainer.appendChild(label);
                });

                // Handle radio button change
                votingOptionsContainer.addEventListener("change", function(event) {
                    if (event.target.classList.contains("radiobtn")) {
                        singleValue = event.target.value;
                        singleChoice = singleValue
                        console.log("Single Value: ", singleChoice);
                    }
                });
            } else if (votingChoice !== "single") {
                if (multipleUserVote !== 'null' && multipleUserVote) {
                    multipleValues = multipleUserVote;
                    multipleChoices = multipleUserVote;
                    updateSelectedValuesContainer();
                }

                votingOptions.forEach(function(option) {
                    var label = document.createElement("label");
                    const selected = multipleUserVote && multipleUserVote.includes(option.text) ? 'checked' :
                        '';
                    label.innerHTML = '<input type="checkbox" name="option" class="checkbox" value="' + option
                        .text +
                        '" ' + selected + '> ' + option.text + '<br>';
                    votingOptionsContainer.appendChild(label);
                });


                votingOptionsContainer.addEventListener("change", function(event) {
                    if (event.target.classList.contains("checkbox")) {
                        if (votingChoice === "punishment") {
                            // Allow only one selection: uncheck all other checkboxes
                            document.querySelectorAll(".checkbox").forEach((checkbox) => {
                                if (checkbox !== event.target) {
                                    checkbox.checked = false;
                                }
                            });

                            // Store only the selected value
                            multipleValues = event.target.checked ? [event.target.value] : [];
                        } else {
                            // Multiple selections allowed
                            if (event.target.checked) {
                                multipleValues.push(event.target.value);
                                // initializeDragAndDrop();
                            } else {
                                var index = multipleValues.indexOf(event.target.value);
                                if (index > -1) {
                                    multipleValues.splice(index, 1);
                                    // initializeDragAndDrop();
                                }
                            }
                        }

                        multipleChoices = multipleValues;
                        console.log("Selected Values: ", multipleChoices);
                        updateSelectedValuesContainer();
                    }
                });

            }

            // Function to update the selected values container
            function updateSelectedValuesContainer() {
                selectedValuesContainer.innerHTML = '';
                if (multipleChoices !== 'null' && multipleChoices.length > 0) {
                    console.log(multipleChoices)
                    var list = document.createElement('ul');
                    list.id = "draggable-list"; // Adding the ID
                    multipleChoices && multipleChoices.forEach(function(value) {
                        var listItem = document.createElement('li');

                        listItem.textContent = value;
                        listItem.style.listStyle = "none";


                    });

                    selectedValuesContainer.appendChild(list);



                } else {
                    selectedValuesContainer.textContent = 'No options selected';
                }
            }
        });



        document.addEventListener("DOMContentLoaded", () => {
            const draggableList = document.getElementById("draggable-list");

            if (!draggableList) {
                console.error("Element with ID 'draggable-list' not found.");
                return;
            }

            let draggedItem = null;

            function initializeDragAndDrop() {
                const listItems = draggableList.querySelectorAll("li");

                listItems.forEach((item) => {
                    item.setAttribute("draggable", true);

                    item.addEventListener("dragstart", (e) => {
                        draggedItem = item;
                        e.target.classList.add("dragging");
                        setTimeout(() => (e.target.style.display = "none"), 0);
                    });

                    item.addEventListener("dragover", (e) => {
                        e.preventDefault();
                    });

                    item.addEventListener("drop", (e) => {
                        e.preventDefault();
                        if (draggedItem !== item) {
                            draggableList.insertBefore(draggedItem, item.nextSibling);
                        }
                    });

                    item.addEventListener("dragend", (e) => {
                        e.target.classList.remove("dragging");
                        e.target.style.display = "block";
                        draggedItem = null;
                    });
                });
            }

            // Initial setup
            initializeDragAndDrop();

            // MutationObserver to detect list changes and reinitialize drag-and-drop
            const observer = new MutationObserver(() => {
                initializeDragAndDrop();
            });

            observer.observe(draggableList, {
                childList: true
            });
        });
    </script> -->

    <script>
        // document.addEventListener("DOMContentLoaded", () => {
        //   const draggableList = document.getElementById("draggable-list");

        //   if (!draggableList) {
        //     console.error("Element with ID 'draggable-list' not found.");
        //     return;
        //   }

        //   let draggedItem = null;

        //   // Enable draggable property for all list items
        //   const listItems = draggableList.querySelectorAll("li");
        //   listItems.forEach((item) => {
        //     item.setAttribute("draggable", true); // Make items draggable

        //     item.addEventListener("dragstart", (e) => {
        //       draggedItem = item;
        //       e.target.classList.add("dragging");
        //       setTimeout(() => (e.target.style.display = "none"), 0);
        //     });

        //     item.addEventListener("dragover", (e) => {
        //       e.preventDefault(); // Allow dropping
        //     });

        //     item.addEventListener("drop", (e) => {
        //       e.preventDefault();
        //       if (draggedItem !== item) {
        //         // Swap items
        //         let parent = draggableList;
        //         let currentItem = item;

        //         parent.insertBefore(draggedItem, currentItem.nextSibling);
        //       }
        //     });

        //     item.addEventListener("dragend", (e) => {
        //       e.target.classList.remove("dragging");
        //       e.target.style.display = "block";
        //       draggedItem = null;
        //     });
        //   });
        // });
    </script>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var votingChoice = "<?php echo $voting_choice; ?>";
            var userId = "<?php echo $current_user_id; ?>";
            var userName = "<?php echo $current_user_name; ?>";
            var votingOptions = <?php echo $voting_options_json; ?>;
            var voteBoxPhp = <?php echo $vote_box; ?>;
            // const currentuservote = <?php echo $current_user_vote; ?>;

            console.log(voteBoxPhp);
            console.log(votingChoice);

            voteBox = voteBoxPhp;
            var votingOptionsContainer = document.getElementById("votingOptionsContainer");

            if (votingChoice !== 'single') {
                var selectedValuesContainer = document.createElement('div');
                selectedValuesContainer.id = 'selectedValuesContainer';

                selectedValuesContainer.style.display =
                    <?php echo !$current_user_vote1 ? "'block'" : ($current_user_vote1 && $current_user_comment ? "'block'" : "'none'"); ?>;

                votingOptionsContainer.after(selectedValuesContainer);
            }

            var singleValue = "";
            var multipleValues = [];
            var singleUserVote = votingChoice === 'single' ? <?php echo json_encode($current_user_vote); ?> : "";
            var multipleUserVote = votingChoice !== 'single' ? JSON.parse('<?php echo $current_user_vote; ?>') : [];

            if (votingChoice === "single") {
                if (singleUserVote !== 'null') {
                    singleChoice = singleUserVote;
                    singleValue = singleUserVote;
                }

                votingOptions.forEach(function(option) {
                    var label = document.createElement("label");
                    const selected = singleUserVote && option.text === singleUserVote ? 'checked' : '';
                    label.innerHTML = `<input type="radio" name="option" class="radiobtn" value="${option.text}" ${selected}> ${option.text}<br>`;
                    votingOptionsContainer.appendChild(label);
                });

                votingOptionsContainer.addEventListener("change", function(event) {
                    if (event.target.classList.contains("radiobtn")) {
                        singleValue = event.target.value;
                        singleChoice = singleValue;
                        console.log("Single Value: ", singleChoice);
                    }
                });
            } else {
                if (multipleUserVote !== 'null' && multipleUserVote) {
                    multipleValues = multipleUserVote;
                    multipleChoices = multipleUserVote;
                    updateSelectedValuesContainer();
                }

                votingOptions.forEach(function(option) {
                    var label = document.createElement("label");
                    const selected = multipleUserVote && multipleUserVote.includes(option.text) ? 'checked' : '';
                    label.innerHTML = `<input type="checkbox" name="option" class="checkbox" value="${option.text}" ${selected}> ${option.text}<br>`;
                    votingOptionsContainer.appendChild(label);
                });

                votingOptionsContainer.addEventListener("change", function(event) {
                    if (event.target.classList.contains("checkbox")) {
                        if (votingChoice === "punishment") {
                            document.querySelectorAll(".checkbox").forEach((checkbox) => {
                                if (checkbox !== event.target) {
                                    checkbox.checked = false;
                                }
                            });

                            multipleValues = event.target.checked ? [event.target.value] : [];
                        } else {
                            if (event.target.checked) {
                                multipleValues.push(event.target.value);
                            } else {
                                var index = multipleValues.indexOf(event.target.value);
                                if (index > -1) {
                                    multipleValues.splice(index, 1);
                                }
                            }
                        }

                        multipleChoices = multipleValues;
                        console.log("Selected Values: ", multipleChoices);
                        updateSelectedValuesContainer();
                    }
                });
            }

            function updateSelectedValuesContainer() {
                selectedValuesContainer.innerHTML = ''; // Reset only this container

                if (multipleChoices !== 'null' && multipleChoices.length > 0) {
                    console.log(multipleChoices);
                    var list = document.createElement('ul');
                    list.id = "draggable-list";

                    multipleChoices.forEach(function(value) {
                        var listItem = document.createElement('li');
                        listItem.style.listStyle = "none";

                        // Create checkbox
                        // var checkbox = document.createElement('input');
                        // checkbox.type = 'checkbox';
                        // checkbox.value = value;
                        // checkbox.checked = true;
                        // checkbox.classList.add("checkbox-item");

                        // listItem.appendChild(checkbox);
                        listItem.appendChild(document.createTextNode(" " + value));

                        // Make draggable
                        listItem.setAttribute("draggable", true);

                        list.appendChild(listItem);
                    });

                    selectedValuesContainer.appendChild(list);
                    initializeDragAndDrop();
                } else {
                    selectedValuesContainer.textContent = 'No options selected';
                }
            }

            function initializeDragAndDrop() {
                const draggableList = document.getElementById("draggable-list");
                if (!draggableList) return;

                let draggedItem = null;

                draggableList.querySelectorAll("li").forEach((item) => {
                    item.setAttribute("draggable", true);

                    item.addEventListener("dragstart", (e) => {
                        draggedItem = item;
                        e.target.classList.add("dragging");
                        setTimeout(() => (e.target.style.display = "none"), 0);
                    });

                    item.addEventListener("dragover", (e) => {
                        e.preventDefault();
                    });

                    item.addEventListener("drop", (e) => {
                        e.preventDefault();
                        if (draggedItem !== item) {
                            draggableList.insertBefore(draggedItem, item.nextSibling);
                        }
                    });

                    item.addEventListener("dragend", (e) => {
                        e.target.classList.remove("dragging");
                        e.target.style.display = "block";
                        draggedItem = null;
                    });
                });
            }

            // Observe changes in the draggable list to reinitialize drag-and-drop
            const observer = new MutationObserver(() => {
                initializeDragAndDrop();
            });

            observer.observe(selectedValuesContainer, {
                childList: true,
            });
        });
    </script>
<?php

    return ob_get_clean();
}

add_shortcode('voting_box', 'voting_box_shortcode');
