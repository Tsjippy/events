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
        $url        = SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'schedules_pages')."?schedule={$result[0]->schedule_id}&session={$result[0]->id}";

        $buttonHtml	= "<a href=$url class='button small'>Edit this schedule session</a>";
    }

    return $buttonHtml;
}

// Do not display single events in search results
/**
 * This function modifies the main WordPress query to remove 
 * pages from search results.
 *
 * @param object $query The main WordPress query.
 */
function tg_exclude_pages_from_search_results( $query ) {
    if ( $query->is_main_query() && $query->is_search() && ! is_admin() ) {
        $query->set( 'post_type', array( 'post' ) );
    }    
}
add_action( 'pre_get_posts', __NAMESPACE__.'\tg_exclude_pages_from_search_results' );

add_action('loop_start', __NAMESPACE__.'\loopStart');
function loopStart($query){
	if($query->is_main_query() && is_search()){
		ob_start();
	}
}