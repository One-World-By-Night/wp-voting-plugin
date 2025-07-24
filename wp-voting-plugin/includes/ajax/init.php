<?php

/** File: includes/vote-ajax/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init tests functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/ajax-cast-ballot.php';
require_once __DIR__ . '/ajax-file-upload.php';
require_once __DIR__ . '/ajax-save-vote.php';
require_once __DIR__ . '/ajax-vote-status.php';
require_once __DIR__ . '/vote-ajax.php';
