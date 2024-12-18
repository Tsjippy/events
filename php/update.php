<?php
namespace SIM\EVENTS;
use SIM;

add_action('sim_events_module_update', __NAMESPACE__.'\pluginUpdate');
function pluginUpdate($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    SIM\printArray($oldVersion);

    if($oldVersion < '2.41.7'){
        $schedules = new Schedules();

        SIM\printArray($oldVersion);
    }
}