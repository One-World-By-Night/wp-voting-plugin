<?php

// function sendmail_shortcode() {
//     ob_start(); // Start output buffering

//     // Call the Sendmail function
//     Sendmail();

//     // Get the buffered content
//     $output = ob_get_clean();

//     // Return the content for the shortcode
//     return $output;
// }



// if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
//     // Include PHPMailer
//     require __DIR__ . '/phpmailer/src/Exception.php';
//     require __DIR__ . '/phpmailer/src/PHPMailer.php';
//     require __DIR__ . '/phpmailer/src/SMTP.php';
// }
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    error_log('PHPMailer already loaded');
}




// function get_all_users()
// {
//     global $wpdb;

//     // Query to fetch all user emails and their display names
//     $query = "SELECT user_email, display_name FROM {$wpdb->users}";

//     // Execute the query and fetch results
//     return $wpdb->get_results($query); // Returns an array of objects
// }


function get_all_users() {
    global $wpdb;
    $all_users = [];

    if (is_multisite()) {
        // Multisite: Collect all users from all sites
        $sites = get_sites();

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $admin_users = get_users([
                'role'   => 'administrator',
                'fields' => ['user_email', 'display_name','ID','user_login']
            ]);
            $author_users = get_users([
                'role'   => 'armember',
                'fields' => ['user_email', 'display_name','ID','user_login']
            ]);

            // Merge users
            $all_users = array_merge($all_users, $admin_users, $author_users);

            restore_current_blog();
        }
    } else {
        // Single site: Collect admins and authors
        $admin_users = get_users([
            'role'   => 'administrator',
            'fields' => ['user_email', 'display_name','ID','user_login']
        ]);
        $author_users = get_users([
            'role'   => 'armember',
            'fields' => ['user_email', 'display_name','ID','user_login']
        ]);

        // Merge users
        $all_users = array_merge($admin_users, $author_users);
    }

    return $all_users;

print_r($all_users);
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
    FROM wp_2_voting 
    WHERE opening_date = '$today' AND active_status = 'activeVote'
";
error_log($query);
    echo $query;
    $results = $wpdb->get_results($wpdb->prepare($query, $today));

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
    FROM wp_2_voting 
    WHERE closing_date = '$today' 
      AND voting_stage IN ('completed', 'autopass')
";
echo $query;
error_log($query);
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
    FROM wp_2_voting 
    WHERE closing_date = '$tomorrow' AND active_status = 'activeVote'
";

