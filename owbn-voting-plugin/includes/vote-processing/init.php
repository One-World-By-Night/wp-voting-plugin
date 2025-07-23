<?php

/** File: includes/vote-processing/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init vote processing functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each tools file once --- */
require_once __DIR__ . '/process-vote.php';
require_once __DIR__ . '/process-rcv.php';
require_once __DIR__ . '/process-singleton.php';
require_once __DIR__ . '/results-calculations.php';
require_once __DIR__ . '/results-validate.php';
