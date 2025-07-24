<?php

/** File: includes/helper/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init helper functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/static-data.php';
require_once __DIR__ . '/db-queries.php';
require_once __DIR__ . '/db-updates.php';
require_once __DIR__ . '/db-views.php';
