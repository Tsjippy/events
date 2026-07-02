<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get all the anniversary events
 *
 * @return    array    all messages
 */
function getAnniversaries()
{
    $messages = [];

    $events = new DisplayEvents();
    $events->retrieveEvents(gmdate('Y-m-d'), gmdate('Y-m-d'));

    foreach ($events->events as $event) {
        $startYear    = get_post_meta($event->ID, 'tsjippy_celebrationdate', true);

        if (is_array($startYear)) {
            array_filter($startYear);

            $startYear    = array_values($startYear)[0];
        }

        if (!empty($startYear) && $startYear != gmdate('Y-m-d')) {
            $title        = $event->post_title;
            $age        = TSJIPPY\getAge($startYear);

            if ($age == "zero") {
                continue;
            }

            $privacy    = get_user_meta($event->post_author, 'tsjippy_privacy_preference');

            if (substr($title, 0, 8) == 'Birthday' && in_array('hide_age', $privacy)) {
                $age    = '';
            }
            if (substr($title, 0, 3) != 'SIM') {
                $title    = lcfirst($title);
            }

            //there happen to be more celeberations on the same day for one person
            if (isset($messages[$event->post_author])) {
                $name    = get_userdata($event->post_author)->display_name;
                // remove the name of the previous text
                if (str_contains($title, $name)) {
                    $messages[$event->post_author]    = trim(str_replace($name, '', $messages[$event->post_author]));
                }
                $messages[$event->post_author]    .= ' and the ';
            } else {
                $messages[$event->post_author]    = '';
            }
            $messages[$event->post_author] .= trim("$age $title");
        }
    }

    return $messages;
}

add_action('delete_user', __NAMESPACE__ . '\deleteUser');
/**
 * Deletes a user and their associated events
 *
 * @param    int    $userId    The user ID to delete
 */
function deleteUser($userId)
{
    $events = new CreateEvents();

    //Remove birthday events
    $birthdayPostId = get_user_meta($userId, 'tsjippy_birthday_event_id', true);
    if (is_numeric($birthdayPostId)) {
        $events->removeDbRows($birthdayPostId);
    }

    $anniversaryId    = get_user_meta($userId, 'tsjippy_'.TSJIPPY\SITENAME . ' anniversary_event_id', true);
    if (is_numeric($anniversaryId)) {
        $events->removeDbRows($anniversaryId);
    }
}

/**
 * Get the couple string of a certain user
 *
 * @param    int|object                $user        the user or user id
 * @param    int|object|string        $partner    the user object of the users partner. default empty
 *
 * @return    string                    The couple string
 */
function getCoupleString($user, $partner = '')
{
    if (is_numeric($user)) {
        $user    = get_userdata($user);
    }

    $family     = new TSJIPPY\FAMILY\Family();
    $lastName   = $user->last_name;
    $familyName = $family->getFamilyMeta($user, 'family_name', true);
    if (!empty($familyName)) {
        $lastName    = $familyName;
    }

    if (empty($partner)) {
        $partner        = $family->getPartner($user->ID, true);

        if (!$partner) {
            return "$user->first_name $lastName";
        }
    }

    return "$user->first_name & $partner->first_name $lastName";
}

/**
 * Replaces the couple string in a message
 *
 * @param    string    $string        The original message
 * @param    string    $replaceString    The string to replace with
 * @param    int|object                $user        The user or user id
 * @param    int|object|string        $partner    The user object of the users partner. default empty
 *
 * @return    string                    The modified message
 */
function replaceCoupleString($string, $replaceString, $user, $partner = '')
{
    $family    = new TSJIPPY\FAMILY\Family();
    if (is_numeric($user)) {
        $user    = get_userdata($user);
    }

    if (empty($partner)) {
        $partner        = $family->getPartner($user->ID, true);

        if (!$partner) {
            return $string;
        }
    }

    //Search for first names and last names
    $pattern    = "/((\b$user->first_name\b)|(\b$partner->first_name\b)).*((\b$partner->first_name\b)|(\b$user->first_name\b)).*((\b$partner->last_name\b)|(\b$user->last_name\b))/i";

    return preg_replace($pattern, $replaceString, $string);
}

/**
 *
 * Adds html to the frontpage prayer message
 *
 * @return   string|false     Birthday and arrining usrs html or false if there are no events
 *
 **/
add_filter('tsjippy-prayer-message', __NAMESPACE__ . '\anniversaryMessages');
/**
 *
 * Get the html birthday message
 * 
 * @param   string    $html    The html to add to  
 *
 * @return   string|false     Anniversary html
 *
 */
function anniversaryMessages($html)
{
    $family              = new TSJIPPY\FAMILY\Family();
    $currentUser         = wp_get_current_user();
    $anniversaryMessages = getAnniversaries();

    if (empty($anniversaryMessages)) {
        return $html;
    }

    $messageString    = '';

    //Loop over the anniversary_messages
    foreach ($anniversaryMessages as $userId => $message) {
        if (!empty($messageString)) {
            $messageString .= " and the ";
        }

        $message    = str_replace('&amp;', '&', $message);

        $partner    = $family->getPartner($userId, true);

        $userdata    = get_userdata($userId);
        if (!$userdata) {
            continue;
        }

        $addImage    = true;

        if ($userId  == $currentUser->ID) {
            $coupleLink   = "of you and your spouse my dear $currentUser->first_name!<br>";
            $link         = str_replace($currentUser->display_name, "of you my dear $currentUser->first_name!<br>", $message);

            $addImage     = false;
        } else {
            //Get the url of the user page
            $url          = get_author_posts_url($userId);

            $coupleString = getCoupleString($userdata, $partner);
            $coupleLink   = "of <a href=\"$url\">$coupleString</a>";
            $link         = "of <a href=\"$url\">{$userdata->display_name}</a>";
        }

        if ($partner) {
            $newMessage   = replaceCoupleString($message, $coupleLink, $userdata, $partner);

            // Add family picture if needed
            if ($newMessage != $message && $addImage) {
                $message  = $newMessage;
                $family   = new TSJIPPY\FAMILY\Family();
                $picture  = $family->getFamilyMeta($userId, 'family_picture', true);

                if (is_numeric($picture)) {
                    $url        = wp_get_attachment_url($picture);
                    $picture    = wp_get_attachment_image($picture, 'avatar', false, 'style=border-radius: 50%;');
                    $message    .= "<a href='$url'>$picture</a>";
                }

                $addImage    = false;
            }
        }
        $message    = str_replace($userdata->display_name, $link, $message);

        if ($addImage) {
            $profilePicture    = get_user_meta($userId, 'tsjippy_profile_picture', true);
            if (isset($profilePicture[0])) {
                $pictureUrl    = wp_get_attachment_url($profilePicture[0]);
                $pictureHtml   = wp_get_attachment_image($profilePicture[0], 'avatar', false, 'style=border-radius: 50%;');
                $message       .= "<a href='$pictureUrl'>$pictureHtml</a>";
            }
        }

        $messageString    .= $message;
    }

    if (empty($messageString)) {
        return '';
    }

    $html .= '<div name="anniversaries" style="font-size: 18px;">';
    $html .= '<h3>Celebrations</h3>';
    $html .= '<p>';
    $html .= "Today is the $messageString";
    $html .= ' .</p>';
    $html .= '</div>';

    return $html;
}
