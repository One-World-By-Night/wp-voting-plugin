<?php

/**
 * Handles submitting voting form. This function is called from the admin page and should not be called directly
 */
function submit_voting_form()
{
    // print_r($_POST);
    // print_r($_REQUEST);
    // die;
    // Check if the user is logged in
    if (is_user_logged_in()) {
        // Get the current user's ID
        $current_user_id = get_current_user_id();

        // Decode JSON data sent from the client
        $data = json_decode(file_get_contents('php://input'), true);
        // print_r($data);s
        //die;
        // Check if the data is valid
        if ($data && isset($data['proposalName'])) {
            // Sanitize and process other form fields...
            $proposal_name = sanitize_text_field($data['proposalName']);
            $content = $data['content'];
            $remark=$data['remark'];
            $voting_options = serialize($data['votingOptions']);
            $files_name = serialize($data['filesName']);
            $vote_type = sanitize_text_field($data['voteType']);
            $voting_choice = sanitize_text_field($data['votingChoice']);
            $voting_stage = sanitize_text_field($data['votingStage']);
            $proposed_by = sanitize_text_field($data['proposedBy']);
            $seconded_by = sanitize_text_field($data['secondedBy']);
            $create_date = sanitize_text_field($data['createDate']);
            $opening_date = sanitize_text_field($data['openingDate']);
            $closing_date = sanitize_text_field($data['closingDate']);
            $visibility = sanitize_text_field($data['visibility']);
            $BlindVoting = sanitize_text_field($data['blindVoting']);

            $maximum_choices = sanitize_text_field($data['maximumChoices']);
            $number_of_winner = sanitize_text_field($data['number_of_winner']);
            $withdrawntime = sanitize_text_field($data['withdrawntime']);

            $active_status = sanitize_text_field($data['activeStatus']);

            // Insert data into the voting table
            global $wpdb;
            $table_name = $wpdb->prefix . 'voting';
            if (isset($data['id']) && ($data['id']!=0)) {




                // if ($data['votingStage'] == 'withdrawn' && $data['votingstage2'] == 'normal' || $data['votingStage'] == 'withdrawn' && $data['votingstage2'] == 'autopass') {
                //     // Get the current date and time
                //     $current_date = date('Y-m-d H:i:s'); // Current date and time

                //     // Add 1 day to the current date
                //     $increased_date = date('Y-m-d H:i:s', strtotime($current_date . ' +1 day'));

                   

                //     $closing_date = date('Y-m-d H:i:s', strtotime($current_date . ' +1 day'));
                //     // error_log("Closing Date with Time: " . $closing_date);
                // }
                // Update existing row
                $wpdb->update(
                    $table_name,
                    array(
                        'proposal_name' => $proposal_name,
                        'content' => $content,
                        'voting_choice' => $voting_choice,
                        'voting_options' => $voting_options,
                        'files_name' => $files_name,
                        'vote_type' => $vote_type,
                        'voting_stage' => $voting_stage,
                        'proposed_by' => $proposed_by,
                        'seconded_by' => $seconded_by,
                        'create_date' => $create_date,
                        'opening_date' => $opening_date,
                        'closing_date' => $closing_date,
                        'visibility' => $visibility,
                        'blindVoting'=>$BlindVoting,
                        'maximum_choices' => $maximum_choices,
                        'number_of_winner' => $number_of_winner,
'remark'=>$remark,
                        'active_status' => $active_status,

                        'withdrawn_time'=>$withdrawntime
               //         'created_by' => $current_user_id, // Set the created_by column to the current user's ID
                    ),
                    array('id' => $data['id']) // Where clause for updating the specific row
                );
            } else {
                // Insert new row
                $wpdb->insert(
                    $table_name,
                    array(
                        'proposal_name' => $proposal_name,
                        'content' => $content,
                        'voting_choice' => $voting_choice,
                        'voting_options' => $voting_options,
                        'files_name' => $files_name,
                        'vote_type' => $vote_type,
                        'voting_stage' => $voting_stage,
                        'proposed_by' => $proposed_by,
                        'seconded_by' => $seconded_by,
                        'create_date' => $create_date,
                        'opening_date' => $opening_date,
                        'closing_date' => $closing_date,
                        'visibility' => $visibility,

                       'blindVoting'=>$BlindVoting,
                        'maximum_choices' => $maximum_choices,
                        'number_of_winner' => $number_of_winner,
'remark'=>$remark,
                        'active_status' => $active_status,
                        'vote_box' => '[]',
                        'created_by' => $current_user_id, // Set the created_by column to the current user's ID

                        'withdrawn_time'=>$withdrawntime

                    )
                );
            }
            wp_send_json_success(array('redirect_url' => site_url('/owbn-personal-dashboard')));
            wp_die(); // This is required to terminate AJAX requests
        } else {
            // Proposal name is empty, handle error or return response
            wp_send_json_error('Proposal name is empty');
        }
    } else {
        // User is not logged in, handle error or return response
        wp_send_json_error('User is not logged in');
    }
}


