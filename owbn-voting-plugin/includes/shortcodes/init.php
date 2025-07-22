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
require_once __DIR__ . '/form.php';
require_once __DIR__ . '/list.php';