error_log("Today's date is: " . $today);
error_log("tomorrow's date is: " . $tomorrow);
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
    error_log(print_r($users, true));

  require __DIR__ . '/phpmailer/src/Exception.php';
    require __DIR__ . '/phpmailer/src/PHPMailer.php';
    require __DIR__ . '/phpmailer/src/SMTP.php';
    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    // try {
    //     // SMTP Configuration
    //     error_log("smtp assign");
    //     $mail->isSMTP();
    //     $mail->Host = 'smtp.gmail.com';  // Example SMTP server (replace with your server)
    //     $mail->SMTPAuth = true;
    //     $mail->Username = 'votes@owbn.net'; // SMTP username
    //     $mail->Password = 'ezzv pzhr kezz kned';
    //     // $mail->Username = 'votes@owbn.net'; // SMTP username
    //     // $mail->Password = 'N7UMmQeZuLFC8hG='; 
        
    //     $mail->SMTPSecure = 'tls';
    //     $mail->Port = 587;
    //     $mail->SMTPDebug = 0;  // Enable detailed debug output
    //     $mail->Debugoutput = 'error_log';  // Log output to PHP error log
        
    //     // Sender info
    //     $mail->setFrom('votes@owbn.net', 'OWBN');
    //     error_log("smtp process");

    //     // Get upcoming votes
    //     // Fetch votes opening and closing tomorrow
    //     $votesOpening = get_upcoming_votes();
    //     $votesClosing = get_closing_votes();
    //     // $voteRanking = get_result_votes();
    //     // error_log("testupcomingvotes: " . print_r($voteRanking, true));




    //     $image_path = '/wp-content/uploads/sites/2/2024/05/OWBN-logo_wht-red-1.png';
    //     $image_url = get_site_url() . $image_path;
        
    //     echo '<img src="' . esc_url($image_url) . '" alt="OWBN Logo">';
    //     // Send emails for opening votes
    //     if (!empty($votesOpening)) {
    //         foreach ($votesOpening as $vote) {
    //             if($vote->voting_stage!="autopass"){
    //                 foreach ($users as $user) {
    //                     // Prepare email body for opening votes
    //                     $emailBody = Openingbody($user, $image_url, $vote);
        
        
        
        
        
    //                     // Send the email
    //                     $mail->addAddress($user->user_email);
    //                     $mail->isHTML(true);
    //                     $mail->Subject = 'Votes opening Tomorrow->' . $vote->id;
    //                     $mail->Body = $emailBody;
    //                     sleep(1);
    //                     // echo $emailBody;
    //                     if ($mail->send()) {
    //                         error_log("opening votes email sent to");
                            
    //                         // echo "opening votes email sent to: " . $user->user_email . "<br>";
    //                     } else {
    //                         error_log('opening votes email error sent to' .$mail->ErrorInfo);
    //                         echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
    //                     }
    //                     $mail->clearAddresses();
    //                 }
    //             }
    //         }
        
    //     } else {
    //         echo "No votes opening tomorrow.";
    //     }


        
    //     // Send emails for closing votes
    //     if (!empty($votesClosing)) {
    //         foreach ($votesClosing as $vote) {
    //             if($vote->voting_stage!="autopass"){

    //             foreach ($users as $user) {
    //                 // Prepare email body for opening votes
    //                 $emailBody = Closingbody($user, $image_url, $vote,$users);





    //                 // Send the email
    //                 $mail->addAddress($user->user_email);
    //                 $mail->isHTML(true);
    //                 $mail->Subject = 'Votes Closing Tomorrow->' . $vote->id;
                  
    //                 $mail->Body = $emailBody;
    //                 // echo $emailBody;
    //                 sleep(1);
    //                 if ($mail->send()) {
    //                     echo "Closing votes email sent to: " . $user->user_email . "<br>";
    //                     error_log("Closing votes email sent to:");
    //                 } else {
    //                     echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
    //                     error_log('opening votes email error sent to' .$mail->ErrorInfo);

    //                 }
    //                 $mail->clearAddresses();
    //             }
    //         }
    //         }
    //     } else {
    //         echo "No votes closing tomorrow.";
    //     }



    //     // if (!empty($voteRanking)) {
    //     //     foreach ($voteRanking as $vote_id) {

    //     //         error_log("Processing vote ID: " . $vote_id);

    //     //         // $vote_id = $all_vote->id;
    //     //         // foreach ($users as $user) {



    //     //         // Prepare email body for closing votes
    //     //         $result = Remarksdata($vote_id);

    //     //         $emailBody = $result['email_body'];
    //     //         $vote_box_array_new = $result['vote_box_data'];

    //     //         // Initialize an array to store all user emails
    //     //         $user_emails = [];

    //     //         foreach ($vote_box_array_new as $option) {
    //     //             // Get the user object by ID
    //     //             $user = get_user_by('id', $option['userId']);

    //     //             if ($user) {
    //     //                 // Collect user email
    //     //                 $user_emails[] = $user->user_email;
    //     //             }
    //     //         }

    //     //         // Now $user_emails contains all the user emails
    //     //         print_r($user_emails);
    //     //         // Send the email
    //     //         foreach ($user_emails as $user_email) {
                   
    //     //             // $mailaddres="muskan@delimp.com";
        
    //     //             // Send the email
    //     //             // $mail->addAddress($mailaddres);
    //     //             $mail->addAddress($user_email);
    //     //             $mail->isHTML(true);
    //     //             // $mail->Subject = 'Votes Result';
    //     //         $mail->Subject = 'Votes Result->' . $vote_id;

    //     //             $mail->Body = $emailBody;
    //     //             echo $emailBody;
    //     //             if ($mail->send()) {
    //     //                 echo "Result votes email sent to: " . $user->user_email . "<br>";
    //     //             } else {
    //     //                 echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
    //     //             }
    //     //             $mail->clearAddresses();
    //     //         }
    //     //         // }
    //     //     }
    //     // } else {
    //     //     echo "No votes closing tomorrow.";
    //     // }
    // } catch (Exception $e) {
    //     error_log("errortest: " . $mail->ErrorInfo);


    //     echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
     
    // }


    try {
        // SMTP Configuration
        error_log("SMTP setup started");
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  
        $mail->SMTPAuth = true;
        $mail->Username = 'votes@owbn.net'; 
        $mail->Password = "ezzv pzhr kezz kned"; // Use an environment variable
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';
        $mail->setFrom('votes@owbn.net', 'OWBN');
    
        error_log("Fetching votes");
        $votesOpening = get_upcoming_votes();
        $votesClosing = get_closing_votes();
        error_log("Closing votes data: " . print_r($votesClosing, true));
        $image_url = get_site_url() . '/wp-content/uploads/sites/2/2024/05/OWBN-logo_wht-red-1.png';
    
        // Send emails for opening votes
            if (!empty($votesOpening)) {
                foreach ($votesOpening as $vote) {
                    if ($vote->voting_stage !== "autopass" && !empty($users)) {
                        foreach ($users as $user) {
                            $mail = new PHPMailer(true); // Create a fresh instance for each email
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'votes@owbn.net';
                            $mail->Password = "ezzv pzhr kezz kned"; 
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;
                            $mail->setFrom('votes@owbn.net', 'OWBN');
        
                            // Prepare email
                            $emailBody = Openingbody($user, $image_url, $vote);
                            $mail->addAddress($user->user_email);
                            $mail->isHTML(true);
                            $mail->Subject = 'Votes opening Today -> ' . $vote->id;
                            $mail->Body = $emailBody;
        
                            if ($mail->send()) {
                                error_log("Opening vote email sent to: " . $user->user_email);
                            } else {
                                error_log("Opening vote email error: " . $mail->ErrorInfo);
                            }
                        }
                    }
                }
            } else {
                error_log("No votes opening tomorrow.");
            }
    
        // Send emails for closing votes
        if (!empty($votesClosing)) {
            foreach ($votesClosing as $vote) {
                if ($vote->voting_stage !== "autopass" && !empty($users)) {
                    foreach ($users as $user) {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'votes@owbn.net';
                        $mail->Password = "ezzv pzhr kezz kned"; 
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom('votes@owbn.net', 'OWBN');
    
                        // Prepare email
                        $emailBody = Closingbody($user, $image_url, $vote, $users);
                        $mail->addAddress($user->user_email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Votes Closing Tomorrow -> ' . $vote->id;
                        $mail->Body = $emailBody;
    
                        if ($mail->send()) {
                            error_log("Closing vote email sent to: " . $user->user_email);
                        } else {
                            error_log("Closing vote email error: " . $mail->ErrorInfo);
                        }
                    }
                }
            }
        } else {
            error_log("No votes closing tomorrow.");
        }
    
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo "Message could not be sent. Error: " . $e->getMessage();
    }
    
}






