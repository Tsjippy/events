<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

/**
 * Plugin Name:          Tsjippy Events
 * Description:          This plugin adds a custom posttype named event. This creates a possibility to add a post with a date, time, location and organizer. It also allows the creation of birthdays and anniversaries. A calendar displaying all events is accessible at /events. It also adds the possibility for schedules: a mealschedule or orientantion schedule or other. Add it to any page using the shortcode <code>[schedules]</code>
 * Version:              10.3.5
 * Author:               Ewald Harmsen
 * AuthorURI:            harmseninnigeria.nl
 * Requires at least:    6.3
 * Requires PHP:         8.3
 * Tested up to:         6.9
 * Plugin URI:            https://github.com/Tsjippy/events/
 * Tested:                6.9
 * TextDomain:            tsjippy
 * Requires Plugins:    
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if (! defined('ABSPATH')) {
    exit;
}

// Load shared code
if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
    require_once(__DIR__  . '/shared-functionality/loader.php');
}

// Define constants
define(__NAMESPACE__ . '\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ . '\PLUGINPATH', __DIR__ . '/');
define(__NAMESPACE__ . '\PLUGINVERSION', get_plugin_data(__FILE__, false, false)['Version']);
define(__NAMESPACE__ . '\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ . '\SETTINGS', get_option('tsjippy_events_settings', []));

// run right before activation
register_activation_hook(__FILE__, function () { 
    if(file_exists(__DIR__  . '/shared-functionality/loader.php')){
        require_once(__DIR__  . '/shared-functionality/loader.php');
    }
    
    // Create the dbs
    $events    = new Events();
    $events->createEventsTable();
});
