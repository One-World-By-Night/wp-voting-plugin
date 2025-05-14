<?php

/**
 * Plugin Name: Owbn-Main
 * Plugin URI: https://delimp.com/
 * Description: This Plugin has all the core features for voting
 * Version: 1.0
 */

// Include necessary files
$plugin_dir = plugin_dir_path(__FILE__) . 'wpvp/';

require_once($plugin_dir . 'shortcodes/personal_dashboard.php');
require_once($plugin_dir . 'shortcodes/voting_form.php');
require_once($plugin_dir . 'shortcodes/voting_result.php');
require_once($plugin_dir . 'shortcodes/preview_vote.php');
require_once($plugin_dir . 'shortcodes/elections_dashboard.php');
require_once($plugin_dir . 'shortcodes/voting_box.php');
require_once($plugin_dir . 'shortcodes/user_voting_list.php');
require_once($plugin_dir . 'shortcodes/user_search_vote_list.php');
require_once($plugin_dir . 'pages/personal_dashboard_page.php');
require_once($plugin_dir . 'pages/elections_dashboard_page.php');
require_once($plugin_dir . 'pages/vote_page.php');
require_once($plugin_dir . 'pages/preview_vote_page.php');
require_once($plugin_dir . 'pages/voting_box_page.php');

require_once($plugin_dir . 'pages/voting_result_page.php');


require_once($plugin_dir . 'functions/db-call.php');

require_once($plugin_dir . 'tables/votes.php');

require_once($plugin_dir . 'shortcodes/remark.php');
require_once($plugin_dir . 'shortcodes/send.php');

// Hook to enqueue ThickBox scripts and styles
add_action('admin_enqueue_scripts', 'owbn_enqueue_thickbox');
function owbn_enqueue_thickbox()
{
    add_thickbox();
}

// Hook into the plugin row meta
add_filter('plugin_row_meta', 'owbn_plugin_row_meta', 10, 2);
function owbn_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) == $file) {
        $details_link = '<a href="#TB_inline?width=600&height=550&inlineId=owbn-plugin-details" class="thickbox">View Details</a>';
        $links[] = $details_link;
    }
    return $links;
}

// Add the hidden div for ThickBox content
add_action('admin_footer', 'owbn_thickbox_content');
function owbn_thickbox_content()
{
?>
    <div id="owbn-plugin-details" style="display:none;">
        <h2>OWBN Voting Details</h2>
        <ul>
            <li><b>[user_search_vote_list] : </b> This shortcode can be used to show listing of vote, where voting visibility for both council and public and status is completed</li>
            <li><b>[user_voting_list] :</b> This shortcode can be used to show listing of vote with the status where can see user have voted or not in that perticular voting, this shortcode will need arm membership plugin where username field will required in profile page</li>
        </ul>
    </div>
<?php
}




/**
 * Enqueues assets used by owbn. This is a callback function to wp_enqueue_scripts. You should not call this
 */
function owbn_enqueue_assets()
{
    $plugin_dir_url = plugin_dir_url(__FILE__);

    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), '3.7.1', true);
    // Enqueue global variables script first
    wp_enqueue_script('global-variables', $plugin_dir_url . 'wpvp/js/global_variable.js', array(), '1.0.0', true);

    wp_enqueue_script('voting-form-script', $plugin_dir_url . 'wpvp/js/voting-form-script.js', array('global-variables'), '1.0.0', true);

    // enque the cdn scripts

    wp_enqueue_script('ckeditor',  $plugin_dir_url . 'wpvp/ckeditor/ckeditor.js', array(), '41.4.2', true);

    // Enqueue styles
    wp_enqueue_style('voting-form-styles', $plugin_dir_url . 'wpvp/css/voting-form-styles.css', array(), '1.0.0');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
}
add_action('wp_enqueue_scripts', 'owbn_enqueue_assets', 99);

/**
 * Enqueue scripts and localizations for upload - file. This is called on wp_enqueue_scripts and wp_enqueue_
 */
