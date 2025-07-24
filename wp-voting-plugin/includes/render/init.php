<?php

/** File: includes/render/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init render functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/group.php';
require_once __DIR__ . '/list.php';
require_once __DIR__ . '/vote.php';
