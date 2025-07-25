<?php
namespace SIM\EVENTS;
use SIM;

add_filter('asl_query_cpt', __NAMESPACE__.'\excludePersonalEventsFromSearch', 10, 4);
function excludePersonalEventsFromSearch($querystr, $args, $id, $_ajax_search){
    global $wpdb;

    $events         = new Events();

    // THe onlyfor and atendees columns
    $querystr       = str_replace("$wpdb->posts.post_title as title,","$events->tableName.onlyfor as onlyfor, $events->tableName.atendees as atendees, $wpdb->posts.post_title as title,", $querystr);

    // Add the joined to join posts and events table
    $querystr       = str_replace("FROM $wpdb->posts", "FROM $wpdb->posts\nLEFT JOIN $events->tableName ON $wpdb->posts.ID = $events->tableName.post_id", $querystr);

    return $querystr;
}

add_filter('asl_cpt_query_add_where', __NAMESPACE__.'\excludePersonalEventsFromSearchWhereClause');
function excludePersonalEventsFromSearchWhereClause($where){
    $userId = wp_get_current_user()->ID;
    SIM\getFamilyName($userId, false, $partnerId);

    return "AND (onlyfor IS NULL OR onlyfor = $userId OR onlyfor = $partnerId OR atendees LIKE '%;i:$userId;%') $where";
}