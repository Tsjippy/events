<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules", __NAMESPACE__.'\schedules');
function schedules(){
	return displaySchedules();
}

add_shortcode('missing_events', __NAMESPACE__ . '\showMissingEvents');
function showMissingEvents(){
	$posts = get_posts(
		array(
			'post_type'		=> 'event',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    $html   = "<table class='sim table'>";

    foreach($posts as $post){
        $celDate    = get_post_meta($post->ID, 'celebrationdate', true);

        $author   = get_user_by('id', $post->post_author);
        if(!$author){
            wp_delete_post($post->ID);
        }

        if (!empty($celDate)){
            if(has_term(44, "events", $post->ID)  && $celDate != get_user_meta($post->post_author, 'arrival_date', true)){
                if(get_user_meta($post->post_author, 'SIM Nigeria anniversary_event_id', true) != $post->ID && get_user_meta($post->post_author, 'Wedding anniversary_event_id', true) != $post->ID){
                    wp_delete_post($post->ID);
                }else{
                    $weddingDate    = get_user_meta($post->post_author, 'family', true);

                    if(!is_array($weddingDate) || $celDate != $weddingDate['weddingdate']){
                        $html   .= "<tr><td>Event date: $celDate.</td>";
                        $html   .= "<td>Arrival date: ".get_user_meta($post->post_author, 'arrival_date', true)."</td>";
    
                        $html   .= "<td>";
                        if(!empty( $weddingDate)){
                            $html   .= "Wedding date: {$weddingDate['weddingdate']}";
                        }else{
                            $html   .= " - ";
                        }
                        $html   .= "</td>";
                        $html   .= "<td><a href='/add-content/?post_id=$post->ID' target='_blank'>Edit event</a></td></tr>";
                    }
                }
            }

            if(has_term(43, "events", $post->ID)  && $celDate != get_user_meta($post->post_author, 'birthday', true)){
                if(get_user_meta($post->post_author, 'birthday_event_id', true) != $post->ID){
                    wp_delete_post($post->ID);
                }else{
                    $html   .= "<tr><td>Event date: $celDate.</td>";
                    $html   .= "<td>Birthdate: ".get_user_meta($post->post_author, 'birthday', true)."</td><td> - </td>";
                    $html   .= "<td><a href='/add-content/?post_id=$post->ID' target='_blank'>Edit event</a></td></tr>";
                }
                
            }            
        };
    }

    foreach(getUserAccounts() as $user){
        if(empty(get_user_meta($user->ID, 'birthday_event_id', true))){
            $html   .= "<tr><td>Missing Birthdays event</td><td><a href='/edit-users/?userid=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }

        if(empty(get_user_meta($user->ID, 'SIM Nigeria anniversary_event_id', true))){
            $html   .= "<tr><td>Missing Anniversary event</td><td><a href='/edit-users/?userid=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }

        $weddingDate    = get_user_meta($user->ID, 'family', true);

        if(is_array($weddingDate) && !empty($weddingDate['weddingdate']) && empty(get_user_meta($user->ID, 'Wedding anniversary_event_id', true))){
            $html   .= "<tr><td>Missing Wedding event</td><td><a href='/edit-users/?userid=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }
    }
    $html   .= "</table>";

    return $html;
}