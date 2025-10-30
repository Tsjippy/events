<?php
namespace SIM\EVENTS;
use SIM;

add_shortcode("schedules", __NAMESPACE__.'\schedules');
function schedules(){
	return displaySchedules();
}

add_shortcode('missing_events', __NAMESPACE__ . '\showMissingEvents');
function showMissingEvents(){
    $family					= new SIM\FAMILY\Family();

	$posts = get_posts(
		array(
			'post_type'		=> 'event',
			//'author'		=> 137,
			'numberposts'	=> -1,
            'tax_query'     => [
                [
                    'taxonomy'          => 'events',
                    'field'             => 'slug', 
                    'terms'             => 'anniversary',
                    'include_children'  => true
                ]
            ]
		)
	);

    $anniversaryRows    = [];
    $weddingRows        = [];
    $birthdayRows       = [];

    foreach($posts as $post){
        $celDate        = get_post_meta($post->ID, 'celebrationdate', true);
        if(!empty($celDate)){
            $celDate    = date(DATEFORMAT, strtotime($celDate));
        }

        $arrivalDate    = get_user_meta($post->post_author, 'arrival_date', true);
        if(!empty($arrivalDate)){
            $arrivalDate    = date(DATEFORMAT, strtotime($arrivalDate));
        }

        $weddingDate    = $family->getWeddingDate($post->post_author);
        $weddingDate    = date(DATEFORMAT, strtotime($weddingDate));

        $author     = get_user_by('id', $post->post_author);
        if(!$author){
            wp_delete_post($post->ID);
            continue;
        }

        if ( !empty($celDate)){
            if($celDate != $arrivalDate){
                // this is a rogue event
                if( get_user_meta($post->post_author, 'SIM Nigeria anniversary_event_id', true) != $post->ID ){
                    wp_delete_post($post->ID);
                }

                $anniversaryRows[]  = [
                    $author,
                    $celDate,
                    $arrivalDate,
                    $post->ID
                ];
            }

            if( $celDate != $weddingDate){
                // this is a rogue event
                if( get_user_meta($post->post_author, 'Wedding anniversary_event_id', true) != $post->ID ){
                    wp_delete_post($post->ID);
                }

                $weddingRows[]  = [
                    $author,
                    $celDate,
                    $weddingDate,
                    $post->ID
                ];
            }
        }else{
            echo 'fail';
        }
    }

    $posts = get_posts(
		array(
			'post_type'		=> 'event',
			'numberposts'	=> -1,
            'tax_query'     => [
                [
                    'taxonomy'          => 'events',
                    'field'             => 'slug', 
                    'terms'             => 'birthday',
                    'include_children'  => true
                ]
            ]
		)
	);

    foreach($posts as $post){
        if(get_user_meta($post->post_author, 'birthday_event_id', true) != $post->ID){
            wp_delete_post($post->ID);
            continue;
        }

        $celDate        = get_post_meta($post->ID, 'celebrationdate', true);
        if(!empty($celDate)){
            $celDate    = date(DATEFORMAT, strtotime($celDate));
        }

        $author     = get_user_by('id', $post->post_author);
        if(!$author){
            wp_delete_post($post->ID);
            continue;
        }

        if (!empty($celDate)){
            $birthday   = get_user_meta($post->post_author, 'birthday', true);
            if(!empty($birthday)){
                $birthday    = date(DATEFORMAT, strtotime($birthday));
            }

            if($celDate != $birthday){
                $birthdayRows[]  = [
                    $author,
                    $celDate,
                    $birthday,
                    $post->ID
                ];               
            }            
        }else{
            echo 'fail';
        }
    }

    ob_start();

    if(!empty($anniversaryRows)){
        ?>
        <h4>SIM Anniversaries</h4>
        <?php
        tableBody($anniversaryRows);
    }

    if(!empty($weddingRows)){
        ?>
        <h4>Wedding Anniversaries</h4>
        <?php
        tableBody($weddingRows);
    }

    if(!empty($birthdayRows)){
        ?>
        <h4>Birthdays</h4>
        <?php
        tableBody($birthdayRows);
    }

    $missingEvents   = '';

    foreach(get_users() as $user){
        if(empty(get_user_meta($user->ID, 'birthday_event_id', true))){
            $missingEvents   .= "<tr><td>Birthdays</td><td><a href='/edit-users/?user-id=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }

        if(empty(get_user_meta($user->ID, 'SIM Nigeria anniversary_event_id', true))){
            $missingEvents   .= "<tr><td>Anniversary</td><td><a href='/edit-users/?user-id=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }

        $weddingDate    = $family->getWeddingDate($user->ID);

        if($weddingDate && empty(get_user_meta($user->ID, 'Wedding anniversary_event_id', true))){
            $missingEvents   .= "<tr><td>Wedding</td><td><a href='/edit-users/?user-id=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
        }
    }
   
    ?>
    <h4>Missing Events</h4>
    <table class='sim table'>
        <thead>
            <th>Type</th>
            <th>Link</th>
        </thead>
        <tbody>
            <?php echo $missingEvents;?>
        </tbody>
    </table>

    <?php

    return ob_get_clean();
}

function tableBody($data){
    ?>
    <table class='sim table'>
        <thead>
            <th>User</th>
            <th>Event Date</th>
            <th>Meta Date</th>
            <th>Actions</th>
        </thead>
        <tbody>
            <?php
            foreach($data as $row){
                ?>
                <tr>
                    <?php
                    foreach($row as $index=>$d){
                        if($index == 0){
                            echo "<td>{$d->display_name}</td>";
                        }elseif($index == 1 || $index == 2){
                            echo "<td>$d</td>";
                        }else{
                            echo "<td><a href='/add-content/?post-id=$d' target='_blank'>Edit event</a></td>";
                        }
                    }
                    ?>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <?php
}