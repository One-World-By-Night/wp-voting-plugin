<?php
function personal_dashboard_shortcode()
{
    // Check if the user is logged in
    if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('author'))) {
        wp_die('You do not have permission to access this page.');
    }
    if (is_user_logged_in()) {
        // Get the current user's username
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;

        // Fetch data from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'voting';
        // $results = $wpdb->get_results("SELECT * FROM $table_name");
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");


        $completedResultData = array_filter($results, function ($item) {
            return $item->voting_stage === 'completed';
        });

        $normalResultData = array_filter($results, function ($item) {
            return $item->voting_stage === 'normal' || $item->voting_stage === 'autopass';
        });
        $draftResultData = array_filter($results, function ($item) {
            return $item->voting_stage === 'draft';
        });
        $withdrawnResultData = array_filter($results, function ($item) {
            return $item->voting_stage === 'withdrawn';
        });
        // print_r(($withdrawnResultData));
      
        $user_meta = get_userdata($current_user->ID);
        $user_roles = $user_meta->roles;

        if (in_array('author', $user_roles)) {
            $draftResultDatanew = array_filter($draftResultData, function ($item) use ($current_user) {
                return (int)$item->created_by === $current_user->ID;
            });
            $draftResultData = $draftResultDatanew;
        }



        // Display the dashboard content HTML
        ob_start(); 
?>
        <div class="owbnVP dashboard-wrapper">
            <div class="staff-dashboard">
                <h1 class="custom-heading">Welcome, <?php echo esc_html($username);
    
                ?></h1>
                
                <!-- Logout form -->
                <div class="custom--btns">
                    <a href="<?php echo esc_url(site_url('/owbn-voting-form')); ?>" class="button submitbtn votebtn">Create
                        Vote</a>
                    <form id="logout-form" action="<?php echo esc_url(wp_logout_url(site_url('/wp-login.php'))); ?>"
                        method="post">
                        <input type="submit" value="Logout" class="submitbtn">
                    </form>

                </div>

                <!-- Table of Voting Data -->
                <h2>Voting Proposals</h2>


                <section class="owbnVP custom-section">
                    <div class="wrapper">
                        <div class="tab--section">
                            <div class="tab--wrapper">
                                <div class="tabs">
                                    <ul class="tab-links">
                                        <li class="active"><a href="#tab1">Active Votes</a></li>
                                        <li><a href="#tab3">Complete</a></li>
                                        <?php 

                                        // if (is_user_logged_in() && (current_user_can('administrator')))
                                       if (is_user_logged_in()) { ?>

                                      
                                            <li><a href="#tab4">Draft</a></li>

                                        <?php

                                        }
                                        ?>
                                        <?php if (is_user_logged_in() && (!current_user_can('administrator'))) { ?>

                                            <li><a href="#tab6">Withdrawn</a></li>
                                        <?php } ?>

                                        <?php if (is_user_logged_in() && (current_user_can('administrator'))) { ?>

<li><a href="#tab5">Withdrawn</a></li>
<?php } ?>

                                    </ul>
                                    <div class="tab-content">
                                        <!-- Outputs the tab options in the admin UI. This is a copy of the code that has been moved to utils. -->
                                        <div id="tab1" class="tab active">
                                            <div class="table-area">
                                            <div class="table-responsive">
                                                <table class="custom--table desktop-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Proposal Name</th>
                                                            <th>Vote Type</th>
                                                            <th>Voting Stage</th>
                                                            <th>Proposed By</th>
                                                            <th>Seconded By</th>
                                                            <th>Opening Date</th>
                                                            <th>Closing Date</th>
                                                            <th>Visibility</th>
                                                            <th>Active Status</th>
                                                            <th>Actions</th>

                                                            <?php
                                                            if (is_user_logged_in()) {
                                                            ?>
                                                                <th>Result Ongoing</th>
                                                            <?php
                                                            }

                                                            ?>


                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($results) {
                                                            foreach ($normalResultData as $row) {
                                                                echo '<tr>';
                                                                echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                                                echo '<td>' . esc_html($row->vote_type) . '</td>';
                                                                echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                                                echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                                                echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                                                echo '<td>' . esc_html($row->opening_date) . '</td>';
                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                                echo '<td>' . esc_html($row->visibility) . '</td>';
                                                                echo '<td>' . esc_attr($row->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote')  . '</td>';
                                                                echo '<td  class="votePerson_Action">';
                                                                if (is_user_logged_in() && (current_user_can('administrator') || ($row->created_by == $current_user->ID))) {
                                                                    // echo '<a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';

                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                                                                }
                                                                echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> </td>';

                                                                // echo '<td><a href="' . esc_url(site_url('/owbn-voting-result?id=' . $row->id)) . '" class="edit-icon">Show</a> </td>';

                                                                echo '<td>';
                                                                if (is_user_logged_in() && $row->vote_type != 'Disciplinary' && $row->vote_type != 'Coordinator Elections (Full Term)' && $row->vote_type != 'Coordinator Elections (Special)'): ?>
                                                                    <!-- <a href="<?php echo esc_url(site_url('/owbn-voting-result?id=' . $row->id)); ?>" class="edit-icon">
                                                                        Show
                                                                    </a> -->

                                                                    <a href="<?php echo esc_url(site_url('/owbn-voting-result/' . $row->id . '/')); ?>" class="edit-icon">
                                                                        Show
                                                                    </a>
                                                                    
                                                                <?php else: ?>
                                                                    <!-- If vote_type is 'Disciplinary', display a non-clickable link or text -->
                                                                    <span class="disabled-link">After Closing</span>

                                                                    </td>

                                                                <?php endif; ?>
                                                        <?php

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
                                                        
                                                            <th>Actions</th>

                                                            <?php
                                                            if (is_user_logged_in()) {
                                                            ?>
                                                                <th>Result Ongoing</th>
                                                            <?php
                                                            }

                                                            ?>


                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($results) {
                                                            foreach ($normalResultData as $row) {
                                                                echo '<tr>';
                                                                echo '<td>' . esc_html($row->proposal_name) . ' <br> ' . esc_html($row->vote_type) .'</td>';
                                                             
                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                        
                                                         
                                                                echo '<td  class="votePerson_Action">';
                                                                if (is_user_logged_in() && (current_user_can('administrator') || ($row->created_by == $current_user->ID))) {
                                                                    // echo '<a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';

                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                                                                }
                                                                echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> </td>';

                                                                // echo '<td><a href="' . esc_url(site_url('/owbn-voting-result?id=' . $row->id)) . '" class="edit-icon">Show</a> </td>';

                                                                echo '<td>';
                                                                if (is_user_logged_in() && $row->vote_type != 'Disciplinary' && $row->vote_type != 'Coordinator Elections (Full Term)' && $row->vote_type != 'Coordinator Elections (Special)'): ?>
                                                                    <!-- <a href="<?php echo esc_url(site_url('/owbn-voting-result?id=' . $row->id)); ?>" class="edit-icon">
                                                                        Show
                                                                    </a> -->

                                                                    <a href="<?php echo esc_url(site_url('/owbn-voting-result/' . $row->id . '/')); ?>" class="edit-icon">
                                                                        Show
                                                                    </a>
                                                                    
                                                                <?php else: ?>
                                                                    <!-- If vote_type is 'Disciplinary', display a non-clickable link or text -->
                                                                    <span class="disabled-link">After Closing</span>

                                                                    </td>

                                                                <?php endif; ?>
                                                        <?php

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

                                        <div id="tab3" class="tab">
                                            <div class="table-area">
                                            <div class="table-responsive">
                                                <table class="custom--table desktop-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Proposal Name</th>
                                                            <th>Vote Type</th>
                                                            <th>Voting Stage</th>
                                                            <th>Proposed By</th>
                                                            <th>Seconded By</th>
                                                            <th>Opening Date</th>
                                                            <th>Closing Date</th>
                                                            <th>Visibility</th>
                                                            <th>Active Status</th>
                                                            <?php 
                                                               if (is_user_logged_in() && (current_user_can('administrator'))) {
                                                                ?>
                                                                <th>Action</th>
                                                         <?php 
                                                               }
                                                               ?>
                                                            <th>Result</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($results) {
                                                            foreach ($completedResultData as $row) {
                                                                echo '<tr>';
                                                                echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                                                echo '<td>' . esc_html($row->vote_type) . '</td>';
                                                                echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                                                echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                                                echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                                                echo '<td>' . esc_html($row->opening_date) . '</td>';
                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                                echo '<td>' . esc_html($row->visibility) . '</td>';
                                                                echo '<td>' . esc_attr($row->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote')  . '</td>';
                                                                if (is_user_logged_in() && (current_user_can('administrator'))) {
                                                                    echo '<td  class="votePerson_Action">';
                                                                  
                                                                    // echo '<a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                  
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                                                                    echo '</td>';
                                                                }
                                                             
                                                                echo '<td><a href="' . esc_url(site_url('/owbn-voting-result/' . $row->id . '/')) . '" class="edit-icon">Show</a></td>';


                                                                // echo '<td><a href="' . esc_url(site_url('/owbn-voting-result?id=' . $row->id)) . '" class="edit-icon">Show</a> </td>';
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
                                                          
                                                          
                                                            <?php 
                                                               if (is_user_logged_in() && (current_user_can('administrator'))) {
                                                                ?>
                                                                <th>Action</th>
                                                         <?php 
                                                               }
                                                               ?>
                                                            <th>Result</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($results) {
                                                            foreach ($completedResultData as $row) {
                                                                echo '<tr>';
                                                                echo '<td>' . esc_html($row->proposal_name) .' <br>' . esc_html($row->vote_type) . '</td>';
                                                              
                                                                echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                     
                                                              
                                                                if (is_user_logged_in() && (current_user_can('administrator'))) {
                                                                    echo '<td  class="votePerson_Action">';
                                                                  
                                                                    // echo '<a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                                                                  
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                                                                    echo '</td>';
                                                                }
                                                             
                                                                echo '<td><a href="' . esc_url(site_url('/owbn-voting-result/' . $row->id . '/')) . '" class="edit-icon">Show</a></td>';


                                                                // echo '<td><a href="' . esc_url(site_url('/owbn-voting-result?id=' . $row->id)) . '" class="edit-icon">Show</a> </td>';
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

                                        <?php if (is_user_logged_in()) { ?>
                                            <div id="tab4" class="tab">
                                                <div class="table-area">
                                                <div class="table-responsive">
                                                    <table class="custom--table  desktop-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Proposal Name</th>
                                                                <th>Vote Type</th>
                                                                <th>Voting Stage</th>
                                                                <th>Proposed By</th>
                                                                <th>Seconded By</th>
                                                                <th>Opening Date</th>
                                                                <th>Closing Date</th>
                                                                <th>Visibility</th>
                                                                <th>Active Status</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (0 < count($draftResultData)) {
                                                                foreach ($draftResultData as $row) {

                                                                    echo '<tr>';
                                                                    echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                                                    echo '<td>' . esc_html($row->vote_type) . '</td>';
                                                                    echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                                                    echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                                                    echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                                                    echo '<td>' . esc_html($row->opening_date) . '</td>';
                                                                    echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                                    echo '<td>' . esc_html($row->visibility) . '</td>';
                                                                    echo '<td>' . esc_attr($row->active_status === 'activeVote' ? 'Active Vote' : 'Inactive Vote')  . '</td>';
                                                                    echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    </a> ';
                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                   </a>';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
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



                                                    <table class="custom--table mobile-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Proposal Name</th>
                                                               
                                                                <th>Closing Date</th>
                                                          
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (0 < count($draftResultData)) {
                                                                foreach ($draftResultData as $row) {

                                                                    echo '<tr>';
                                                                    echo '<td>' . esc_html($row->proposal_name) .'br' . esc_html($row->vote_type) . '</td>';
                                                                 
                                                                    echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                                 
                                                                   
                                                                    echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    </a> ';
                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                   </a>';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
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
                                                </div>
                                            </div>
                                            <div id="tab5" class="tab">
                                          
                                          
                                            <div class="table-area">
                                            <div class="table-responsive">
                                                    <table class="custom--table desktop-table">
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
                                                                    echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    </a> ';
                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                   </a>';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
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


                                                    <table class="custom--table mobile-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Proposal Name</th>
                                                            
                                                                <th>WithdrawnAt</th>
                                                   
                                                                <th>Closing Date</th>
                                                           
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (0 < count($withdrawnResultData)) {
                                                                foreach ($withdrawnResultData as $row) {

                                                                    echo '<tr>';
                                                                    echo '<td>' . esc_html($row->proposal_name) . 'br>'  . esc_html($row->vote_type) . '</td>';
                                                                 
                                                                    echo '<td>' . esc_html($row->withdrawn_time) . '</td>';

                                                            
                                                                    echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                             
                                                                    echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    </a> ';
                                                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                   </a>';
                                                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
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
                                                </div>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        //  if (is_user_logged_in() && (!current_user_can('administrator'))) { 
                                        
                                        ?>
                                            <div id="tab6" class="tab">
                                                <div class="table-area">
                                                <div class="table-responsive">
                                                    <table class="custom--table desktop-table">
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
                                                                <!-- <th>Actions</th> -->
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
                                                                    //     echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    //     </a> ';
                                                                    //     echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                    //    </a>';
                                                                    //     echo '<a href="' . esc_url(site_url('/owbn-preview-vote?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
                                                                    //    </a> </td>';
                                                                    // echo '<td><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil" aria-hidden="true"></i></a> </td>';
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
                                                            
                                                                <th>WithdrawnAt</th>

                                                                <th>Closing Date</th>
                                                              
                                                                <!-- <th>Actions</th> -->
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (0 < count($withdrawnResultData)) {
                                                                foreach ($withdrawnResultData as $row) {

                                                                    echo '<tr>';
                                                                    echo '<td>' . esc_html($row->proposal_name) . 'br'  . esc_html($row->vote_type) . '</td>';
                                                             
                                                                    echo '<td>' . esc_html($row->withdrawn_time) . '</td>';

                                                                    echo '<td>' . esc_html($row->closing_date) . '</td>';
                                                                 
                                                                    //     echo '<td  class="votePerson_Action"><a href="' . esc_url(site_url('/owbn-voting-form?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                                                                    //     </a> ';
                                                                    //     echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                                                                    //    </a>';
                                                                    //     echo '<a href="' . esc_url(site_url('/owbn-preview-vote?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
                                                                    //    </a> </td>';
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
                                                </div>
                                            </div>
                                        <?php
                                        //  } 
                                        
                                        
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>



                <!-- <div class="table-area">
                    <table>
                        <thead>
                            <tr>
                                <th>Proposal Name</th>
                                <th>Vote Type</th>
                                <th>Voting Stage</th>
                                <th>Proposed By</th>
                                <th>Seconded By</th>
                                <th>Opening Date</th>
                                <th>Closing Date</th>
                                <th>Visibility</th>
                               
                                <th>Active Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($results) {
                                foreach ($results as $row) {
                                    echo '<tr>';
                                    echo '<td>' . esc_html($row->proposal_name) . '</td>';
                                    echo '<td>' . esc_html($row->vote_type) . '</td>';
                                    echo '<td>' . esc_html($row->voting_stage) . '</td>';
                                    echo '<td>' . esc_html($row->proposed_by) . '</td>';
                                    echo '<td>' . esc_html($row->seconded_by) . '</td>';
                                    echo '<td>' . esc_html($row->opening_date) . '</td>';
                                    echo '<td>' . esc_html($row->closing_date) . '</td>';
                                    echo '<td>' . esc_html($row->visibility) . '</td>';

                                    echo '<td>' . esc_html($row->active_status) . '</td>';
                                    echo '<td><a href="' . esc_url(site_url('/owbn-voting-form/' . $row->id . '/')) . '" class="edit-icon"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>

                            </a> ';
                                    echo '<a href="#" class="delete-icon" onclick="deleteVote(' . $row->id . ')"><i class="fa fa-trash-o" aria-hidden="true"></i>
                           </a>';
                                    echo '<a href="' . esc_url(site_url('/owbn-preview-vote?id=' . $row->id)) . '" class="edit-icon"><i class="fa fa-eye" aria-hidden="true"></i>
                           </a> </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="11">No voting proposals found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div> -->
            </div>
        </div>
        <script>
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
add_shortcode('personal_dashboard', 'personal_dashboard_shortcode');
