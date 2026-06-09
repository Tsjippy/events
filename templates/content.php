<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

/**
 * The content of an event.
 **/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


$events        = new DisplayEvents();
$event        = $events->retrieveSingleEvent(get_the_ID());
if (!empty($event->only_for) && $event->only_for != wp_get_current_user()->ID) {
?>
    <div class='error'>
        This event is not ment for you to see, sorry.
    </div>

<?php
    return;
}


?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="cat-card inside-article">
        <?php do_action('tsjippy_before_content'); ?>
        <div class='entry-content'>
            <div class="description event">
                <?php
                displayEventCategories();

                the_content();

                wp_link_pages(
                    array(
                        'before' => '<div class="page-links">Pages:',
                        'after'  => '</div>',
                    )
                );
                ?>
            </div>

            <?php
            displayEventMeta();
            ?>
        </div>
    </div>
</article>

<?php
function displayEventCategories()
{
    $baseUrl    = TSJIPPY\pathToUrl(PLUGINPATH . 'pictures', __DIR__);

    $categories = wp_get_post_terms(
        get_the_ID(),
        'events',
        array(
            'orderby'   => 'name',
            'order'     => 'ASC',
            'fields'    => 'id=>name'
        )
    );

    if (!empty($categories)) {
?>
        <span class='category eventmeta'>
            <img src='<?php echo esc_url($baseUrl); ?>/event_category.png' alt='category' loading='lazy' class='event-icon'>
            <?php

            //First loop over the cat to see if any parent cat needs to be removed
            foreach ($categories as $id => $category) {
                //Get the child categories of this category
                $children = get_term_children($id, 'events');

                //Loop over the children to see if one of them is also in he cat array
                foreach ($children as $child) {
                    if (isset($categories[$child])) {
                        unset($categories[$id]);
                        break;
                    }
                }
            }

            //now loop over the array to print the categories
            $lastKey     = array_key_last($categories);
            foreach ($categories as $id => $category) {
                //Only show the category if all of its subcats are not there
                $url         = get_term_link($id);
                $category     = ucfirst($category);
            ?>
                <a href='<?php echo esc_url($url); ?>'>
                    <?php echo esc_html($category); ?>
                </a>
            <?php
                if ($id != $lastKey) {
                    echo ', ';
                }
            }
            ?>
        </span>
    <?php
    }
}

function displayEventMeta()
{
    global $events;
    global $event;

    if (!$event) {
        return;
    }

    $date        = $events->getDate($event);
    $time        = $events->getTime($event);
    $meta        = get_post_meta($event->ID, 'tsjippy_eventdetails', true);
    if (!is_array($meta)) {
        if (!empty($meta)) {
            $meta    = (array)json_decode($meta, true);
        } else {
            $meta    = [];
        }
    }
    $baseUrl    = TSJIPPY\pathToUrl(PLUGINPATH . 'pictures');

    ?>
    <div class='event metas' style='margin-top:10px;'>
        <div class="event-meta">
            <div class="single-event-date">
                <img src='<?php echo esc_url($baseUrl); ?>/date.png' alt='date' loading='lazy' class='event-icon'>
                <h4>DATE</h4>
                <dl>
                    <dd>
                        <?php
                        echo esc_html($date);
                        ?>
                    </dd>
                </dl>
            </div>
            <div class="event-time">
                <img src='<?php echo esc_url($baseUrl); ?>/time_red.png' alt='time' loading='lazy' class='event-icon'>
                <h4 class="time">TIME</h4>
                <dl>
                    <dd>
                        <?php
                        echo esc_html($time);
                        ?>
                    </dd>
                </dl>
            </div>

            <?php
            if (!empty($meta['repeat']['type'])) {
            ?>
                <div class="event-repeat">
                    <img src='<?php echo esc_url($baseUrl); ?>/repeat_small.png' alt='repeat' loading='lazy' class='event-icon'>
                    <h4 class="repeat">REPEATS</h4>
                    <dl>
                        <dd>
                            <?php
                            $type    = $meta['repeat']['type'];
                            if ($meta['repeat']['type'] == 'custom_days') {
                                $type    = '';
                                foreach ($meta['repeat']['includedates'] as $date) {
                                    $type    .= gmdate('j F Y', strtotime($date)) . '<br>';
                                }
                            }
                            echo esc_html(ucfirst($type));

                            if (!empty($meta['repeat']['end_date'])) {
                                echo esc_html(" until " . gmdate('j F Y', strtotime($meta['repeat']['end_date'])));
                            }
                            if (!empty($meta['repeat']['amount'])) {
                                $repeatAmount = $meta['repeat']['amount'];
                                if ($repeatAmount != 90) {
                                    echo esc_html(" for $repeatAmount times");
                                }
                            }
                            ?>
                        </dd>
                    </dl>
                </div>
            <?php
            }
            if (!empty($event->location)) {
            ?>
                <div class="event-location">
                    <img src='<?php echo esc_url($baseUrl); ?>/location_red.png' alt='location' loading='lazy' class='event-icon'>
                    <h4>LOCATION</h4>
                    <div class='location-details'>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $events->getLocationDetail($event);
                        ?>
                    </div>
                </div>
            <?php
            }
            if (!empty($event->organizer)) {
            ?>
                <div class="event-organizer">
                    <img src='<?php echo esc_url($baseUrl); ?>/organizer.png' alt='organizer' loading='lazy' class='event-icon'>
                    <h4>ORGANIZER</h4>
                    <div class='author-details''>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $events->getAuthorDetail($event);
                        ?>
                    </div>
                </div>
            <?php
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $events->eventExportHtml($event);
            ?>
        </div>
    </div>
    <?php
}