function enqueue_custom_scripts()
{
    wp_enqueue_script('upload-file', plugin_dir_url(__FILE__) . 'wpvp/js/upload-file.js', array('global-variables'), '1.0.0', true);

    // Localize the script with new data
    wp_localize_script('upload-file', 'my_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('upload_file_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

/**
 * Create pages on plugin activation and activate them on first page load ( for admin only ) This function is hooked into owbn_init
 */
function owbn_activate_plugin()
{
    create_voting_table();
    create_personal_dashboard_page();
    create_elections_dashboard_page();
    create_vote_page();
    create_preview_vote_page();
    create_voting_box_page();
    create_voting_result_page();
}
register_activation_hook(__FILE__, 'owbn_activate_plugin');

/**
 * Remove pages when plugin is deactivated from Wordpress This is called by WP_Deregister_Plugin
 */
function owbn_deactivate_plugin()
{
    $pages = ['owbn-personal-dashboard', 'owbn-voting-form', 'owbn-preview-vote', 'owbn-voting-box', 'owbn-elections-dashboard', 'owbn-voting-result'];
    foreach ($pages as $slug) {
        $page = get_page_by_path($slug);
        // Delete the post from the page.
        if ($page) {
            wp_delete_post($page->ID);
        }
    }
}
register_deactivation_hook(__FILE__, 'owbn_deactivate_plugin');

/**
 * Allow. txt rtf doc and. pdf file types to PHP's built - in mime types
 * 
 * @param $mimes
 * 
 * @return Modified array of allowed mime types to PHP's built - in mime types ( modified in place by this
 */
function custom_mime_types($mimes)
{
    $mimes['txt'] = 'text/plain';
    $mimes['rtf'] = 'application/rtf';
    $mimes['doc'] = 'application/msword';
    $mimes['pdf'] = 'application/pdf';
    return $mimes;
}
add_filter('upload_mimes', 'custom_mime_types');

// AJAX Handlers
add_action('wp_ajax_submit_voting_form', 'submit_voting_form');
add_action('wp_ajax_nopriv_submit_voting_form', 'submit_voting_form');
add_action('wp_ajax_submit_voting_box', 'submit_voting_box');
add_action('wp_ajax_nopriv_submit_voting_box', 'submit_voting_box');
add_action('wp_ajax_delete_vote', 'delete_vote_callback');
add_action('wp_ajax_upload_file', 'upload_file_callback');
add_action('wp_ajax_delete_file', 'delete_file_from_media_library');
// Add Admin Menu
function add_custom_admin_menu()
{
    // Add main menu item
    add_menu_page(
        'OWBN Dashboard',              // Page title
        'OWBN Dashboard',              // Menu title
        'manage_options',              // Capability
        'owbn-dashboards',             // Menu slug
        'custom_links_main_page',      // Function to display page content
        'dashicons-admin-links',       // Icon URL (you can choose any dashicon)
        6                              // Position
    );

    // Add submenu items
    add_submenu_page(
        'owbn-dashboards',             // Parent slug
        'OWBN Elections Dashboard',    // Page title
        'OWBN Elections Dashboard',    // Menu title
        'manage_options',              // Capability
        'owbn-elections-dashboard',    // Menu slug
        'custom_frontend_link'         // Function to display page content
    );

    add_submenu_page(
        'owbn-dashboards',             // Parent slug
        'OWBN Personal Dashboard',     // Page title
        'OWBN Personal Dashboard',     // Menu title
        'manage_options',              // Capability
        'owbn-personal-dashboard',     // Menu slug
        'custom_another_link'          // Function to display page content
    );
    add_submenu_page(
        'owbn-dashboards',             // Parent slug
        'Owbn Voting Form',     // Page title
        'Owbn Voting Form',     // Menu title
        'manage_options',              // Capability
        'owbn-voting-form',     // Menu slug
        'custom_another_link'          // Function to display page content
    );
    // add_submenu_page(
    //     'owbn-dashboards',             // Parent slug
    //     'Owbn Voting Result',     // Page title
    //     'Owbn Voting Result',     // Menu title
    //     'manage_options',              // Capability
    //     'owbn-voting-result',     // Menu slug
    //     'custom_another_link'          // Function to display page content
    // );
}

add_action('admin_menu', 'add_custom_admin_menu');

// Main menu page callback function (optional)
function custom_links_main_page()
{
    return false;
}

// Hook to handle redirections before headers are sent
function handle_custom_redirects()
{
    if (isset($_GET['page'])) {
        if ($_GET['page'] == 'owbn-dashboards') {
            wp_redirect(admin_url()); // Redirect to the admin dashboard or another page
            exit;
        } elseif ($_GET['page'] == 'owbn-elections-dashboard') {
            wp_redirect(site_url() . '/owbn-elections-dashboard/');
            exit;
        } elseif ($_GET['page'] == 'owbn-personal-dashboard') {
            wp_redirect(site_url() . '/owbn-personal-dashboard/');
            exit;
        } elseif ($_GET['page'] == 'owbn-voting-form') {
            wp_redirect(site_url() . '/owbn-voting-form/');
            exit;
        } elseif ($_GET['page'] == 'owbn-voting-result') {
            wp_redirect(site_url() . '/owbn-voting-result/');
            exit;
        }
    }
}
add_action('admin_init', 'handle_custom_redirects');

// Add custom CSS to ensure that the main menu item is not clickable
function disable_main_menu_link()
{
    echo '<style>
        #toplevel_page_owbn-dashboards a {
            pointer-events: none;
            cursor: default;
        }
        /* Ensure submenu items are clickable */
        #toplevel_page_owbn-dashboards .wp-submenu a {
            pointer-events: auto;
            cursor: pointer;
        }
    </style>';
}
add_action('admin_head', 'disable_main_menu_link');

