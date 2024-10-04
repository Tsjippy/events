<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'remove_old_events_action', __NAMESPACE__.'\removeOldEvents');
	add_action( 'anniversary_check_action', __NAMESPACE__.'\anniversaryCheck');
	add_action( 'remove_old_schedules_action', __NAMESPACE__.'\removeOldSchedules');
	add_action( 'add_repeated_events_action', __NAMESPACE__.'\addRepeatedEvents');

	add_action('send_event_reminder_action', function ($eventId){
		$events = new DisplayEvents();
		$events->sendEventReminder($eventId);
	});
});

/**
 * Schedules the tasks for this module
 *
*/
function scheduleTasks(){
	SIM\scheduleTask('anniversary_check_action', 'daily');
	SIM\scheduleTask('remove_old_schedules_action', 'daily');
	SIM\scheduleTask('add_repeated_events_action', 'yearly');

    $freq   = SIM\getModuleOption(MODULE_SLUG, 'freq');

    if($freq){
		SIM\scheduleTask('remove_old_events_action', $freq);
	}
}

/**
 * Clean up events, in events table. Not the post
 *
*/
function removeOldEvents(){
	global $wpdb;

	$maxAge   	= SIM\getModuleOption(MODULE_SLUG, 'max_age');

	$events		= new CreateEvents();

	$query		= "DELETE FROM {$events->tableName} WHERE startdate<'".date('Y-m-d', strtotime("- $maxAge"))."'";

	$expiredEvents	= $wpdb->get_results( $query);
	foreach($expiredEvents as $event){
		$events->removeDbRows($event->ID, true);
	}
}

/**
 * Get all the events of today and check if they are an anniversary.
 * If so, send a concratulation message
 *
*/
function anniversaryCheck(){
	$events		= new DisplayEvents();

	// Get all the events of today
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID, 'celebrationdate', true);

		if(!empty($startYear)){
			$userData		= get_userdata($event->post_author);
			$firstName		= $userData->first_name;
			$eventTitle		= $event->post_title;
			$partner		= SIM\hasPartner($event->post_author, true);

			if($partner){
				$coupleString	= $firstName.' & '.$partner->display_name;
				$eventTitle		= trim(str_replace($coupleString, "", $eventTitle));
			}
			
			$eventTitle	= trim(str_replace($userData->display_name, "", $eventTitle));

			$age	= SIM\getAge($startYear);

			SIM\trySendSignal("Hi $firstName,\nCongratulations with your $age $eventTitle!", $event->post_author);

			//If the author has a partner and this events applies to both of them
			if($partner && str_contains($event->post_title, $coupleString)){
				SIM\trySendSignal("Hi {$partner->first_name},\nCongratulations with your $eventTitle!", $partner->ID);
			}
		}
	}
}

/**
 * Get all schedules with an enddate in the past and deletes them
*/
function removeOldSchedules(){
	$schedules	= new CreateSchedule();
	$schedules->getSchedules();

	foreach($schedules->schedules as $schedule){
		if($schedule->enddate < date('Y-m-d')){
			$schedules->removeSchedule($schedule->id);
		}
	}
}

/**
 * Create repeated events for the next 5 years
 */
function addRepeatedEvents(){
	global $wpdb;

	$query		= "SELECT * FROM `$wpdb->postmeta` WHERE `meta_key`='eventdetails'";
	$results	= $wpdb->get_results($query);

	foreach($results as $result){
		$details	= maybe_unserialize($result->meta_value);
		if(!is_array($details)){
			$details	= json_decode($details, true);
		}else{
			update_post_meta($result->post_id, 'eventdetails', json_encode($details));
		}

		if(@is_array($details['repeat']) && !empty($details['repeat']['stop']) && $details['repeat']['stop'] == 'never'){
			$events	= new CreateEvents();
			$events->eventData	= $details;
			$events->postId		= $result->post_id;
			$events->createEvents();
		}
	}
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'anniversary_check_action' );
	wp_clear_scheduled_hook( 'remove_old_events_action' );
});