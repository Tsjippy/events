<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', __NAMESPACE__ . '\restApiInit');
function restApiInit()
{
    // Month calendar
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/get_month_html',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $events     = new DisplayEvents();
                $month      = (int) $_POST['month'];
                $year       = (int) $_POST['year'];

                return $events->monthCalendar('01', $month, $year);
            },
            'permission_callback'     => '__return_true',                // Allow public access
            'args'                    => array(
                'month'        => array(
                    'required'    => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
                'year'        => array(
                    'required'    => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            )
        )
    );

    // Week calendar
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/get_week_html',
        array(
            'methods'        => 'POST',
            'callback'       => function () {
                $events      = new DisplayEvents();

                $weekNr      = (int) $_POST['wknr'];
                $year        = (int) $_POST['year'];

                return $events->weekCalendar($weekNr, $year);
            },
            'permission_callback'     => '__return_true',                // Allow public access
            'args'                    => array(
                'wknr'        => array(
                    'required'    => true,
                    'validate_callback' => function ($weekNr) {
                        return is_numeric($weekNr);
                    }
                ),
                'year'        => array(
                    'required'    => true,
                    'validate_callback' => function ($year) {
                        return is_numeric($year);
                    }
                ),
            )
        )
    );

    // List calendar
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/get_list_html',
        array(
            'methods'                 => 'POST,GET',
            'callback'                 => function () {
                $events        = new DisplayEvents();
                $offset        = (int) $_POST['offset'];
                $month         = (int) $_POST['month'];
                $year          = (int) $_POST['year'];
                return $events->listCalendar($offset, $month, $year);
            },
            'permission_callback'     => '__return_true',                // Allow public access
            'args'                    => array(
                'offset'        => array(
                    'required'    => true,
                    'validate_callback' => function ($offset) {
                        return is_numeric($offset);
                    }
                ),
            )
        )
    );
}