/* Cron JOb for auto update completed after close date */

function my_custom_plugin_activate()
{
    if (!wp_next_scheduled('my_custom_cron_hook')) {
        // wp_schedule_event(time() + 10, 'hourly', 'my_custom_cron_hook');
        // wp_schedule_event(strtotime('tomorrow midnight')- 300, 'daily', 'my_custom_cron_hook');

        $timezone = new DateTimeZone('America/New_York');
        $time = new DateTime('today 00:10', $timezone); // 11:50 PM EST
        $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

        wp_schedule_event($timestamp, 'daily', 'my_custom_cron_hook');
    }

    if (!wp_next_scheduled('my_custom_opening_cron_hook')) {
        $timezone = new DateTimeZone('America/New_York');
        $time = new DateTime('tomorrow midnight', $timezone); // Midnight EST
        $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

        wp_schedule_event($timestamp, 'daily', 'my_custom_opening_cron_hook');
    }
    if (!wp_next_scheduled('my_custom_send_mail_hook')) {

        $timezone = new DateTimeZone('America/New_York');
        $time = new DateTime('today 09:00 AM', $timezone);


        $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

        wp_schedule_event($timestamp, 'daily', 'my_custom_send_mail_hook');
    }
    if (!wp_next_scheduled('my_custom_send_result_mail_hook')) {


        $timezone = new DateTimeZone('America/New_York');
        $time = new DateTime('today midnight', $timezone); // Midnight EST
        $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

        wp_schedule_event($timestamp, 'daily', 'my_custom_send_result_mail_hook');


        // wp_schedule_event(strtotime('tomorrow 14:00'), 'daily', 'my_custom_send_mail_hook');
        // wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'my_custom_send_result_mail_hook');

    }
}

// function checktime(){
// //   echo  strtotime('tomorrow 14:00');
// // $timezone = new DateTimeZone('America/New_York');
// // $time = new DateTime('now', $timezone);
// // $time->modify('+2 minutes'); // Add 2 minutes
// // $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

// $timezone = new DateTimeZone('America/New_York');
// $time = new DateTime('today 3:35 AM', $timezone);


// $timestamp = $time->getTimestamp(); // Convert to UTC timestamp

// echo $timestamp;

// // die();
// $timestamp2 = wp_next_scheduled('my_custom_send_mail_hook');
// if ($timestamp2) {
//     wp_unschedule_event($timestamp2, 'my_custom_send_mail_hook');
// }
// wp_schedule_event($timestamp,'daily', 'my_custom_send_mail_hook');

// }

register_activation_hook(__FILE__, 'my_custom_plugin_activate');

// Clear scheduled events on deactivation
register_deactivation_hook(__FILE__, 'clear_plugin_cron_events');

function clear_plugin_cron_events()
{
    $timestamp = wp_next_scheduled('my_custom_cron_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'my_custom_cron_hook');
    }

    $timestamp1 = wp_next_scheduled('my_custom_opening_cron_hook');
    if ($timestamp1) {
        wp_unschedule_event($timestamp1, 'my_custom_opening_cron_hook');
    }

    $timestamp2 = wp_next_scheduled('my_custom_send_mail_hook');
    if ($timestamp2) {
        wp_unschedule_event($timestamp2, 'my_custom_send_mail_hook');
    }


    $timestamp3 = wp_next_scheduled('my_custom_send_result_mail_hook');
    if ($timestamp3) {
        wp_unschedule_event($timestamp3, 'my_custom_send_result_mail_hook');
    }
}

// Function to be called by the scheduled event for closing_date
add_action('my_custom_cron_hook', 'my_custom_cron_function');

