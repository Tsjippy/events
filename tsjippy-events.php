<?php
namespace SIM\EVENTS;
use SIM;

/**
 * Plugin Name:  		Tsjippy Events
 * Description:  		This plugin adds a custom posttype named event. This creates a possibility to add a post with a date, time, location and organizer. It also allows the creation of birthdays and anniversaries. A calendar displaying all events is accessible at /events. It also adds the possibility for schedules: a mealschedule or orientantion schedule or other. Add it to any page using the shortcode <code>[schedules]</code>
 * Version:      		1.0.0
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.3
 * Requires PHP: 		8.3
 * Tested up to: 		6.9
 * Plugin URI:			https://github.com/Tsjippy/comments/
 * Tested:				6.9
 * TextDomain:			tsjippy
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pluginData = get_plugin_data(__FILE__, false, false);

// Define constants
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGINPATH', __FILE__);
define(__NAMESPACE__ .'\PLUGINVERSION', $pluginData['Version']);
define(__NAMESPACE__ .'\SETTINGS', get_option('sim_events_settings', []));

// run on activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin != PLUGIN ) {
        return;
    }

    // Create the dbs
	$events	= new Events();
	$events->createEventsTable();

	$schedules	= new Schedules();
	$schedules->createDbTable();

	$settings	= SIM\ADMIN\createDefaultPage(SETTINGS, 'schedules-pages', 'Schedules', '[schedules]', SETTINGS);

    update_option('sim_events_settings', $settings);
});