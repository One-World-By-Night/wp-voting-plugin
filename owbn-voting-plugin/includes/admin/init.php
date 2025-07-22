<?php

/** File: includes/admin/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init admin functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/cpt.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/enqueue.php';
require_once __DIR__ . '/metabox.php';
