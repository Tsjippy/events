<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}
add_shortcode('tsjippy_missing_events', __NAMESPACE__ . '\showMissingEvents');
/**
 * Show missing events
 *
 * @return string
 */
function showMissingEvents()
{
    $adminMenu  = new AdminMenu(SETTINGS, 'Events');

    return $adminMenu->data();
}