/**
 * AJAX Callback for deleting a vote from the database. Used to clean up after a vote has been
 */
function delete_vote_callback()
{
    // Delete a vote from the database
    if (isset($_POST['id'])) {
        // Get the vote ID from the AJAX request
        $vote_id = intval($_POST['id']);

        // Delete the vote from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'voting';
        $wpdb->delete($table_name, array('id' => $vote_id), array('%d'));

        // Return a response
        wp_send_json_success('Vote deleted successfully');
    } else {
        // If no ID is provided, return an error response
        wp_send_json_error('No vote ID provided');
    }
}

/**
 * Retrieve data associated with a voting. This function is used to retrieve vote data from the database.
 * 
 * @param $vote_id
 * 
 * @return Array of data or false if not found. Note that the array is indexed by ID and not by object
 */
function get_vote_data_by_id($vote_id)
{
    global $wpdb;
    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}voting WHERE id = %d", $vote_id);
    $result = $wpdb->get_row($query, ARRAY_A);
    if (!$result) {
        // Handle case where no row is found
        return null; // Or throw an exception, log an error, etc.
    }
    return $result;
}


/**
 * Handles submitting voting box data to the database This function is called via AJAX to submit the voting
 */
function submit_voting_box()
{
    // Check if the user is logged in
    if (is_user_logged_in()) {
        // Get the current user's ID
        $current_user_id = get_current_user_id();

        // Decode JSON data sent from the client
        $data = json_decode(file_get_contents('php://input'), true);

        // Check if the data is valid
        if ($data) {
            $voteId = $data['voteId'];
            $vote_box = json_encode($data['voteBox']); // Ensure vote_box is a JSON encoded string

            // Insert data into the voting table
            global $wpdb;
            $table_name = $wpdb->prefix . 'voting';
            if (isset($data['voteId'])) {
                // Update existing row
                $wpdb->update(
                    $table_name,
                    array(
                        'vote_box' => $vote_box,
                    ),
                    array('id' => $data['voteId']) // Where clause for updating the specific row
                );
            }
            wp_send_json_success(array('redirect_url' => site_url('/owbn-elections-dashboard')));
            wp_die(); // This is required to terminate AJAX requests
        } else {
            // Data is empty or invalid, handle error or return response
            wp_send_json_error('Invalid data');
        }
    } else {
        // User is not logged in, handle error or return response
        wp_send_json_error('User is not logged in');
    }
}


/**
 * Upload file callback function for ajax request to upload a file to the media library. It is used to upload files
 */
