<?php

/** File: includes/core/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init core functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/activation.php';
require_once __DIR__ . '/deactivation.php';
require_once __DIR__ . '/authorization.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/enqueue.php';
require_once __DIR__ . '/votes.php';
