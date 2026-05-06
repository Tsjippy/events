<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) exit;

class AfterUpdate extends TSJIPPY\AfterPluginUpdate {

    public function afterPluginUpdate($oldVersion){
        global $wpdb;

        TSJIPPY\printArray('Running update actions');

        if(version_compare('10.0.3', $oldVersion)){
            /**
             * Rename tables to tsjippy_
             */
            $wpdb->query(
                "ALTER TABLE `{$wpdb->prefix}tsjippy_events`
                RENAME COLUMN `startdate` to `start_date`,
                RENAME COLUMN `enddate` to `end_date`,
                RENAME COLUMN `starttime` to `start_time`,
                RENAME COLUMN `endtime` to `end_time`,
                RENAME COLUMN `onlyfor` to `only_for`;"
            );

            $wpdb->query(
                "ALTER TABLE `{$wpdb->prefix}tsjippy_schedules`
                RENAME COLUMN `startdate` to `start_date`,
                RENAME COLUMN `enddate` to `end_date`,
                RENAME COLUMN `starttime` to `start_time`,
                RENAME COLUMN `endtime` to `end_time`,
                RENAME COLUMN `hidenames` to `hide_names`;"
            );
        }
    }
}
