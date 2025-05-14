<?php
/**
* Create Owbn Voting Result page if it doesn't exist. If it exists untrash
*/
function create_voting_result_page() {
    $voting_result_page = get_page_by_title('Owbn Voting Result', OBJECT, 'page');

    // Untrash posts and unpublishes the trash post
    if ($voting_result_page && $voting_result_page->post_status === 'trash') {
        $voting_result_page_id = $voting_result_page->ID;
        wp_untrash_post($voting_result_page_id);

        wp_update_post(array(
            'ID'           => $voting_result_page_id,
            'post_status'  => 'publish',
        ));
    }

    // Add a new voting result page to the database
    if (empty($voting_result_page)) {
        $voting_result_page_args = array(
            'post_title'   => 'Owbn Voting Result',
            'post_content' => '[voting_result]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $voting_result_page_id = wp_insert_post($voting_result_page_args);
        update_option('voting_result_page_id', $voting_result_page_id);
    }
}