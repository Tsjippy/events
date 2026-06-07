<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

add_action('rest_api_init',  __NAMESPACE__ . '\blockRestApiInit');
function blockRestApiInit()
{
    // show schedules
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/show_schedules',
        array(
            'methods'                 => 'GET',
            'callback'                 => __NAMESPACE__ . '\showSchedules',
            'permission_callback'     => function ($rest) {
                return current_user_can('read');
            },
        )
    );

    // show upcoming arrivals
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/upcoming_arrivals',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\upcomingArrivals',
            'permission_callback'     => function ($rest) {
                return current_user_can('read');
            },
        )
    );

    // Upcoming events
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/upcoming_events',
        array(
            'methods'                 => 'GET',
            'callback'                 => __NAMESPACE__ . '\upcomingEvents',
            'permission_callback'     => '__return_true',                        // allow public access for public events
        )
    );
}

function showSchedules()
{
    $schedule        = new Schedules();
    return $schedule->showSchedules();
}

/**
 * Show the upcoming arrivals block
 *
 * @param \WP_REST_Request|array $param    The parameters for the block, either as an array or as a WP_REST_Request object
 */
function upcomingArrivals($param)
{
    if (!is_array($param)) {
        $param    = $param->get_Params();
    }

    return upcomingArrivalsBlock($param);
}

function upcomingEvents($rest)
{
    $events        = new DisplayEvents();

    $items   = 10;
    $months  = 3;
    $cats    = [];
    $include = [];

    if (is_numeric($_GET['items'] ?? false)) {
        $items    = TSJIPPY\sanitize($_GET['items']);
    }

    if (is_numeric($_GET['months'] ?? false)) {
        $months    = TSJIPPY\sanitize($_GET['months']);
    }

    if (!empty($_GET['categories'])) {
        $cats    = explode(',', trim(TSJIPPY\sanitize($_GET['categories']), ','));

        $categories    = get_categories(array(
            'taxonomy'        => 'events',
            'hide_empty'     => false,
        ));

        $exclude    = $cats;

        foreach ($categories as $category) {
            if (!in_array($category->term_id, $exclude)) {
                $include[]    = $category->term_id;
            }
        }
    }

    return $events->upcomingEventsArray($items, $months, $include);
}
