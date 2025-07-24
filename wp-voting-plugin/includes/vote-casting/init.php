<?php

/** File: includes/vote-casting/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init vote casting functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/cast-vote.php';
require_once __DIR__ . '/save-ballot.php';
require_once __DIR__ . '/update-ballot.php';
require_once __DIR__ . '/validate-ballot.php';
