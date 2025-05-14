<?php
/**
* Create Elections Dashboard page if it doesn't exist and set post_status to publish. If page already exists and post_status is'trash
*/
function create_elections_dashboard_page() {
    $elections_dashboard_page = get_page_by_title('Owbn Elections Dashboard', OBJECT, 'page');

    // Untrash posts if the post status is publish
    if ($elections_dashboard_page && $elections_dashboard_page->post_status === 'trash') {
        $elections_dashboard_page_id = $elections_dashboard_page->ID;
        wp_untrash_post($elections_dashboard_page_id);

        wp_update_post(array(
            'ID'           => $elections_dashboard_page_id,
            'post_status'  => 'publish',
        ));
    }

    // Adds a new page to the Elections Dashboard
    if (empty($elections_dashboard_page)) {
        $elections_dashboard_page_args = array(
            'post_title'   => 'Owbn Elections Dashboard',
            'post_content' => '[elections_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        $elections_dashboard_page_id = wp_insert_post($elections_dashboard_page_args);
        update_option('elections_dashboard_page_id', $elections_dashboard_page_id);
    }
}