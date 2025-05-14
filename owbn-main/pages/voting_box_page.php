<?php
/**
* Create Owbn Voting Box page if doesn't exist in the database. This is used to prevent duplicates
*/
function create_voting_box_page() {
    $voting_box_page = get_page_by_title('Owbn Voting Box', OBJECT, 'page');

    // Untrash posts if the box page is a trash.
    if ($voting_box_page && $voting_box_page->post_status === 'trash') {
        $voting_box_page_id = $voting_box_page->ID;
        wp_untrash_post($voting_box_page_id);

        wp_update_post(array(
            'ID'           => $voting_box_page_id,
            'post_status'  => 'publish',
        ));
    }

    // Add a new page to the voting box
    if (empty($voting_box_page)) {
        $voting_box_page_args = array(
            'post_title'   => 'Owbn Voting Box',
            'post_content' => '[voting_box]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $voting_box_page_id = wp_insert_post($voting_box_page_args);
        update_option('voting_box_page_id', $voting_box_page_id);
    }
}