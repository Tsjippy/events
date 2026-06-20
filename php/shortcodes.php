<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}
add_shortcode('tsjippy_missing_events', __NAMESPACE__ . '\showMissingEvents');
function showMissingEvents()
{
    $adminMenu  = new AdminMenu(SETTINGS, 'Events');

    return $adminMenu->data();
}
