<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('init', __NAMESPACE__.'\initTasks');
function initTasks(){
	//add action for use in scheduled task
	add_action( 'remove_old_events_action', __NAMESPACE__.'\removeOldEvents');
	add_action( 'anniversary_check_action', __NAMESPACE__.'\anniversaryCheck');
	add_action( 'remove_old_schedules_action', __NAMESPACE__.'\removeOldSchedules');
	add_action( 'add_repeated_events_action', __NAMESPACE__.'\addRepeatedEvents');

	add_action('send_event_reminder_action', function ($eventId){
		$events = new DisplayEvents();
		$events->sendEventReminder($eventId);
	});
}

/**
 * Clean up events, in events table. Not the post
 *
*/
function removeOldEvents(){
	global $wpdb;

	$maxAge   	= SETTINGS['max-age'] ?? 90;

	$events		= new CreateEvents();

	$query		= "DELETE FROM {$events->tableName} WHERE start_date<'".date('Y-m-d', strtotime("- $maxAge"))."'";

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
	$family		= new TSJIPPY\FAMILY\Family();

	// Get all the events of today
	$events->retrieveEvents(date('Y-m-d'), date('Y-m-d'));

	foreach($events->events as $event){
		$startYear	= get_post_meta($event->ID, 'celebrationdate', true);

		if(!empty($startYear)){
			$userData		= get_userdata($event->post_author);
			$firstName		= $userData->first_name;
			$eventTitle		= $event->post_title;
			$partner		= $family->getPartner($event->post_author, true);

			if($partner){
				$coupleString	= $firstName.' & '.$partner->display_name;
				$eventTitle		= trim(str_replace($coupleString, "", $eventTitle));
			}
			
			$eventTitle	= trim(str_replace($userData->display_name, "", $eventTitle));

			$age	= TSJIPPY\getAge($startYear);

			do_action(
				'tsjippy-events-anniversary-message',
				"Hi $firstName,\nCongratulations with your $age $eventTitle!", 
				$event->post_author
			);

			//If the author has a partner and this events applies to both of them
			if($partner && str_contains($event->post_title, $coupleString)){
				do_action(
					'tsjippy-events-anniversary-message',
					"Hi {$partner->first_name},\nCongratulations with your $eventTitle!", 
					$partner->ID
				);
			}
		}
	}
}

/**
 * Get all schedules with an end_date in the past and deletes them
*/
function removeOldSchedules(){
	$schedules	= new CreateSchedule();
	$schedules->getSchedules();

	foreach($schedules->schedules as $schedule){
		if($schedule->end_date < date('Y-m-d')){
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