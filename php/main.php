<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'before_delete_post', function($postId){
    $events = new CreateEvents();
    $events->removeDbRows($postId);
});

add_filter('post-edit-button', function($buttonHtml, $post, $content){
    if($post->post_type != 'event'){
        return $buttonHtml;
    }

    global $wpdb;

    $schedules  = new Schedules();

    $query  = "SELECT * FROM `{$schedules->sessionTableName}` WHERE `post_ids` LIKE '%{$post->ID}%';";
    $result = $wpdb->get_results($query);
    
    if(!empty($result)){;
        $url        = SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'schedules_pages')."?schedule={$result[0]->schedule_id}&session={$result[0]->id}";

        $buttonHtml	= "<a href=$url class='button small'>Edit this schedule session</a>";
    }

    return $buttonHtml;
}, 10, 3);