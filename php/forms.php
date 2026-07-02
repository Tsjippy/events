<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Add meta keys to store in family meta table
add_filter('tsjippy-family-meta-keys', function ($metaKeys) {
    $metaKeys['Wedding anniversary_event_id'] = 1;
    $metaKeys[TSJIPPY\SITENAME . ' anniversary_event_id'] = 1;

    return $metaKeys;
});

//create  events
add_filter('tsjippy-forms-before-inserting-formdata', __NAMESPACE__ . '\beforeSavingFormData', 10, 2);
/**
 * Before saving form data
 *
 * @param array $request
 * @param object $object
 * @return array
 */
function beforeSavingFormData($request, $object)
{
    if ($object->formData->slug == 'user_generics' || $object->formData->slug == 'child_generic') {
        $events    = new CreateEvents();
        
        // Birthday Events
        $events->createCelebrationEvent('birthday', $object->userId, get_user_meta($object->userId, 'tsjippy_birthday', true), $request['birthday'] ?? '');
    }

    if ($object->formData->slug == 'user_family') {
        $family = new TSJIPPY\FAMILY\Family();

        // Then the weddingdate
        $newDate    = $request['weddingdate'] ?? '' ;
        $oldDate    = $family->getWeddingDate($object->userId);
        if ($newDate != $oldDate) {
            $events        = new CreateEvents();

            $events->createCelebrationEvent('Wedding anniversary', $object->userId, $oldDate, $newDate);
        }
    }

    return $request;
}