function my_custom_cron_function()
{
    error_log('Cron job for closing_date triggered at ' . current_time('mysql'));

  
    global $wpdb;
    // $table_name = $wpdb->prefix . 'voting';
    $table_name = 'wp_2_voting';
    // $current_time = current_time('mysql');
    // $current_date = current_time('Y-m-d');
    $current_time = date('Y-m-d', strtotime(current_time('Y-m-d') . ' +1 day'));



    // $result = $wpdb->query(
    //     $wpdb->prepare(
    //         "UPDATE $table_name 
    //     SET voting_stage = %s, active_status = %s 
    //     WHERE closing_date < %s AND voting_stage = %s",
    //         'completed',
    //         'InactiveVote',
    //         $current_time,
    //         'normal'
    //     )
    // );
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table_name 
            SET voting_stage = %s, active_status = %s 
            WHERE closing_date = %s AND (voting_stage = %s OR voting_stage = %s)",
            'completed',        // New voting_stage value
            'InactiveVote',     // New active_status value
            $current_time,      // Closing date condition
            'normal',           // First condition for voting_stage
            'autopass'          // Second condition for voting_stage
        )
    );
    echo $wpdb->last_query;

    error_log('Cron job for closing_date triggered at ' . current_time('mysql'));
    error_log('Cron job for closing_date last_query ' . $wpdb->last_query);
    error_log('Cron job for closing_date result ' . $result);


    error_log('Cron job for closing_date triggered at ' . current_time('mysql'));
}

// Function to be called by the scheduled event for opening_date
add_action('my_custom_opening_cron_hook', 'my_custom_opening_cron_function');

function my_custom_opening_cron_function()
{
    global $wpdb;
    // $table_name = $wpdb->prefix . 'voting';
    $table_name = 'wp_2_voting';
    //$current_time = current_time('mysql');
    $current_date = current_time('Y-m-d');

    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE $table_name 
        SET voting_stage = %s, active_status = %s 
        WHERE opening_date = %s AND voting_stage = %s",
            'normal',
            'activeVote',
            $current_date,
            'normal'
        )
    );
    //echo $wpdb->last_query;
    error_log('Cron job for opening_date triggered at ' . current_time('mysql'));
    error_log('Cron job for opening_date last_query ' . $wpdb->last_query);
    error_log('Cron job for opening_date result ' . $result);
}

add_action('my_custom_send_mail_hook', 'my_custom_send_mail_hook_function');
function my_custom_send_mail_hook_function()
{
    error_log("running");
    echo "mail";
    mail('muskan@delimp.com', 'crone subject', 'hello');
    Sendmail();
    wp_die();
}
add_action('my_custom_send_result_mail_hook', 'my_custom_send_result_mail_hook_function');
function my_custom_send_result_mail_hook_function()
{
    // mail('muskan@delimp.com','crone subject','hello');
    SendResultclosemail();
    error_log("running");
}


function register_custom_query_vars($vars)
{
    $vars[] = 'id';
    return $vars;
}
add_filter('query_vars', 'register_custom_query_vars');


function add_custom_rewrite_rules()
{
    add_rewrite_rule('^owbn-voting-result/([0-9]+)/?$', 'index.php?pagename=owbn-voting-result&id=$matches[1]', 'top');
    add_rewrite_rule('^owbn-voting-form/([0-9]+)/?$', 'index.php?pagename=owbn-voting-form&id=$matches[1]', 'top');
    add_rewrite_rule('^owbn-preview-vote/([0-9]+)/?$', 'index.php?pagename=owbn-preview-vote&id=$matches[1]', 'top');
    add_rewrite_rule('^owbn-voting-box/([0-9]+)/?$', 'index.php?pagename=owbn-voting-box&id=$matches[1]', 'top');
}
add_action('init', 'add_custom_rewrite_rules');

add_action('init', 'add_custom_rewrite_rules');

// Flush rewrite rules (run once, then remove)
add_action('init', function () {
    flush_rewrite_rules();
});
add_action('wp', function () {
    $custom_var = get_query_var('id', 'default_value');
    if ($custom_var !== 'default_value') {
        // echo 'id Var: ' . esc_html( $custom_var );
    }
});
add_action('after_switch_theme', 'flush_rewrite_rules');


// $my_param = get_query_var('custom_field',101);


// echo "<h1>".$my_param ."</h1>";
// echo '</pre>';
// die();
?>