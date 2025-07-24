<?php

/** File: includes/hooks/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init hooks functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/actions.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/rest-api.php';
require_once __DIR__ . '/webhooks.php';