function SendResultclosemail()
{
    // Get user data (emails and names)
    $users = get_all_users();
    error_log(print_r($users, true));

  require __DIR__ . '/phpmailer/src/Exception.php';
    require __DIR__ . '/phpmailer/src/PHPMailer.php';
    require __DIR__ . '/phpmailer/src/SMTP.php';
    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        error_log("smtp assign");
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Example SMTP server (replace with your server)
        $mail->SMTPAuth = true;
        $mail->Username = 'votes@owbn.net'; // SMTP username
        $mail->Password = 'ezzv pzhr kezz kned';
        // $mail->Username = 'votes@owbn.net'; // SMTP username
        // $mail->Password = 'N7UMmQeZuLFC8hG='; 
        
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 2;  // Enable detailed debug output
        $mail->Debugoutput = 'error_log';  // Log output to PHP error log
        
        // Sender info
        $mail->setFrom('votes@owbn.net', 'OWBN');
        error_log("smtp process");

        // Get upcoming votes
        // Fetch votes opening and closing tomorrow
     
    
        $voteRanking = get_result_votes();
        error_log("testupcomingvotes: " . print_r($voteRanking, true));




        $image_path = '/wp-content/uploads/sites/2/2024/05/OWBN-logo_wht-red-1.png';
        $image_url = get_site_url() . $image_path;
        
        echo '<img src="' . esc_url($image_url) . '" alt="OWBN Logo">';
        // Send emails for opening votes
      


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
                   
                    // $mailaddres="muskan@delimp.com";
        
                    // Send the email
                    // $mail->addAddress($mailaddres);
                    $mail->addAddress($user_email);
                    $mail->isHTML(true);
                    // $mail->Subject = 'Votes Result';
                $mail->Subject = 'Votes Result->' . $vote_id;

                    $mail->Body = $emailBody;
                    // echo $emailBody;
                    if ($mail->send()) {
                        echo "Result votes email sent to: " . $user->user_email . "<br>";
                    } else {
                        echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
                    }
                    $mail->clearAddresses();
                }
                // }
            }
        } else {
            echo "No votes closing tomorrow.";
        }
    } catch (Exception $e) {
        error_log("errortest: " . $mail->ErrorInfo);


        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
     
    }
}


// add_shortcode('sendmail', 'sendmail_shortcode');
