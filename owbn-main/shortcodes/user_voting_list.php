<?php
function user_voting_list_shortcode()
{

    $voteList = getUserVoteList();
    ob_start();

    // Your HTML content or function calls to generate the preview content
?>
    <section class="owbnVP vote--result">
        <div class="vote--wrapper">
            <div class="vote--row">
                <div class="col">
                    <div class="vote--result--area">
                        <div class="container">

                            <table id="example" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>CM Vote</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <?php if ($voteList) { ?>
                                        <?php foreach ($voteList as $row) { ?>
                                            <tr>
                                                <td><?php echo $row->proposal_name; ?></td>
                                                <td><?php echo $row->vote_type; ?></td>
                                                <td><?php echo date('d-M-Y', strtotime($row->closing_date)); ?></td>
                                                <td><?php echo date('d-M-Y', strtotime($row->opening_date)); ?></td>
                                                <td><?php echo $row->vote_box; ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="5" class="text-center"> Data Not Found</td>
                                        </tr>
                                    <?php } ?> -->

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>ID</th>
                                        <th>Proposal Name</th>
                                        <th>Vote Type</th>
                                        <th>Closing Date</th>
                                        <th>Opening Date</th>
                                        <th>CM Vote</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            var username = $('.arm_data_value').first().text();

            jQuery(document).ready(function($) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fetch_data',
                        username: username
                    },
                    success: function(response) {
                        var responseData = JSON.parse(response);
                        var data = responseData.data;
                        let userId=responseData.user.ID;
                        console.log(data)
                        $('#example').DataTable({
                            //"pageLength": 1 ,
                            data: data,
                            columns: [{
                                    data: 'id'
                                },
                                {
                                    data: 'proposal_name'
                                },
                                {
                                    data: 'vote_type'
                                },
                                {
                                    data: 'opening_date'
                                },
                                {
                                    data: 'closing_date'
                                },
                                {
                                    data: 'vote_box',
                                    render: function(data, type, row) {
                                        try {
                                            var voteBox = JSON.parse(data);
                                            var userFound = voteBox.some(function(vote) {
                                                return vote.userId === userId;
                                            });
                                            if (userFound) {
                                                return '<span class="vote_box_meesage success">vote submitted</span>';
                                            } else {
                                                return '<span class="vote_box_meesage">No vote submitted</span>';
                                            }
                                        } catch (error) {
                                            console.error('Error parsing vote_box:', error);
                                            return '<span class="vote_box_meesage"></span>';
                                        }
                                    }
                                }
                            ]
                        });
                    },
                    error: function(xhr, error, thrown) {
                        console.error("Error occurred:", error, thrown);
                        console.log("Response:", xhr.responseText);
                    }
                });
            });

        });
    </script>
<?php

    return ob_get_clean();
    // }
}
add_shortcode('user_voting_list', 'user_voting_list_shortcode');

// Add AJAX action for logged-in users
add_action('wp_ajax_fetch_data', 'fetch_data');

// Add AJAX action for non-logged-in users (if needed)
add_action('wp_ajax_nopriv_fetch_data', 'fetch_data');

function fetch_data()
{
    global $wpdb;
    // Retrieve pagination parameters
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';

    $user = get_user_by('login', $username);

    // Prepare SQL query with LIMIT for pagination
    $table_name = $wpdb->prefix . 'voting'; // Adjust table name accordingly

    // Construct query
    $query = "SELECT  id,proposal_name,vote_type,opening_date,closing_date,vote_box  FROM $table_name WHERE voting_stage = %s";
    $query_params = ['completed'];

    // Append pagination
    $query .= " LIMIT %d, %d";
    $query_params[] = $start;
    $query_params[] = $length;

    // Prepare and execute query
    $query = $wpdb->prepare($query, ...$query_params);
    $results = $wpdb->get_results($query, ARRAY_A);

    // Count total records (for pagination)
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE voting_stage = 'completed'");

    // Return data as JSON
    echo json_encode([
        'draw' => intval($_POST['draw']),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $results,
        'user' => $user
    ]);
    wp_die(); // Required to terminate immediately and return a proper response
}