<?php
namespace SIM\EVENTS;
use SIM;

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
        $url        = SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'schedules-pages')."?schedule={$result[0]->schedule_id}&session={$result[0]->id}";

        $buttonHtml	= "<a href=$url class='button small'>Edit this schedule session</a>";
    }

    return $buttonHtml;
}

add_filter('sim-theme-archive-page-title', __NAMESPACE__.'\changeArchiveTitle', 10, 2);
function changeArchiveTitle($title, $category){
    if($title == 'Event Posts'){
        $title = 'Calendar';
    }elseif(is_tax('events')){
        $title .= ucfirst($category->name).' Events';
    }
	
	return $title;
}