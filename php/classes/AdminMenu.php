<?php
namespace SIM\EVENTS;
use SIM;
use SIM\ADMIN;

use function SIM\addElement;
use function SIM\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        addElement('label', $parent, ['for' => 'freq'], __('How often should we check for expired events?'));

        $this->recurrenceSelector('freq', $this->settings['freq'] ?? '', $parent);

        addElement('br', $parent);

        addElement('label', $parent, ['for' => 'freq'], __('Minimum age of events before they get removed:'));

        $select = addElement('select', $parent, ['name' => 'max-age']);

        $strings	= [
            '1 day',
            '1 week',
            '1 month',
            '3 months',
            '1 year'
        ];

        $maxAge     = $this->settings["max-age"] ?? '';
        foreach($strings as $string){
            addElement(
                'option', 
                $select, 
                [
                    'value'     => $string,
                    'selected'  => $string == $maxAge ? 'selected' : ''
                ],
                $string
            );
        }

        addElement('br', $parent);

        addElement('h4', $parent, [], 'Default picture for birthdays');

        $this->pictureSelector('birthday_image', 'Birthdays', $parent);

        addElement('br', $parent);

        addElement('h4', $parent, [], 'Default picture for anniversaries');

        $this->pictureSelector('anniversary_image', 'Anniversaries', $parent);

        return true;
    }

    public function emails($parent){
        return false;
    }

    public function data($parent=''){
        $family					= new SIM\FAMILY\Family();

        /**
         * Get all aniversary events
         */
        $posts = get_posts(
            array(
                'post_type'		=> 'event',
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

            $author     = get_user_by('id', $post->post_author);
            if(!$author){
                wp_delete_post($post->ID);
                continue;
            }

            if ( !empty($celDate)){
                $celDate    = date(DATEFORMAT, strtotime($celDate));

                $arrivalDate    = get_user_meta($post->post_author, 'arrival_date', true);
                if(!empty($arrivalDate)){
                    $arrivalDate    = date(DATEFORMAT, strtotime($arrivalDate));
                }
                if($celDate != $arrivalDate){
                    // this is a rogue event
                    if( get_user_meta($post->post_author, 'SIM Nigeria anniversary_event_id', true) != $post->ID ){
                        wp_delete_post($post->ID);
                    }else{

                        $anniversaryRows[]  = [
                            $author,
                            $celDate,
                            $arrivalDate,
                            $post->ID
                        ];
                    }
                }

                $weddingDate    = $family->getWeddingDate($post->post_author);
                $weddingDate    = date(DATEFORMAT, strtotime($weddingDate));
                if( $celDate != $weddingDate){
                    // this is a rogue event
                    if( get_user_meta($post->post_author, 'Wedding anniversary_event_id', true) != $post->ID ){
                        wp_delete_post($post->ID);
                    }else{

                        $weddingRows[]  = [
                            $author,
                            $celDate,
                            $weddingDate,
                            $post->ID
                        ];
                    }
                }
            }
        }

        /**
         * Get all birthday events
         */
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
            }
        }

        ob_start();

        if(!empty($anniversaryRows)){
            ?>
            <h4>SIM Anniversaries</h4>
            <?php
            $this->tableBody($anniversaryRows);
        }

        if(!empty($weddingRows)){
            ?>
            <h4>Wedding Anniversaries</h4>
            <?php
            $this->tableBody($weddingRows);
        }

        if(!empty($birthdayRows)){
            ?>
            <h4>Birthdays</h4>
            <?php
            $this->tableBody($birthdayRows);
        }

        $missingEvents   = '';

        foreach(get_users() as $user){
            if(empty(get_user_meta($user->ID, 'birthday_event_id', true))){
                $missingEvents   .= "<tr><td>Birthdays</td><td><a href='/edit-users/?user-id=$user->ID' target='_blank'>Edit $user->display_name</a></td></tr>";
            }

            if(empty(get_user_meta($user->ID, SITENAME.' anniversary_event_id', true))){
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

        if(empty($parent)){
            return ob_get_clean();
        }

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    private function tableBody($data){
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

    public function functions($parent){
        global $wpdb;

        $events     = new Events();
    
        /**
         * Get events of which the author account no longer exists
         */
        $query  	= "SELECT * FROM %i as events INNER JOIN %i as posts ON post_id = posts.ID WHERE posts.post_author NOT IN (SELECT ID FROM %i)";

        $orphans	= $wpdb->get_results(
            $wpdb->prepare($query, [$events->tableName, $wpdb->posts, $wpdb->users])
        );

        /**
         * Get events of which the post no longer exists
         */
        $query  	= "SELECT * FROM %i WHERE post_id NOT IN (SELECT ID FROM %i)";

        $orphans	= array_merge(
            $wpdb->get_results($wpdb->prepare($query, [$events->tableName, $wpdb->posts])), 
            $orphans
        );

        if(empty($orphans)){
            return false;
        }
        
        ob_start();
        ?>
        <h4>Orphan events</h4>
        <p>
            There are <?php echo count($orphans);?> events found linked to a valid user or post in the database.
        </p>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Start date</th>
                    <th>Start time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($orphans as $orphan){
                    ?>
                    <tr>
                        <th><?php echo $orphan->post_title;?></th>
                        <th><?php echo $orphan->startdate;?></th>
                        <th><?php echo $orphan->starttime;?></th>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <form method='POST'>
            <button type='submit' name='delete-orphans'>Remove these events</button>
        </form>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function postActions(){

        if(isset($_POST['delete-orphans'])){
            global $wpdb;

            $events = new Events();
            $query  	= "DELETE %i FROM %i as events INNER JOIN $wpdb->posts as posts ON events.post_id = posts.ID WHERE posts.post_author NOT IN (SELECT ID FROM %i)";

            $wpdb->query($wpdb->prepare($query, $events->tableName, $events->tableName, $wpdb->users));

            return "Succesfully deleted $wpdb->rows_affected orphan events.";
        }

        return '';
    }

    /**
     * Schedules the tasks for this module
     *
    */
    public function postSettingsSave(){
        SIM\scheduleTask('anniversary_check_action', 'daily');
        SIM\scheduleTask('remove_old_schedules_action', 'daily');
        SIM\scheduleTask('add_repeated_events_action', 'yearly');

        $freq   = SETTINGS['freq'] ?? false;

        if($freq){
            SIM\scheduleTask('remove_old_events_action', $freq);
        }
    }
}