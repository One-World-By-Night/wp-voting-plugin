<?php

/** File: includes/shortcodes/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init shortcodes functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/detail.php';
require_once __DIR__ . '/elections_dashboard.php';
require_once __DIR__ . '/form.php';
require_once __DIR__ . '/list.php';
require_once __DIR__ . '/personal_dashboard.php';
require_once __DIR__ . '/preview_vote.php';
require_once __DIR__ . '/remark.php';
require_once __DIR__ . '/send.php';
require_once __DIR__ . '/user_search_vote_list.php';
require_once __DIR__ . '/user_voting_list.php';
require_once __DIR__ . '/voting_box.php';
require_once __DIR__ . '/voting_form.php';
