<?php

/**
* Create or update the voting table in the database This function is called when the database is loaded and should be the first time we need to create or update
*/
function create_voting_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'voting';

   // Define the SQL query to create/update the table
$sql = "CREATE TABLE $table_name (
    id INT NOT NULL AUTO_INCREMENT, 
    proposal_name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    voting_options TEXT NOT NULL,
    files_name TEXT NOT NULL,
    vote_box TEXT NOT NULL,
    vote_type VARCHAR(50) NOT NULL,
    voting_stage VARCHAR(50) NOT NULL,
    proposed_by VARCHAR(100) NOT NULL,
    seconded_by VARCHAR(100) NOT NULL,
    create_date DATE NOT NULL,
    opening_date DATE NOT NULL,
    closing_date DATE NOT NULL,
    visibility VARCHAR(50) NOT NULL,
    maximum_choices VARCHAR(50) NOT NULL,
    active_status VARCHAR(50) NOT NULL,
    voting_choice VARCHAR(50) NOT NULL,
    -- number_of_winner INT(5) NOT NULL DEFAULT 1,

    created_by VARCHAR(100) NOT NULL, 
--       remark  VARCHAR(255) DEFAULT NULL,
--  withdrawn_time TIME DEFAULT NULL
    
    PRIMARY KEY (id)
) {$wpdb->get_charset_collate()};";


    // Include the upgrade functions for dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Execute dbDelta function
    dbDelta($sql);

    // Debugging: check for errors
    if ($wpdb->last_error) {
        error_log("Error updating the voting table: " . $wpdb->last_error);
    } else {
        error_log("Voting table created/updated successfully.");
    }
}


// Function to insert data into the voting table
function insert_voting_data($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'voting';

    // Serialize arrays before inserting
    $data['voting_options'] = serialize($data['voting_options']);
    $data['files_name'] = serialize($data['files_name']);

    $wpdb->insert($table_name, $data);
}


/**
* Retrieve voting data from the database. Unserializes arrays after retrieving. This is used to save time when we need to re - use the database in a different way
* 
* @param $id
* 
* @return $result Array of voting data or false if not found or error ( may be empty array in case of error
*/
function get_voting_data($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'voting';

    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
    $result = $wpdb->get_row($query, ARRAY_A);

    // Unserialize arrays after retrieving
    $result['voting_options'] = unserialize($result['voting_options']);
    $result['files_name'] = unserialize($result['files_name']);

    return $result;
}