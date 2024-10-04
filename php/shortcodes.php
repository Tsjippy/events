<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules", function(){
	return displaySchedules();
});