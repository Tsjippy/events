<?php
namespace SIM\EVENTS;
use SIM;

// remove all events related to the user to be deleted
add_action('delete_user', function ($userId, $reassign){
    $events     = new Events();

    $allMeta    = get_user_meta($userId);

    $celebrationIds = array_filter($allMeta, function($key){
        return str_contains($key, '_event_id');
    }, ARRAY_FILTER_USE_KEY);

    // Remove the celebration events
    foreach($celebrationIds as $id){
        $events->removeDbRows($id[0], true);
    }

    // add the remainder to the new author or delete
    $eventIds = get_posts(
        [
            'author'        => $userId,
            'fields'        => 'ids',
            'numberposts'   => -1,
            'post_type'     => 'any'
        ]
    );

    foreach($eventIds as $id){
        if(empty($reassign)){
            wp_trash_post($id);
        }else{
            // update the autho
            $arg = array(
                'ID'            => $id,
                'post_author'   => $reassign,
            );
            wp_update_post( $arg, false, false );
        }
    }
}, 10, 2);