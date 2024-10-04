<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'wp_after_insert_post', function($postId, $post){
    if(has_shortcode($post->post_content, 'schedules')){
        global $Modules;

        if(!is_array($Modules[MODULE_SLUG]['schedule_pages'])){
            $Modules[MODULE_SLUG]['schedule_pages']    = [$postId];
        }else{
            $Modules[MODULE_SLUG]['schedule_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }

    if(has_shortcode($post->post_content, 'upcomingevents')){
        global $Modules;

        if(!is_array($Modules[MODULE_SLUG]['upcomingevents_pages'])){
            $Modules[MODULE_SLUG]['upcomingevents_pages']    = [$postId];
        }else{
            $Modules[MODULE_SLUG]['upcomingevents_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_trash_post', function($postId){
    global $Modules;

    $pages  = SIM\getModuleOption(MODULE_SLUG, 'upcomingevents_pages', false);
    $index  = array_search($postId, $pages);
    if($index){
        unset($Modules[MODULE_SLUG]['upcomingevents_pages'][$index]);
        $Modules[MODULE_SLUG]['upcomingevents_pages']   = array_values($pages);
        update_option('sim_modules', $Modules);
    }

    $pages  = SIM\getModuleOption(MODULE_SLUG, 'schedule_pages', false);
    $index  = array_search($postId, $pages);
    if($index){
        unset($Modules[MODULE_SLUG]['schedule_pages'][$index]);
        $Modules[MODULE_SLUG]['schedule_pages']   = array_values($pages);
        update_option('sim_modules', $Modules);
    }
} );

add_action( 'wp_enqueue_scripts', function(){
    if(str_contains($_SERVER['REQUEST_URI'], '.map')){
        return;
    }

    wp_register_script('sim_frontend_events_script', plugins_url('js/frontend-event.min.js', __DIR__), [], MODULE_VERSION, true);
    add_filter('sim-frontend-content-js', function($dependables){
        $dependables[]  = 'sim_frontend_events_script';

        return $dependables;
    });

    //css
    wp_register_style('sim_schedules_css', plugins_url('css/schedules.min.css', __DIR__), array(), MODULE_VERSION);
    wp_register_style('sim_events_css', plugins_url('css/events.min.css', __DIR__), array(), MODULE_VERSION);
        
    //js
    wp_register_script('sim_event_script', plugins_url('js/events.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION,true);

    if(wp_is_mobile()){
        wp_register_script('sim_schedules_script', plugins_url('js/mobile-schedule.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);
    }else{
        wp_register_script('sim_schedules_script', plugins_url('js/desktop-schedule.min.js', __DIR__), array('sim_table_script','selectable','sim_formsubmit_script'), MODULE_VERSION, true);
    }

    $schedulePages         = (array)SIM\getModuleOption(MODULE_SLUG, 'schedule_pages');
    $upcomingEventsPages   = (array)SIM\getModuleOption(MODULE_SLUG, 'upcomingevents_pages');
    if(is_numeric(get_the_ID())){
        if(in_array(get_the_ID(), $schedulePages)){
        wp_enqueue_style('sim_schedules_css');

            wp_enqueue_script('sim_schedules_script');
        }elseif(in_array(get_the_ID(), $upcomingEventsPages)){
            wp_enqueue_style('sim_events_css');
        }
    }
});