<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Remove event when post is deleted
add_action('before_delete_post', __NAMESPACE__ . '\befoeDeletePost');
function befoeDeletePost($postId)
{
    $events = new CreateEvents();
    $events->removeDbRows($postId);
}

add_filter('tsjippy-theme-archive-page-title', __NAMESPACE__ . '\changeArchiveTitle', 10, 2);
function changeArchiveTitle($title, $category)
{
    if ($title == 'Event Posts') {
        $title = 'Calendar';
    } elseif (is_tax('events')) {
        $title .= ucfirst($category->name) . ' Events';
    }

    return $title;
}
