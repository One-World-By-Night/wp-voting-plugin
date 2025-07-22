<?php
/**
* Create or restore Owbn Personal Dashboard page if it doesn't exist in the wp_
*/
function create_personal_dashboard_page() {
    // Check if the personal dashboard page exists
    $personal_dashboard_page = get_page_by_title('Owbn Personal Dashboard', OBJECT, 'page');

    // If the page is in trash, restore it
    if ($personal_dashboard_page && $personal_dashboard_page->post_status === 'trash') {
        $personal_dashboard_page_id = $personal_dashboard_page->ID;
        wp_untrash_post($personal_dashboard_page_id);

        // Update the status to 'publish'
        wp_update_post(array(
            'ID'           => $personal_dashboard_page_id,
            'post_status'  => 'publish',
        ));
    }

    // If the page doesn't exist, create it
    if (empty($personal_dashboard_page)) {
        $personal_dashboard_page_args = array(
            'post_title'   => 'Owbn Personal Dashboard', 
            'post_content' => '[personal_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        // Insert the page into the database
        $personal_dashboard_page_id = wp_insert_post($personal_dashboard_page_args);

        // Optionally, you can save the page ID for future use
        update_option('personal_dashboard_page_id', $personal_dashboard_page_id);
    }
};