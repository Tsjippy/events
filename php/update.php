<?php
namespace SIM\EVENTS;
use SIM;

add_action('sim_events_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    SIM\printArray($oldVersion);

    if($oldVersion < '8.1.5'){
        $events	= new CreateEvents();

        $posts = get_posts([
            'post_type' => 'event',
            'numberposts' => -1
        ]);

        foreach($posts as $post){
            $eventMeta		= get_post_meta($post->ID, 'eventdetails', true);
            if(!is_array($eventMeta)){
                if(!empty($eventMeta)){
                    $eventMeta	= (array)json_decode($eventMeta, true);

                    if(!empty($eventMeta['repeat']['type']) && $eventMeta['repeat']['type'] == 'yearly'){
                        $events->eventData  = $eventMeta;
                        $events->postId     = $post->ID;
                        $events->createEvents();
                    }
                }else{
                    $eventMeta	= [];
                }
            }
        }
    }
}