<?php
/**
* Create Owbn Preview Vote page if it doesn't exist. This is used to prevent duplicates
*/
function create_preview_vote_page() {
    $preview_vote_page = get_page_by_title('Owbn Preview Vote', OBJECT, 'page');

    // Untrash posts if the preview vote page is a trash.
    if ($preview_vote_page && $preview_vote_page->post_status === 'trash') {
        $preview_vote_page_id = $preview_vote_page->ID;
        wp_untrash_post($preview_vote_page_id);

        wp_update_post(array(
            'ID'           => $preview_vote_page_id,
            'post_status'  => 'publish',
        ));
    }

    // If the preview vote page is empty update the option preview_vote_page_id.
    if (empty($preview_vote_page)) {
        $preview_vote_page_args = array(
            'post_title'   => 'Owbn Preview Vote',
            'post_content' => '[preview_vote]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $preview_vote_page_id = wp_insert_post($preview_vote_page_args);
        update_option('preview_vote_page_id', $preview_vote_page_id);
    }
}