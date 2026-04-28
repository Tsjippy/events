<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Remove event when post is deleted
add_action( 'before_delete_post', __NAMESPACE__.'\befoeDeletePost');
function befoeDeletePost($postId){
    $events = new CreateEvents();
    $events->removeDbRows($postId);
}

add_filter('post-edit-button', __NAMESPACE__.'\editButton', 10, 3);
function editButton($buttonHtml, $post, $content){
    if($post->post_type != 'event'){
        return $buttonHtml;
    }

    global $wpdb;

    $schedules  = new Schedules();

    $query  = "SELECT * FROM `{$schedules->sessionTableName}` WHERE `post_ids` LIKE '%{$post->ID}%';";
    $result = $wpdb->get_results($query);
    
    if(!empty($result)){;
        $url        = TSJIPPY\ADMIN\getDefaultPageLink('events', 'schedules-pages')."?schedule={$result[0]->schedule_id}&session={$result[0]->id}";

        $buttonHtml	= "<a href=$url class='button small'>Edit this schedule session</a>";
    }

    return $buttonHtml;
}

add_filter('tsjippy-theme-archive-page-title', __NAMESPACE__.'\changeArchiveTitle', 10, 2);
function changeArchiveTitle($title, $category){
    if($title == 'Event Posts'){
        $title = 'Calendar';
    }elseif(is_tax('events')){
        $title .= ucfirst($category->name).' Events';
    }
	
	return $title;
}

/**
 * Add a description to the schedules page
 */
add_filter('display_post_states', __NAMESPACE__.'\postStates', 10, 2);
function postStates( $states, $post ) {
    
    if (in_array($post->ID, SETTINGS['schedules-page'] ?? [])) {
        $states[] = __('Schedules page');
    }

    return $states;
}