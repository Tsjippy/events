<?php
namespace SIM\EVENTS;
use SIM;

add_action('sim_plugin_update', function($oldVersion){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    SIM\printArray($oldVersion);

    if($oldVersion < '2.41.7'){
        $schedules = new Schedules();

        SIM\printArray($oldVersion);

        maybe_add_column($schedules->tableName, 'fixed_timeslot_size', "ALTER TABLE $schedules->tableName ADD COLUMN `fixed_timeslot_size` boolean NOT NULL");

        maybe_add_column($schedules->tableName, 'subject', "ALTER TABLE $schedules->tableName ADD COLUMN `subject` longtext NOT NULL");

        SIM\printArray('Columns added');
    }
});