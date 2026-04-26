<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode("schedules", __NAMESPACE__.'\schedules');
function schedules(){
	return displaySchedules();
}

add_shortcode('missing_events', __NAMESPACE__ . '\showMissingEvents');
function showMissingEvents(){
    $adminMenu  = new AdminMenu(SETTINGS, 'Events');

    return $adminMenu->data();
}

