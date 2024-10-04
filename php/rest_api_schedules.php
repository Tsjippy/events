<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	//add_schedule
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/add_schedule',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->addSchedule($_POST['update']);
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();

				if(is_numeric($_POST['schedule_id'])){
					$schedule->getScheduleById($_POST['schedule_id']);
				}
				return $schedule->admin;
			},
			'args'					=> array(
				'target_name'		=> array(
					'required'	=> true
				),
				'startdate'		=> array(
					'required'	=> true,
				),
				'enddate'		=> array(
					'required'	=> true
				),
			)
		)
	);

	//publish_schedule
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/publish_schedule',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->publishSchedule();
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();
				return $schedule->admin;
			},
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				)
			)
		)
	);

	//remove_schedule
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/remove_schedule',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->removeSchedule($_POST['schedule_id']);
			},
			'permission_callback' 	=> function(){
				$schedule	= new CreateSchedule();
				return $schedule->admin;
			},
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				)
			)
		)
	);

	//add_host
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/add_host',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedules		= new CreateSchedule();

				if(is_array($_POST['date'])){
					$schedule		= $schedules->getScheduleById($schedules->scheduleId);

					$succesFull		= '';
					$unSuccesFull	= '';
					$html			= [];
					foreach($_POST['date'] as $date){
						$result	= $schedules->addHost($date);

						if(is_wp_error($result)){
							if(!empty($unSuccesFull)){
								$unSuccesFull		.= ' and ';
							}
							$unSuccesFull	.= date(DATEFORMAT, strtotime($date));
						}else{
							if(!empty($succesFull)){
								$succesFull		.= ' and ';
							}
							$succesFull	.= date(DATEFORMAT, strtotime($date));

							$html[$date]	= $result['html'];

							$succes		= explode(" as a host for $schedule->name on", $result['message'])[0]." as a host for $schedule->name on";
						}
					}

					$msg	= '';
					if(!empty($succesFull)){
						$msg	.= "$succes $succesFull";
					}
					if(!empty($unSuccesFull)){
						$msg	.= "Existing bookings where found on $unSuccesFull";
					}

					return [
						'message'	=> $msg,
						'html'		=> $html
					];
				}
				return $schedules->addHost($_POST['date']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isTime($param);
					}
				)
			)
		)
	);

	//remove_host
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/remove_host',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule				= new CreateSchedule();
				return $schedule->removeHost($_POST['session_id']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'session_id'		=> array(
					'required'			=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				)
			)
		)
	);

	//add_menu
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/add_menu',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$schedule		= new CreateSchedule();
				return $schedule->addMenu();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'schedule_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($scheduleId){
						return is_numeric($scheduleId);
					}
				),
				'date'		=> array(
					'required'	=> true,
					'validate_callback' => function($param){
						return SIM\isDate($param);
					}
				),
				'starttime'		=> array(
					'required'	=> true
				),
				'recipe_keyword'		=> array(
					'required'	=> true
				),
			)
		)
	);
} );