<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    /**
     * Render the settings page for the plugin
     * 
     * @param    string        $parent        The parent menu slug
     * @return    boolean        True on success, false on failure
     */
    public function settings($parent)
    {
        $this->recurrenceSelector('freq', $this->settings['freq'] ?? '', 'How often should we check for expired events?', $parent);

        addElement('br', $parent);

        addElement('label', $parent, ['for' => 'freq'], __('Minimum age of events before they get removed:', '%TEXTDOMAIN%'));

        $select = addElement('select', $parent, ['name' => 'max-age']);

        $strings    = [
            '1 day',
            '1 week',
            '1 month',
            '3 months',
            '1 year'
        ];

        $maxAge     = $this->settings["max-age"] ?? '';
        foreach ($strings as $string) {
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

    /**
     * Render the emails page for the plugin
     */
    public function emails($parent)
    {
        return false;
    }

    /**
     * Function to display the emails page
     *
     * @param   string  $parent The parent menu slug
     * 
     * @return  bool            True if the emails page was displayed, false otherwise
     */
    public function data($parent = '')
    {
        $family                    = new TSJIPPY\FAMILY\Family();

        /**
         * Get all aniversary events
         */
        $posts = get_posts(
            array(
                'post_type'     => 'event',
                'numberposts'   => -1,
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

        foreach ($posts as $post) {
            $celDate        = get_post_meta($post->ID, 'tsjippy_celebrationdate', true);

            $author     = get_user_by('id', $post->post_author);
            if (!$author) {
                wp_delete_post($post->ID);
                continue;
            }

            if (!empty($celDate)) {
                $celDate        = gmdate(TSJIPPY\DATEFORMAT, strtotime($celDate));

                $arrivalDate    = get_user_meta($post->post_author, 'tsjippy_arrival_date', true);
                if (!empty($arrivalDate)) {
                    $arrivalDate    = gmdate(TSJIPPY\DATEFORMAT, strtotime($arrivalDate));
                }
                if ($celDate != $arrivalDate) {
                    // this is a rogue event
                    if (get_user_meta($post->post_author, 'tsjippy_'.TSJIPPY\SITENAME . 'anniversary_event_id', true) != $post->ID) {
                        wp_delete_post($post->ID);
                    } else {

                        $anniversaryRows[]  = [
                            $author,
                            $celDate,
                            $arrivalDate,
                            $post->ID
                        ];
                    }
                }

                $weddingDate    = $family->getWeddingDate($post->post_author);
                $weddingDate    = gmdate(TSJIPPY\DATEFORMAT, strtotime($weddingDate));
                if ($celDate != $weddingDate) {
                    // this is a rogue event
                    if (get_user_meta($post->post_author, 'tsjippy_Wedding anniversary_event_id', true) != $post->ID) {
                        wp_delete_post($post->ID);
                    } else {

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
                'post_type'        => 'event',
                'numberposts'    => -1,
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

        foreach ($posts as $post) {
            if (get_user_meta($post->post_author, 'tsjippy_birthday_event_id', true) != $post->ID) {
                wp_delete_post($post->ID);
                continue;
            }

            $celDate        = get_post_meta($post->ID, 'tsjippy_celebrationdate', true);
            if (!empty($celDate)) {
                $celDate    = gmdate(TSJIPPY\DATEFORMAT, strtotime($celDate));
            }

            $author     = get_user_by('id', $post->post_author);
            if (!$author) {
                wp_delete_post($post->ID);
                continue;
            }

            if (!empty($celDate)) {
                $birthday   = get_user_meta($post->post_author, 'tsjippy_birthday', true);
                if (!empty($birthday)) {
                    $birthday    = gmdate(TSJIPPY\DATEFORMAT, strtotime($birthday));
                }

                if ($celDate != $birthday) {
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

        if (!empty($anniversaryRows)) {
            ?>
            <h4>
                <?php echo esc_html(TSJIPPY\SITENAME);?> Anniversaries
            </h4>
            <?php
            $this->tableBody($anniversaryRows);
        }

        if (!empty($weddingRows)) {
        ?>
            <h4>
                Wedding Anniversaries
            </h4>
        <?php
            $this->tableBody($weddingRows);
        }

        if (!empty($birthdayRows)) {
            ?>
                <h4>
                    Birthdays with mismatch between the event date and the user meta date
                </h4>
            <?php
                $this->tableBody($birthdayRows);
        }

        ?>
        <h4>
            Missing Events
        </h4>
        <p>
            Each website user should have a birthday and site anniversary event.<br>
            This table lists all users who are missing one or both.
        </p>
        <table class='tsjippy table'>
            <thead>
                <th>
                    Type
                </th>
                <th>
                    Link
                </th>
            </thead>
            <tbody>
                <?php
                foreach (get_users() as $user) {
                    if (empty(get_user_meta($user->ID, 'tsjippy_birthday_event_id', true))) {
                        ?>
                        <tr>
                            <td>Birthdays</td>
                            <td>
                                <a href='/edit-users/?user-id=<?php echo esc_attr($user->ID); ?>' target='_blank'>
                                    Edit <?php echo esc_attr($user->display_name); ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }

                    if (empty(get_user_meta($user->ID, 'tsjippy_'.TSJIPPY\SITENAME . ' anniversary_event_id', true))) {
                    ?>
                        <tr>
                            <td>Anniversary</td>
                            <td>
                                <a href='/edit-users/?user-id=<?php echo esc_attr($user->ID); ?>' target='_blank'>
                                    Edit <?php echo esc_attr($user->display_name); ?>
                                </a>
                            </td>
                        </tr>
                    <?php
                    }

                    if ($family->getWeddingDate($user->ID) && empty(get_user_meta($user->ID, 'tsjippy_Wedding anniversary_event_id', true))) {
                        ?>
                        <tr>
                            <td>Wedding</td>
                            <td>
                                <a href='/edit-users/?user-id=<?php echo esc_attr($user->ID); ?>' target='_blank'>
                                    Edit <?php echo esc_attr($user->display_name); ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>

        <?php

        if (empty($parent)) {
            return ob_get_clean();
        }

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Render the table body for the given data
     * 
     * @param    array        $data        The data to render in the table body
     * @return    void
     */
    private function tableBody($data)
    {
        ?>
        <table class='tsjippy table'>
            <thead>
                <th>User</th>
                <th>Event Date</th>
                <th>Meta Date</th>
                <th>Actions</th>
            </thead>
            <tbody>
                <?php
                foreach ($data as $row) {
                ?>
                    <tr>
                        <?php
                        foreach ($row as $index => $d) {
                            if ($index == 0) {
                        ?>
                                <td>
                                    <?php echo esc_attr($d->display_name); ?>
                                </td>
                            <?php
                            } elseif ($index == 1 || $index == 2) {
                            ?>
                                <td>
                                    <?php echo esc_attr($d); ?>
                                </td>
                            <?php
                            } else {
                            ?>
                                <td>
                                    <a href='/add-content/?post-id=<?php echo esc_attr($d); ?>' target='_blank'>
                                        Edit event
                                    </a>
                                </td>
                        <?php
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

    /**
     * Add the functions page to the admin menu
     *
     * @param string $parent The parent menu slug
     * 
     * @return bool True if the functions page was added, false otherwise
     */
    public function functions($parent)
    {
        global $wpdb;

        $events     = new Events();

        /**
         * Get events of which the author account no longer exists
         */

        $ect        = "-- Table with user ID's\nWITH userIds AS ( " .$wpdb->prepare("SELECT ID FROM %i", $wpdb->users)." )";

        $orphans    = TSJIPPY\getFromDb(
            'events-without-author',
            'events',
            "$ect\n\n-- Main query\nSELECT * FROM %i as events INNER JOIN %i as posts ON post_id = posts.ID WHERE posts.post_author NOT IN (select * from userIds)",
            $events->tableName,
            $wpdb->posts
        );

        /**
         * Get events of which the post no longer exists
         */
        $ect                 = "-- Table with post ID's\nWITH postIds AS ( " .$wpdb->prepare("SELECT ID FROM %i", $wpdb->posts)." )";

        $orphans    = array_merge(
            TSJIPPY\getFromDb(
                'events-without-author',
                'events',
                "$ect\n\n-- Main query\nSELECT * FROM %i WHERE post_id NOT IN (select * from postIds)",
                $events->tableName,
                $wpdb->posts
            ),
            $orphans
        );

        if (empty($orphans)) {
            return false;
        }

        ob_start();
    ?>
        <h4>
            Orphan events
        </h4>
        <p>
            There are <?php echo count($orphans); ?> events found not linked to a valid user or post in the database.
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
                foreach ($orphans as $orphan) {
                ?>
                    <tr>
                        <th><?php echo esc_attr($orphan->post_title ?? ''); ?></th>
                        <th><?php echo esc_attr($orphan->start_date ?? ''); ?></th>
                        <th><?php echo esc_attr($orphan->start_time ?? ''); ?></th>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        <form method='POST'>
            <button type='submit' name='delete-orphans'>
                Remove these events
            </button>
        </form>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    /**
     * Handle post actions for the plugin
     *
     * @param    array        $request        The request data
     * @return    string        The response message
     */
    public function postActions($request)
    {
        // phpcs:ignore
        if (isset($request['delete-orphans'])) {
            global $wpdb;

            $events = new Events();

            TSJIPPY\removeFromDb(
                $events->tableName,
                [
                    "DELETE %i FROM %i as events INNER JOIN $wpdb->posts as posts ON events.post_id = posts.ID WHERE posts.post_author NOT IN (SELECT ID FROM %i)",
                    $events->tableName,
                    $events->tableName,
                    $wpdb->users
                ],
                [],
                'events'
            );

            /**
             * Flush db cache
             */
            if(wp_cache_supports( 'flush_group' )){
                wp_cache_flush_group('events');
            }else{
                wp_cache_flush();
            }

            return "Succesfully deleted $wpdb->rows_affected orphan events. ";
        }

        return '';
    }
}
