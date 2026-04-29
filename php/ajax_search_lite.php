<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter('asl_query_cpt', __NAMESPACE__.'\excludePersonalEventsFromSearch', 10, 4);
function excludePersonalEventsFromSearch($querystr, $args, $id, $_ajax_search){
    global $wpdb;

    $events         = new Events();

    // The only_for and atendees columns
    $querystr       = str_replace("$wpdb->posts.post_title as title,","$events->tableName.only_for as only_for, $events->tableName.atendees as atendees, $wpdb->posts.post_title as title,", $querystr);

    // Add the joined to join posts and events table
    $querystr       = str_replace("FROM $wpdb->posts", "FROM $wpdb->posts\nLEFT JOIN $events->tableName ON $wpdb->posts.ID = $events->tableName.post_id", $querystr);

    return $querystr;
}

add_filter('asl_cpt_query_add_where', __NAMESPACE__.'\excludePersonalEventsFromSearchWhereClause');
function excludePersonalEventsFromSearchWhereClause($where){
    $family	= new TSJIPPY\FAMILY\Family();
    $userId = get_current_user_id();
    
    if($userId === 0){
        return "AND (only_for IS NULL) $where";
    }
    
    $family->getFamilyName($userId, false, $partnerId);
    $partnerString = '';
    
    if(!empty($partnerId)){
        $partnerString = "OR only_for = $partnerId";
    }
    
    return "AND (only_for IS NULL OR only_for = $userId $partnerString OR atendees LIKE '%;i:$userId;%') $where";
}