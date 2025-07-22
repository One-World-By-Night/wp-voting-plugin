<?php

/** File: includes/vote-management/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init vote management functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/create-vote.php';
require_once __DIR__ . '/delete-vote.php';
require_once __DIR__ . '/save-vote-options.php';
require_once __DIR__ . '/update-vote.php';
require_once __DIR__ . '/vote-validation.php';
