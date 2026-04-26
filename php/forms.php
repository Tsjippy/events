<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add meta keys to store in family meta table
add_filter('tsjippy-family-meta-keys', function($metaKeys){
    $metaKeys[] = 'Wedding anniversary_event_id';
    $metaKeys[] = SITENAME.' anniversary_event_id';

    return $metaKeys;
});

//create  events
add_filter('tsjippy_before_inserting_formdata', __NAMESPACE__.'\beforeSavingFormData', 10, 2);
function beforeSavingFormData($submission, $object){
	if($object->formData->name == 'user_generics' || $object->formData->name == 'child_generic'){
		$events	= new CreateEvents();
		$events->createCelebrationEvent('birthday', $object->userId, get_user_meta($object->userId, 'birthday', true), $_POST['birthday']);
		$events->createCelebrationEvent(SITENAME.' anniversary', $object->userId, get_user_meta($object->userId, 'arrival_date', true), $_POST['arrival-date']);
	}

	if($object->formData->name == 'user_family'){
		$family = new TSJIPPY\FAMILY\Family();

		// Then the weddingdate
		$newDate	= sanitize_text_field($_POST['weddingdate']);
		$oldDate	= $family->getWeddingDate($object->userId);
		if($newDate != $oldDate){
			$events		= new CreateEvents();

			$events->createCelebrationEvent('Wedding anniversary', $object->userId, $oldDate, $newDate);
		}
	}

	return $submission;
}