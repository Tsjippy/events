<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'rest_api_init', function () {
	// Month calendar
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/get_month_html',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
				$events		= new DisplayEvents();
				return $events->monthCalendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'month'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return is_numeric( $param );
					}
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => function($param) {
						return is_numeric( $param );
					}
				),
			)
		)
	);

	// Week calendar
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/get_week_html',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function (){
				$events		= new DisplayEvents();
				return $events->weekCalendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'wknr'		=> array(
					'required'	=> true,
					'validate_callback' => function($weekNr){
						return is_numeric($weekNr);
					}
				),
				'year'		=> array(
					'required'	=> true,
					'validate_callback' => function($year){
						return is_numeric($year);
					}
				),
			)
		)
	);

	// List calendar
	register_rest_route(
		RESTAPIPREFIX.'/events',
		'/get_list_html',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> function(){
				$events		= new DisplayEvents();
				return $events->listCalendar();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'offset'		=> array(
					'required'	=> true,
					'validate_callback' => function($offset){
						return is_numeric($offset);
					}
				),
			)
		)
	);
} );