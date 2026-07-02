<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_after_insert_post', __NAMESPACE__ . '\afterInsertPost', 10, 2);
/**
 * After insert post
 *
 * @param int $postId
 * @param \WP_Post $post
 */
function afterInsertPost($postId, $post)
{

    if (has_shortcode($post->post_content, 'upcomingevents')) {
        $pages          = SETTINGS['upcomingevents-pages'] ?? [];

        $pages[$postId] = $postId;

        $settings       = SETTINGS;
        $settings['upcomingevents-pages']  = $pages;

        update_option("tsjippy_events_settings", $settings);
    }
}

add_action('wp_trash_post',  __NAMESPACE__ . '\trashPost');
/**
 * Trash post
 *
 * @param int $postId
 */
function trashPost($postId)
{
    $pages  = SETTINGS['upcomingevents-pages'] ?? [];
    if (isset($pages[$postId])) {
        unset($pages[$postId]);
        $settings   = SETTINGS;
        $settings['upcomingevents-pages']  = $pages;

        update_option("tsjippy_events_settings", $settings);
    }
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\loadAssets');
/**
 * Load assets
 */
function loadAssets()
{
    if (str_contains($_SERVER['REQUEST_URI'] ?? '', '.map')) {
        return;
    }

    wp_register_script('tsjippy_frontend_events_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/frontend-event.min.js'), [], PLUGINVERSION, true);
    add_filter('tsjippy-frontend-content-js', __NAMESPACE__ . '\addDependable');

    //css
    wp_register_style('tsjippy_events_css', TSJIPPY\pathToUrl(PLUGINPATH . 'css/events.min.css'), array(), PLUGINVERSION);

    //js
    wp_register_script('tsjippy_event_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/events.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION, true);
}

/**
 * Add dependable
 *
 * @param array $dependables
 * @return array
 */
function addDependable($dependables)
{
    $dependables[]  = 'tsjippy_frontend_events_script';

    return $dependables;
}
