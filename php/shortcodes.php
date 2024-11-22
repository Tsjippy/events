<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules", __NAMESPACE__.'\schedules');
function schedules(){
	return displaySchedules();
}