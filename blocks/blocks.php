<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', function () {
    register_block_type(
        __DIR__ . '/upcomingEvents/build',
        array(
            'render_callback' => __NAMESPACE__ . '\displayUpcomingEvents',
            'icon'  => 'calendar (alt)'
        )
    );

    register_block_type(
        __DIR__ . '/metadata/build',
        array(
            "attributes"    =>  [
                "lock"    => [
                    "type"        => "object",
                    "default"    => [
                        "move"   => true,
                        "remove" => true
                    ]
                ],
                'event'    => [
                    'type'       => 'string',
                    'default'    => ''
                ]
            ]
        )
    );
});

function displayUpcomingEvents($attributes)
{

    $args = wp_parse_args($attributes, array(
        'items'      => 10,
        'months'     => 3,
        'categories' => []
    ));

    $categories    = get_categories(array(
        'taxonomy'   => 'events',
        'hide_empty' => false,
    ));

    $exclude    = $args['categories'];

    $include    = [];

    foreach ($categories as $category) {
        if (!isset($exclude[$category->term_id]) || $exclude[$category->term_id] !== true) {
            $include[]    = $category->term_id;
        }
    }

    $events        = new DisplayEvents();

    return $events->upcomingEvents($args['items'], $args['months'], $include, $args['title']);
}

// register custom meta tag field
add_action('init', __NAMESPACE__ . '\registerPostMeta');
function registerPostMeta()
{
    register_post_meta('event', "tsjippy_eventdetails", array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}

add_action('added_post_meta', __NAMESPACE__ . '\createEvents', 10, 4);
add_action('updated_postmeta', __NAMESPACE__ . '\createEvents', 10, 4);

function createEvents($metaId, $postId,  $metaKey,  $metaValue)
{
    if (
        $metaKey != 'eventdetails' ||
        empty($metaValue)
    ) {
        return;
    }

    $metaValue        = json_decode($metaValue, true);

    // Do not create events for the past
    if (
        !empty($metaValue) &&
        !empty($metaValue['start_date']) &&
        $metaValue['start_date'] < gmdate('Y-m-d')
    ) {
        return;
    }

    $events            = new CreateEvents();
    $events->postId    = $postId;

    //check if anything has changed
    $events->removeDbRows();

    //create events
    $events->eventData        = $metaValue;
    $result                    = $events->createEvents();

    if (is_wp_error($result)) {
        TSJIPPY\printArray($result);
        TSJIPPY\printArray($metaValue);
    }
}