function upload_file_callback()
{
    $file = $_FILES['file'];
    $upload = wp_handle_upload($file, array('test_form' => false));

    // function to create an attachment
    if ($upload && !isset($upload['error'])) {
        $attachment = array(
            'guid' => $upload['url'],
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        echo json_encode(array('success' => true, 'attachment_id' => $attach_id, 'url' => $upload['url']));
    } else {
        echo json_encode(array('success' => false, 'error' => $upload['error']));
    }
    wp_die();
}


/**
 * Delete a file from media library via AJAX. This function is used to delete an attachment that was uploaded via AJAX.
 * 
 * 
 * @return Response as JSON with success or failure message. On success the message is'File deleted successfully. '
 */
function delete_file_from_media_library()
{
    // Check the nonce for security
    check_ajax_referer('upload_file_nonce', 'security');

    // Get the attachment ID from the AJAX request
    $attachment_id = intval($_POST['attachment_id']);

    // Check if the attachment exists
    // Get the attachment from the database.
    if (!get_post($attachment_id)) {
        wp_send_json_error(array('message' => 'File not found.'));
        return;
    }

    // Delete the attachment
    $deleted = wp_delete_attachment($attachment_id, true);

    // Delete file. If file was deleted successfully.
    if ($deleted) {
        wp_send_json_success(array('message' => 'File deleted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Error deleting file.'));
    }
}

//Get TimeZone 
function get_timezone_abbreviation()
{
    // Get the timezone string from WordPress options
    $timezone_string = get_option('timezone_string');
    $gmt_offset = get_option('gmt_offset');

    // Default to UTC if no timezone is set
    if ($timezone_string) {
        $timezone = new DateTimeZone($timezone_string);
    } elseif ($gmt_offset) {
        $hours = intval($gmt_offset);
        $minutes = abs($gmt_offset - $hours) * 60;
        $offset_string = sprintf('%+03d:%02d', $hours, $minutes);

        // Map common GMT offsets to timezone strings
        $offset_to_timezone = [
            '-12:00' => 'Pacific/Kwajalein',
            '-11:00' => 'Pacific/Samoa',
            '-10:00' => 'Pacific/Honolulu',
            '-09:00' => 'America/Juneau',
            '-08:00' => 'America/Los_Angeles',
            '-07:00' => 'America/Denver',
            '-06:00' => 'America/Chicago',
            '-05:00' => 'America/New_York', // EST/EDT
            '-04:00' => 'America/Halifax',
            '-03:00' => 'America/Argentina/Buenos_Aires',
            '-02:00' => 'Atlantic/South_Georgia',
            '-01:00' => 'Atlantic/Azores',
            '+00:00' => 'Europe/London', // UTC
            '+01:00' => 'Europe/Berlin',
            '+02:00' => 'Europe/Helsinki',
            '+03:00' => 'Europe/Moscow',
            '+04:00' => 'Asia/Dubai',
            '+05:00' => 'Asia/Karachi',
            '+06:00' => 'Asia/Dhaka',
            '+07:00' => 'Asia/Bangkok',
            '+08:00' => 'Asia/Singapore',
            '+09:00' => 'Asia/Tokyo',
            '+10:00' => 'Australia/Sydney',
            '+11:00' => 'Pacific/Guadalcanal',
            '+12:00' => 'Pacific/Fiji',
        ];

        if (array_key_exists($offset_string, $offset_to_timezone)) {
            $timezone = new DateTimeZone($offset_to_timezone[$offset_string]);
        } else {
            $timezone = new DateTimeZone('UTC'); // Default to UTC if no match found
        }
    } else {
        $timezone = new DateTimeZone('UTC'); // Default to UTC if no timezone is set
    }

    // Create a DateTime object with the specified timezone
    $date = new DateTime('now', $timezone);

    // Check if the timezone is observing DST
    $isDST = $date->format('I');

    // Get the base timezone name without DST
    $timezone_abbreviation = $isDST ? $timezone->getTransitions(time(), time())[0]['abbr'] : $date->format('T');

    return $timezone_abbreviation;
}

// Get User Vote List 
function getUserVoteList()
{

    //if (is_user_logged_in()) {
    //    // Get the current user's ID
    //   $user_id = get_current_user_id();
    global $wpdb;

    // Construct the query
    $query = $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}voting WHERE voting_stage = %s",
        'completed'
        // "SELECT * FROM {$wpdb->prefix}voting WHERE JSON_CONTAINS(vote_box, %s, '$') AND voting_stage = %s",
        // json_encode(array('userId' => $user_id)), 'completed'
    );

    return $results = $wpdb->get_results($query);
    //} else {
    //    // User is not logged in, handle error or return response
    //   wp_send_json_error('User is not logged in');
    //}
}
