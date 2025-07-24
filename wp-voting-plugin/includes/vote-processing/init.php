<?php

/** File: includes/vote-processing/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Init vote processing functionality for the plugin
 */

defined('ABSPATH') || exit;

/** --- Require each processing file once --- */
require_once __DIR__ . '/process-condorcet.php';
require_once __DIR__ . '/process-disciplinary.php';
require_once __DIR__ . '/process-rcv.php';
require_once __DIR__ . '/process-singleton.php';
require_once __DIR__ . '/process-stv.php';
require_once __DIR__ . '/process-vote.php';
require_once __DIR__ . '/results-save.php';
require_once __DIR__ . '/results-validate.php';
