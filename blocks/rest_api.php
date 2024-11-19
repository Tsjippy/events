<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	// show schedules
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/show_schedules',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\showSchedules',
			'permission_callback' 	=> '__return_true',
		)
	);

	// show upcoming arrivals
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/upcoming_arrivals',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\upcomingArrivals',
			'permission_callback' 	=> '__return_true',
		)
	);

	// Upcoming events
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/upcoming_events',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\upcomingEvents',
			'permission_callback' 	=> '__return_true',
		)
	);
} );

function showSchedules(){
	$schedule		= new Schedules();
	return $schedule->showSchedules();
}

function upcomingArrivals($param){
	if(!is_array($param)){
		$param	= $param->get_Params();
	}
	return upcomingArrivalsBlock($param);
}

function upcomingEvents(){
	$events		= new DisplayEvents();

	$items	= 10;
	$months	= 3;
	$cats	= [];

	if(!empty($_GET['items']) && is_numeric($_GET['items'])){
		$items	= $_GET['items'];
	}

	if(!empty($_GET['months']) && is_numeric($_GET['months'])){
		$months	= $_GET['months'];
	}

	if(!empty($_GET['categories'])){
		$cats	= explode(',', trim($_GET['categories'], ','));

		$categories	= get_categories( array(
			'taxonomy'		=> 'events',
			'hide_empty' 	=> false,
		) );
	
		$exclude	= $cats;
	
		$include	= [];
	
		foreach($categories as $category){
			if(!in_array($category->term_id, $exclude)){
				$include[]	= $category->term_id;
			}
		}

	}
	return $events->upcomingEventsArray($items, $months, $include);
}