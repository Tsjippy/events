<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-payer-after-message', __NAMESPACE__ . '\afterBotPrayer'); 
function afterBotPrayer($args)
{
    $family    = new TSJIPPY\FAMILY\Family();

    // calendar events
    $events        = new DisplayEvents();

    // add normal events
    $events->retrieveEvents(gmdate('Y-m-d'), gmdate('Y-m-d'));
    foreach ($events->events as $event) {
        $startYear    = get_post_meta($event->ID, 'tsjippy_celebrationdate', true);

        //only add events which are not a celebration and start today after curent time
        if (empty($startYear) && $event->start_date == gmdate('Y-m-d') && $event->start_time > gmdate('H:i', current_time('U'))) {
            $args['message']    .= "\n\n" . $event->post_title . ' starts today at ' . $event->start_time;
            if (!empty($event->location)) {
                $args['message']    .= "\nIt takes place at $event->location";
            }
            $args['urls'][]        = get_permalink($event->ID);
        }
    }

    // add aniversaries
    $anniversaryMessages = getAnniversaries();

    //If there are anniversaries
    if (!empty($anniversaryMessages)) {
        $args['message'] .= "\n\nToday is the ";

        $messageString    = '';

        //Loop over the anniversary_messages
        foreach ($anniversaryMessages as $userId => $msg) {
            $msg            = html_entity_decode($msg);

            $userdata        = get_userdata($userId);

            // user does not exist
            if (!$userdata) {
                continue;
            }

            // Add to the message
            if (!empty($messageString)) {
                $messageString .= " and the ";
            }

            $coupleString   = getCoupleString($userdata);

            $msg            = replaceCoupleString($msg, "of $coupleString", $userdata);

            $msg            = str_replace($userdata->display_name, "of {$userdata->display_name}", $msg);

            $messageString .= $msg;

            // User page url
            $url            = get_author_posts_url($userId);
            if ($url) {
                $args['urls'][]    = str_replace('https://', '', $url);
            }

            // add appropriate picture
            if (str_contains($msg, '&')) {
                $family    = new TSJIPPY\FAMILY\Family();
                $picture    = $family->getFamilyMeta($userId, 'family_picture', true);

                if (is_numeric($picture)) {
                    $args['pictures'][] = get_attached_file($picture);
                }
            } else {
                $profilePicture    = get_user_meta($userId, 'tsjippy_profile_picture', true);
                if (is_array($profilePicture) && isset($profilePicture[0])) {
                    $args['pictures'][] = get_attached_file($profilePicture[0]);
                } elseif (is_numeric($profilePicture)) {
                    $args['pictures'][] = get_attached_file($profilePicture);
                }
            }
        }
        $args['message'] .= $messageString . ' . ';
    }

    return $args;
}
