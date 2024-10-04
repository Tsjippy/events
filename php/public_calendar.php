<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'init', function() {
    add_rewrite_endpoint( 'public_calendar', EP_ROOT);
});

add_action( 'template_redirect', function() {
    global $wp_query;
 
    // if this is not a request for json or a singular object then bail
    if ( ! isset( $wp_query->query_vars['public_calendar'] )){
		return;
	}
 
    // include custom template
	$icalFeed	= new IcalFeed();
    $icalFeed->calendarStream();
} );

//outlook.com: https://outlook.office.com/calendar/addcalendar
//google: https://calendar.google.com/calendar/u/1/r/settings/addbyurl

