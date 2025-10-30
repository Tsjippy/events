<?php
namespace SIM\EVENTS;
use SIM;

// Add meta keys to store in family meta table
add_filter('sim-family-meta-keys', function($metaKeys){
    $metaKeys[] = 'Wedding anniversary_event_id';
    $metaKeys[] = SITENAME.' anniversary_event_id';

    return $metaKeys;
});

//create  events
add_filter('sim_before_saving_formdata', __NAMESPACE__.'\beforeSavingFormData', 10, 2);
function beforeSavingFormData($formResults, $object){
	if($object->formData->name == 'user_generics' || $object->formData->name == 'child_generic'){
		$events	= new CreateEvents();
		$events->createCelebrationEvent('birthday', $object->userId, get_user_meta($object->userId, 'birthday', true), $_POST['birthday']);
		$events->createCelebrationEvent(SITENAME.' anniversary', $object->userId, get_user_meta($object->userId, 'arrival_date', true), $_POST['arrival-date']);
	}

	if($object->formData->name == 'user_family'){
		$family = new SIM\FAMILY\Family();

		// Then the weddingdate
		$newDate	= sanitize_text_field($_POST['weddingdate']);
		$oldDate	= $family->getWeddingDate($object->userId);
		if($newDate != $oldDate){
			$events		= new CreateEvents();

			$events->createCelebrationEvent('Wedding anniversary', $object->userId, $oldDate, $newDate);
		}
	}

	return $formResults;
}