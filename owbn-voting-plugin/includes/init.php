<?php

/** File: includes/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Porvide a single entry point to load all plugin components in standard and class-based structure
 */

defined('ABSPATH') || exit;

// Load plugin core bootstraps first
require_once __DIR__ . '/core/init.php';

// Autoload classes (optional PSR-like loader for 'classes/' dir)
$classes = glob(__DIR__ . '/classes/*.php');
if ($classes) {
    foreach ($classes as $class_file) {
        require_once $class_file;
    }
}

// Load Helper so we have our data for other content
require_once __DIR__ . '/helper/init.php';

// Load admin so we get everything registered properly
require_once __DIR__ . '/admin/init.php';

// Load AccessSchena-Client for permissions if used
require_once __DIR__ . '/accessschema-client/accessSchema-client.php';

// Load hooks so we can leverage tools
require_once __DIR__ . '/hooks/init.php';

// Load templates for UI/UX
require_once __DIR__ . '/template/init.php';

// Load render for codified material to populate templates
require_once __DIR__ . '/render/init.php';

// Load shortcodes for use as necessary
require_once __DIR__ . '/shortcodes/init.php';

// Ensure we are language ready
require_once __DIR__ . '/languages/init.php';

// Plugin Level init hook
do_action('wpvotingplugin_loaded');
