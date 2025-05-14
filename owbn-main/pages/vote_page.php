<?php
/**
* Create the Owbn Voting Form page if it doesn't exist and update the status to
*/
function create_vote_page() {
    // Check if the staff login page exists
    $vote_page = get_page_by_title('Owbn Voting Form', OBJECT, 'page');

    // If the page is in trash, restore it
    if ($vote_page && $vote_page->post_status === 'trash') {
        $vote_page_id = $vote_page->ID;
        wp_untrash_post($vote_page_id);

        // Update the status to 'publish' 
        wp_update_post(array(
            'ID'           => $vote_page_id,
            'post_status'  => 'publish',
        ));
    }

    // If the page doesn't exist, create it
    if (empty($vote_page)) {
        $vote_page_args = array(
            'post_title'   => 'Owbn Voting Form',
            'post_content' => '[voting_form]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        // Insert the page into the database
        $vote_page_id = wp_insert_post($vote_page_args);

        // Optionally, you can save the page ID for future use
        update_option('vote_page_id', $vote_page_id);
    }
}


?>