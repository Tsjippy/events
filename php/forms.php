<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Add meta keys to store in family meta table
add_filter('tsjippy-family-meta-keys', function ($metaKeys) {
    $metaKeys[] = 'Wedding anniversary_event_id';
    $metaKeys[] = TSJIPPY\SITENAME . ' anniversary_event_id';

    return $metaKeys;
});

//create  events
add_filter('tsjippy-before-inserting-formdata', __NAMESPACE__ . '\beforeSavingFormData', 10, 2);
function beforeSavingFormData($submission, $object)
{
    if ($object->formData->slug == 'user_generics' || $object->formData->slug == 'child_generic') {
        $events    = new CreateEvents();
        
        // phpcs:ignore
        $events->createCelebrationEvent('birthday', $object->userId, get_user_meta($object->userId, 'tsjippy_birthday', true), TSJIPPY\sanitize($_POST['birthday'] ?? ''));

        // phpcs:ignore
        $events->createCelebrationEvent(TSJIPPY\SITENAME . ' anniversary', $object->userId, get_user_meta($object->userId, 'tsjippy_arrival_date', true), TSJIPPY\sanitize($_POST['arrival-date'] ?? ''));
    }

    if ($object->formData->slug == 'user_family') {
        $family = new TSJIPPY\FAMILY\Family();

        // Then the weddingdate
        // phpcs:ignore
        $newDate    = TSJIPPY\sanitize($_POST['weddingdate'] ?? '' );
        $oldDate    = $family->getWeddingDate($object->userId);
        if ($newDate != $oldDate) {
            $events        = new CreateEvents();

            $events->createCelebrationEvent('Wedding anniversary', $object->userId, $oldDate, $newDate);
        }
    }

    return $submission;
}
