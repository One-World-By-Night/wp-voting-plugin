<?php

/** File: includes/init.php
 * Text Domain: wp-voting-plugin
 * @version 2.0.0
 * @author greghacke
 * Function: Provide a single entry point to load all plugin components in standard and class-based structure
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

// Load Ajax functionality
require_once __DIR__ . '/ajax/init.php';

// Load functions
require_once __DIR__ . '/functions/init.php';

// Load vote processing algorithms
require_once __DIR__ . '/vote-processing/init.php';

// Load vote management for creating and editing votes
require_once __DIR__ . '/vote-management/init.php';

// Load vote casting for ballots
require_once __DIR__ . '/vote-casting/init.php';

// Load admin so we get everything registered properly
require_once __DIR__ . '/admin/init.php';

// Load utils so we have our helper functions available
require_once __DIR__ . '/utils/init.php';

// Load AccessSchema-Client for permissions if used
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
do_action('wpvp_plugin_loaded');
