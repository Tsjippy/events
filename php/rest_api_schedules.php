<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', __NAMESPACE__ . '\schedulesRestApiInit');
function schedulesRestApiInit()
{
    //add_schedule
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/add_schedule',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $schedule        = new CreateSchedule();

                // Recursively sanitize all text fields in the array
                $settings = TSJIPPY\sanitize($_POST);

                return $schedule->addSchedule($settings);
            },
            'permission_callback'     => function () {
                $schedule    = new CreateSchedule();

                if (is_numeric($_POST['schedule-id'])) {
                    $schedule->getScheduleById($_POST['schedule-id']);
                }
                return $schedule->admin;
            },
            'args'                    => array(
                'target-name'        => array(
                    'required'    => true
                ),
                'start_date'        => array(
                    'required'    => true,
                ),
                'end_date'        => array(
                    'required'    => true
                ),
            )
        )
    );

    //publish_schedule
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/publish_schedule',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $schedule        = new CreateSchedule();

                $settings = TSJIPPY\sanitize($_POST);

                return $schedule->publishSchedule($settings);
            },
            'permission_callback'     => function () {
                $schedule    = new CreateSchedule();
                return $schedule->admin;
            },
            'args'                    => array(
                'schedule-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($scheduleId) {
                        return is_numeric($scheduleId);
                    }
                )
            )
        )
    );

    //remove_schedule
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/remove_schedule',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $schedule        = new CreateSchedule();
                return $schedule->removeSchedule($_POST['schedule-id']);
            },
            'permission_callback'     => function () {
                $schedule    = new CreateSchedule();
                return $schedule->admin;
            },
            'args'                    => array(
                'schedule-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($scheduleId) {
                        return is_numeric($scheduleId);
                    }
                )
            )
        )
    );

    //add_host
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/add_host',
        array(
            'methods'                 => 'POST',
            'callback'                 => __NAMESPACE__ . '\addHost',
            'permission_callback'     => function () {
                return current_user_can('read');
            },
            'args'                    => array(
                'schedule-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($scheduleId) {
                        return is_numeric($scheduleId);
                    }
                ),
                'date'        => array(
                    'required'    => true,
                    'validate_callback' => function ($param) {
                        return TSJIPPY\isDate($param);
                    }
                ),
                'start_time'        => array(
                    'required'    => true,
                    'validate_callback' => function ($param) {
                        return TSJIPPY\isTime($param);
                    }
                )
            )
        )
    );

    //remove_host
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/remove_host',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $schedule                = new CreateSchedule();
                return $schedule->removeHost($_POST['session-id']);
            },
            'permission_callback'     => function () {
                return current_user_can('read');
            },
            'args'                    => array(
                'session-id'        => array(
                    'required'            => true,
                    'validate_callback' => function ($scheduleId) {
                        return is_numeric($scheduleId);
                    }
                )
            )
        )
    );

    //add_menu
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/events',
        '/add_menu',
        array(
            'methods'                 => 'POST',
            'callback'                 => function () {
                $schedule        = new CreateSchedule();

                $settings = TSJIPPY\sanitize($_POST);

                return $schedule->addMenu($settings);
            },
            'permission_callback'     => function () {
                return current_user_can('read');
            },
            'args'                    => array(
                'schedule-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($scheduleId) {
                        return is_numeric($scheduleId);
                    }
                ),
                'date'        => array(
                    'required'    => true,
                    'validate_callback' => function ($param) {
                        return TSJIPPY\isDate($param);
                    }
                ),
                'start_time'        => array(
                    'required'    => true
                ),
                'recipe-keyword'        => array(
                    'required'    => true
                ),
            )
        )
    );
}

function addHost($param)
{
    $schedules        = new CreateSchedule();

    $settings = TSJIPPY\sanitize($_POST);

    if (is_array($settings['date'])) {
        $schedule        = $schedules->getScheduleById($schedules->scheduleId);

        $succesFull    = '';
        $succes        = '';
        $unSuccesFull  = '';
        $html          = [];

        foreach ($settings['date'] as $date) {
            $result    = $schedules->addHost($date, $settings);

            if (is_wp_error($result)) {
                if (!empty($unSuccesFull)) {
                    $unSuccesFull        .= ' and ';
                }
                $unSuccesFull    .= gmdate(TSJIPPY\DATEFORMAT, strtotime($date));
            } else {
                if (!empty($succesFull)) {
                    $succesFull        .= ' and ';
                }
                $succesFull    .= gmdate(TSJIPPY\DATEFORMAT, strtotime($date));

                $html[$date]    = $result['html'];

                $succes        = explode(" as a host for $schedule->name on", $result['message'])[0] . " as a host for $schedule->name on";
            }
        }

        $msg    = '';
        if (!empty($succesFull)) {
            $msg    .= "$succes $succesFull";
        }
        if (!empty($unSuccesFull)) {
            $msg    .= "Existing bookings where found on $unSuccesFull";
        }

        return [
            'message'    => $msg,
            'html'        => $html
        ];
    }

    return $schedules->addHost($settings['date'], $settings);
}
