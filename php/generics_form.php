<?php
namespace SIM\EVENTS;
use SIM;

//create  events
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 10, 2);
function beforeSavingFormData($formResults, $object){
	if($object->formData->name != 'user_generics' && $object->formData->name != 'child_generic'){
		return $formResults;
	}
	
	$events	= new CreateEvents();
	$events->createCelebrationEvent('birthday', $object->userId, 'birthday', $_POST['birthday']);
	$events->createCelebrationEvent(SITENAME.' anniversary', $object->userId, 'arrival_date', $_POST['arrival_date']);
	
	return $formResults;
}