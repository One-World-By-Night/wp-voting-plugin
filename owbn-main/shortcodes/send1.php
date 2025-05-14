<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
// require __DIR__ . '/remark.php';


// Include WordPress functions if necessary (adjust path accordingly)
// require_once('../../../wp-load.php');  // Adjust path to load WP environment



function get_all_users()
{
    global $wpdb;

    // Query to fetch all user emails and their display names
    $query = "SELECT user_email, display_name FROM {$wpdb->users}";

    // Execute the query and fetch results
    return $wpdb->get_results($query); // Returns an array of objects
}

function get_upcoming_votes()
{
    // new comment
    global $wpdb;

    // Get today's date and tomorrow's date
    $today = date("Y-m-d"); // Current date
    $tomorrow = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date
    error_log("testing mail");

    // Query to fetch votes from wp_voting table with opening date condition
    $query = "
    SELECT * 
    FROM {$wpdb->prefix}voting 
    WHERE opening_date = '$tomorrow' AND active_status = 'activeVote'
";

    echo $query;
    $results = $wpdb->get_results($wpdb->prepare($query, $tomorrow));

    // Debugging: output the query results
    echo "<pre>";
    
    print_r($results);
    echo "</pre>";

    // Return the results
    return $results;
}
function get_result_votes()
{
    global $wpdb;
    // $query = "SELECT id FROM {$wpdb->prefix}voting";
    $today = date("Y-m-d"); // Current date
    // $tomorrow = date("Y-m-d", strtotime("-1 day")); // Tomorrow's date

    // Query to fetch votes from wp_voting table with opening date condition
//     $query = "
//     SELECT * 
//     FROM {$wpdb->prefix}voting 
//     WHERE opening_date = '$today' AND voting_stage = 'completed'
// ";

$query = "
    SELECT * 
    FROM {$wpdb->prefix}voting 
    WHERE opening_date = '$today' 
      AND voting_stage IN ('completed', 'autopass')
";

    // Fetch results as an associative array
    $results = $wpdb->get_results($query, ARRAY_A);

    if ($results) {
        // Extract the IDs into a plain array and convert to integers
        $vote_ids = array_map('intval', array_column($results, 'id'));
        return $vote_ids;

    }

    // Return an empty array if no results found
    return [];
}

function get_closing_votes()
{
    global $wpdb;

    // Get today's date and tomorrow's date
    $today = date("Y-m-d"); // Current date
    $tomorrow = date("Y-m-d", strtotime("+1 day")); // Tomorrow's date

    // Query to fetch votes from wp_voting table with opening date condition
    $query = "
    SELECT * 
    FROM {$wpdb->prefix}voting 
    WHERE closing_date = '$tomorrow' AND active_status = 'activeVote'
";
    echo $query;
    $results = $wpdb->get_results($wpdb->prepare($query, $tomorrow));

    // Debugging: output the query results
    echo "<pre>";
    print_r($results);
    echo "</pre>";

    // Return the results
    return $results;
}
// Check if the form was submitted
// if (isset($_POST["send"])) 
function Sendmail()
{
    // Get user data (emails and names)
    $users = get_all_users();

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';  // Example SMTP server (replace with your server)
        $mail->SMTPAuth = true;
        $mail->Username = '64c6164952528d'; // SMTP username
        $mail->Password = '3b8b0d53b80d14'; // SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender info
        $mail->setFrom('aashish@gmail.com', 'Muskan');

        // Get upcoming votes
        // Fetch votes opening and closing tomorrow
        $votesOpening = get_upcoming_votes();
        $votesClosing = get_closing_votes();
        $voteRanking = get_result_votes();

        $image_path = '/wp-content/uploads/sites/2/2024/05/OWBN-logo_wht-red-1.png';
        $image_url = get_site_url() . $image_path;
        
        echo '<img src="' . esc_url($image_url) . '" alt="OWBN Logo">';
        // Send emails for opening votes
        if (!empty($votesOpening)) {
            foreach ($votesOpening as $vote) {
                if($vote->voting_stage!="autopass"){
                    foreach ($users as $user) {
                        // Prepare email body for opening votes
                        $emailBody = Openingbody($user, $image_url, $vote);
        
        
        
        
        
                        // Send the email
                        $mail->addAddress($user->user_email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Votes opening Tomorrow->' . $vote->id;
                        $mail->Body = $emailBody;
                        echo $emailBody;
                        if ($mail->send()) {
                            error_log("opening votes email sent to");
                            echo "Closing votes email sent to: " . $user->user_email . "<br>";
                        } else {
                            error_log('opening votes email error sent to' .$mail->ErrorInfo);
                            echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
                        }
                        $mail->clearAddresses();
                    }
                }
            }
        
        } else {
            echo "No votes opening tomorrow.";
        }

        // Send emails for closing votes
        if (!empty($votesClosing)) {
            foreach ($votesClosing as $vote) {
                if($vote->voting_stage!="autopass"){

                foreach ($users as $user) {
                    // Prepare email body for opening votes
                    $emailBody = Closingbody($user, $image_url, $vote);





                    // Send the email
                    $mail->addAddress($user->user_email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Votes Closing Tomorrow->' . $vote->id;

                    $mail->Body = $emailBody;
                    echo $emailBody;
                    if ($mail->send()) {
                        echo "Closing votes email sent to: " . $user->user_email . "<br>";
                    } else {
                        echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
                    }
                    $mail->clearAddresses();
                }
            }
            }
        } else {
            echo "No votes closing tomorrow.";
        }



        if (!empty($voteRanking)) {
            foreach ($voteRanking as $vote_id) {

                error_log("Processing vote ID: " . $vote_id);

                // $vote_id = $all_vote->id;
                // foreach ($users as $user) {



                // Prepare email body for closing votes
                $result = Remarksdata($vote_id);

                $emailBody = $result['email_body'];
                $vote_box_array_new = $result['vote_box_data'];

                // Initialize an array to store all user emails
                $user_emails = [];

                foreach ($vote_box_array_new as $option) {
                    // Get the user object by ID
                    $user = get_user_by('id', $option['userId']);

                    if ($user) {
                        // Collect user email
                        $user_emails[] = $user->user_email;
                    }
                }

                // Now $user_emails contains all the user emails
                print_r($user_emails);
                // Send the email
                foreach ($user_emails as $user_email) {
                    $mail->addAddress($user_email);
                    $mail->isHTML(true);
                    // $mail->Subject = 'Votes Result';
                $mail->Subject = 'Votes Result->' . $vote_id;

                    $mail->Body = $emailBody;
                    echo $emailBody;
                    // if ($mail->send()) {
                    //     echo "Result votes email sent to: " . $user->user_email . "<br>";
                    // } else {
                    //     echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
                    // }
                    $mail->clearAddresses();
                }
                // }
            }
        } else {
            echo "No votes closing tomorrow.";
        }
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

