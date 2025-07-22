<?php

/** File: includes/utils/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init utils functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/log-vote-casting.php';
require_once __DIR__ . '/log-vote-management.php';
require_once __DIR__ . '/log-vote-processing.php';
require_once __DIR__ . '/log-vote.php';
