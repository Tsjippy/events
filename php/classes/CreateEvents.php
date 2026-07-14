<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class CreateEvents extends Events
{
    public array $eventData;
    public array $startDates;
    public \WP_User $partner;


    /**
     * Retrieves the weeknumber from a certain date
     * @param      int  $date        a date in epoch
     *
     * @return    int                the weeknumber
     */
    protected function yearWeek($date)
    {
        return intval(gmdate('W', $date));
    }

    /**
     * Retrieves the weeknumber from a certain date
     * @param      int  $date        a date in epoch
     *
     * @return    int                the weeknumber
     */
    protected function monthWeek($date)
    {
        $firstDayOfMonth    = strtotime(gmdate('Y-m-01', $date));
        return $this->yearWeek($firstDayOfMonth);
    }

    /**
     * Creates events in the db
     */
    public function createEvents()
    {
        $baseStartDateStr = $this->eventData['startdate'];
        $baseStartDate    = strtotime($baseStartDateStr);

        // Startdate is in the past
        if ($baseStartDate < strtotime(gmdate('Y-m-d', time()))) {
            // in the past and not repeated
            if (empty($this->eventData['isrepeated'])) {
                return new \WP_Error('events', "Date cannot be in the past: {$this->eventData['startdate']}");
            }
        }

        $this->startDates  = [$baseStartDateStr => 1];
        if (!empty($this->eventData['isrepeated'])) {
            $baseStartDate = $this->createRepeatedEvents($baseStartDate);
        }

        $baseEndDate       = $this->eventData['enddate'];
        $dayDiff           = ((new \DateTime($baseStartDateStr))->diff((new \DateTime($baseEndDate))))->d;

        foreach ($this->startDates as $startDate) {
            $endDate    = gmdate('Y-m-d', strtotime("+{$dayDiff} day", strtotime($startDate)));
            $this->maybeCreateRow($startDate);

            $args    = [
                'end_date' => $endDate
            ];

            // only add the data where there is a column for it
            foreach (['id', 'post_id', 'start_time', 'end_time', 'location', 'organizer', 'location_id', 'organizer_id', 'atendees', 'only_for'] as $column) {
                if (isset($this->eventData[$column])) {
                    $args[$column] = $this->eventData[$column];
                }
            }

            //Update the database
            $result = TSJIPPY\updateDbValue(
                $this->tableName,
                $args,
                array(
                    'post_id'      => $this->postId,
                    'start_date'   => $startDate
                ),
                [],
                [
                    '%d',
                    '%s'
                ],
                'events'
            );

            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Calculates the start date for a repeated event
     * @param      array  $repeatParam        The repeat parameters
     * @param      int    $baseStartDate    The base start date in epoch
     * @param      int    $index            The index of the repetition
     *
     * @return    int|false                The calculated start date in epoch or false if it cannot be calculated
     */
    protected function calculateStartDate($repeatParam, $baseStartDate, $index)
    {
        $weekDays    = [];
        if (!empty($repeatParam['weekdays'])) {
            $weekDays        = (array)$repeatParam['weekdays'];
        }

        $weeks    = [];
        if (!empty($repeatParam['weeks'])) {
            $weeks            = (array)$repeatParam['weeks'];
        }

        $startDate  = '';

        switch ($repeatParam['type']) {
            case 'daily':
                $startDate        = strtotime("+{$index} day", $baseStartDate);
                if (!empty($weekDays) && !isset($startDate[gmdate('w')], $weekDays)) {
                    return false;
                }
                break;
            case 'weekly':
                $startDate        = strtotime("+{$index} week", $baseStartDate);
                $monthWeek        = $this->monthWeek($startDate);

                $firstWeek        = gmdate('m', $startDate) != gmdate('m', strtotime('-1 week', $startDate));
                if ($firstWeek && isset($weeks['First'])) {
                    $monthWeek    = 'First';
                }

                $lastWeek        = gmdate('m', $startDate) != gmdate('m', strtotime('+1 week', $startDate));
                if ($lastWeek && isset($weeks['Last'])) {
                    $monthWeek    = 'Last';
                }
                if (!isset($weeks[$monthWeek])) {
                    return false;
                }
                break;
            case 'monthly' || 'yearly':
                // Get the next month
                if ($repeatParam['type'] == 'yearly') {
                    $startDate            = strtotime("+{$index} year", $baseStartDate);
                    $recurrenceString    = "year";
                } else {
                    $startDate            = strtotime("first day of +{$index} month", $baseStartDate);
                    $recurrenceString    = "month";
                }

                // same day number
                if (!empty($repeatParam['datetype'])) {
                    if ($repeatParam['datetype'] == 'samedate') {
                        // The new month does not have this date
                        if (gmdate('t', $startDate) < gmdate('d', $baseStartDate)) {
                            return false;
                        }
                        $day        = gmdate('d', $baseStartDate) - 1;
                        $startDate    = strtotime("+$day days", $startDate);
                    }
                    // Same week and day i.e. first friday
                    elseif ($repeatParam['datetype'] == 'patterned') {
                        // The weeknumber of the first week of the month
                        $firstWeek    = gmdate('W', strtotime("first day of 0 $recurrenceString", $baseStartDate));

                        // The weeknumber of this week in the month
                        $targetWeek    = TSJIPPY\numberToWords(gmdate("W", $baseStartDate) - $firstWeek + 1);

                        $dayName    = gmdate('l', $baseStartDate);

                        $startDate    = strtotime("$targetWeek $dayName of +{$index} $recurrenceString", $baseStartDate);
                    }
                    // last day of the month
                    elseif ($repeatParam['datetype'] == 'lastday') {
                        $startDate    = strtotime("last day +$index month", $baseStartDate);
                    }
                    // Same last day i.e. last Friday
                    else {
                        $dayName    = gmdate('l', $baseStartDate);
                        $startDate    = strtotime("last $dayName of +{$index} $recurrenceString", $baseStartDate);
                    }
                }

                break;
            case 'custom_days':
                break;
            default:
                $startDate    = strtotime("+{$index} year", $baseStartDate);
                break;
        }

        return $startDate;
    }

    /**
     * Creates repeated events based on the repeat parameters
     * @param      int  $baseStartDate    The base start date in epoch
     *
     * @return    int|false                The base start date in epoch or false if it cannot be calculated
     */
    protected function createRepeatedEvents($baseStartDate)
    {
        //first remove any existing events for this post
        $this->removeDbRows();

        //then create the new ones
        $repeatParam = $this->eventData['repeat'];
        $interval    = max(1, (int)$repeatParam['interval']);
        $amount      = 200; // no more than 200 events to not overload the db

        if ($repeatParam['type'] == 'custom_days') {
            foreach ($repeatParam['includedates'] as $date) {
                // not yet included and not in the past
                if (!isset($this->startDates[$date]) && $date > gmdate('Y-m-d')) {
                    $this->startDates[ $date ] = 1;
                }
            }

            return;
        }

        // Startdate is in the past, adjust
        $type        = rtrim($repeatParam['type'], 'ly');
        if ($type == 'dai') {
            $type    = 'day';
        }
        while ($baseStartDate < strtotime(gmdate('Y-m-d'))) {
            if(!empty($type)){
                // Add one day/week/month/year
                $baseStartDate    = strtotime("+1 $type", $baseStartDate);
            }elseif($repeatParam['datetype'] == 'patterned'){
                $baseStartDate    = strtotime($repeatParam['weeks'][0]." ".$repeatParam['weekdays'][0]." ". "of +". $repeatParam['months'][0] ." months", $baseStartDate);
            }else{
                return;
            }

            //re-adjust the start_date string
            $this->startDates[gmdate('Y-m-d', $baseStartDate)]  = 1;
        }

        // Calculate amount of repititions
        $repeatStop    = $repeatParam['stop'];
        if ($repeatParam['stop'] == 'after' && !empty($repeatParam['amount']) && is_numeric($amount)) {
            $amount = intval($repeatParam['amount']) - 1;    // The first event is already created
        }

        // calculate repetition end date
        if ($repeatStop == 'date') {
            $repEnddate  = strtotime($repeatParam['end_date']);
        } else {
            $repEnddate  = strtotime("+5 year", $baseStartDate);
        }

        $excludeDates    = [];
        if (isset($repeatParam['excludedates'])) {
            $excludeDates = (array)$repeatParam['excludedates'];
        }

        $includeDates    = [];
        if (isset($repeatParam['includedates'])) {
            $includeDates = (array)$repeatParam['includedates'];
        }

        $startDate        = $baseStartDate;
        $i                = 1;
        while ($startDate < $repEnddate && $amount > 0) {
            $startDate    = $this->calculateStartDate($repeatParam, $baseStartDate, $i);

            if ($repeatParam['type'] == 'custom_days') {
                $startDateStr    = $includeDates[$i];
            } elseif ($startDate) {
                $startDateStr    = gmdate('Y-m-d', $startDate);
            }

            if (!$startDate || isset($excludeDates[$startDateStr])) {
                $i                = $i + $interval;
                continue;
            }

            if (
                !isset($includeDates[$startDateStr]) &&        //we should not exclude this date
                $startDate < $repEnddate                &&        //falls within the limits
                (!is_numeric($amount) || $amount > 0)             //We have not exeeded the amount
            ) {
                $this->startDates[$startDateStr] = 1;
            }

            $i      = $i + $interval;
            $amount = $amount - 1;
        }

        return $baseStartDate;
    }

    /**
     * Deletes old celebration events
     * @param      int      $postId        WP_Post if
     * @param    string    $date        date string
     * @param    int        $userId        WP_User id
     * @param    string    $type        the anniverasry type
     * @param    string    $title        the event title
     */
    protected function deleteOldCelEvent($postId, $date, $userId, $type, $title)
    {
        if (!is_numeric($postId)) {
            return false;
        }

        $existingEvent    = $this->retrieveSingleEvent($postId);
        if (
            gmdate('-m-d', strtotime($existingEvent->start_date)) == gmdate('-m-d', strtotime($date)) &&
            $title == $existingEvent->post_title
        ) {
            return false; //nothing changed
        } else {
            delete_user_meta($userId, $type . '_event_id');

            $this->removeDbRows('', true);
        }
    }

    /**
     * Deletes creates new celebration events
     * @param    string        $type        the anniverasry type
     * @param    int|object    $userId        WP_User id or WP_User object
     * @param    string        $metaKey    the meta key key
     * @param    string        $metaValue    the meta value, should be a date string
     */
    public function createCelebrationEvent($type, $user, $oldValue, $newValue)
    {
        $family            = new TSJIPPY\FAMILY\Family();

        if (is_numeric($user)) {
            $user = get_userdata($user);
        }

        if (!$user) {
            return new WP_Error('invalid user', 'Invalid User or User ID supplied');
        }

        if ($type != 'birthday' && $family->isChild($user->ID)) {
            //do not create annversaries for children
            return;
        }

        $eventIdMetaKey        = 'tsjippy_'.$type . '_event_id';

        if ($newValue == $oldValue) {
            // nothing to work with or no update
            return;
        } elseif (empty($newValue)) {
            $newValue = $oldValue;
        } else {
            // check if just the year was updated
            $oldTime    = strtotime($oldValue);
            $newTime    = strtotime($newValue);

            if (
                gmdate('Y', $oldTime) != gmdate('Y', $newTime) &&
                gmdate('m-d', $oldTime) == gmdate('m-d', $newTime)
            ) {
                // no need to create new events, just update the meta value
                $postId    = get_user_meta($user->ID, $eventIdMetaKey, true);

                update_post_meta($postId, 'tsjippy_celebrationdate', $newValue);
                return;
            }
        }

        $title                = ucfirst($type) . ' ' . $user->display_name;
        $this->partner        = $family->getPartner($user->ID, true);
        if ($this->partner) {
            $title            = ucfirst($type) . " {$user->first_name} & {$this->partner->first_name} {$user->last_name}";
        }

        //get old post
        $this->postId    = get_user_meta($user->ID, $eventIdMetaKey, true);
        $this->deleteOldCelEvent($this->postId, $oldValue, $user->ID, $type, $title);

        // Create the post
        $this->createCelebrationPost($user, $newValue, $title, $type, $eventIdMetaKey);

        $this->createEvents();
    }

    /**
     * Creates a celebration post for the user
     * @param    object        $user        WP_User object
     * @param    string        $metaValue    the meta value, should be a date string
     * @param    string        $title        the event title
     * @param    string        $type        the anniverasry type
     * @param    string        $eventIdMetaKey    the meta key key
     */
    protected function createCelebrationPost($user, $metaValue, $title, $type, $eventIdMetaKey)
    {
        //Get the upcoming celebration date
        $start_date                            = gmdate(gmdate('Y') . '-m-d', strtotime($metaValue));

        $this->eventData['start_date']         = $start_date;
        $this->eventData['end_date']           = $start_date;
        $this->eventData['location']           = '';
        $this->eventData['organizer']          = $user->display_name;
        $this->eventData['organizer-id']       = $user->ID;
        $this->eventData['start_time']         = '00:00';
        $this->eventData['end_time']           = '23:59';
        $this->eventData['allday']             = true;
        $this->eventData['isrepeated']         = 'Yes';
        $this->eventData['repeat']['interval'] = 1;
        $this->eventData['repeat']['amount']   = 90;
        $this->eventData['repeat']['stop']     = 'never';
        $this->eventData['repeat']['type']     = 'yearly';

        $post = array(
            'post_type'     => 'event',
            'post_title'    => $title,
            'post_content'  => $title,
            'post_status'   => 'publish',
            'post_author'   => $user->ID
        );

        $this->postId     = wp_insert_post($post, true, false);
        update_metadata('post', $this->postId, 'tsjippy_eventdetails', json_encode($this->eventData));
        update_metadata('post', $this->postId, 'tsjippy_celebrationdate', $metaValue);

        // Set the categories
        if ($type == 'birthday') {
            $catName = 'birthday';
        } else {
            $catName = 'anniversary';
        }
        $termId = get_term_by('slug', $catName, 'events')->term_id;
        if (empty($termId)) {
            $termId = wp_insert_term(ucfirst($catName), 'events')['term_id'];
        }

        wp_set_object_terms($this->postId, $termId, 'events');

        // Set the featured image
        $pictureIds    = SETTINGS['picture-ids'] ?? [];

        if ($type == 'birthday') {
            $pictureId = $pictureIds['birthday_image'] ?? -1;
        } else {
            $pictureId = $pictureIds['anniversary_image'] ?? -1;
        }

        if ($pictureId != -1) {
            set_post_thumbnail($this->postId, $pictureId);
        }

        //Store the post id for the user
        update_user_meta($user->ID, $eventIdMetaKey, $this->postId);
    }

    /**
     * Stores all event details in the db, removes any existing events, and creates new ones.
     * @param   int|\WP_post  $post        The id of a post or the post itself
     * @param   array         $event       The event data    
     */
    public function storeEventMeta($post, $event)
    {
        if (is_numeric($post)) {
            $post    = get_post($post);
        }

        $this->postId                   = $post->ID;

        $event['allday']                = $event['allday'] ?? '';
        $event['start_date']            = $event['start_date'] ?? '';
        $event['start_time']            = $event['start_time'] ?? '';
        $event['end_date']              = $event['end_date'] ?? '';
        $event['end_time']              = $event['end_time'] ?? '';

        if (empty($event['start_date']) || empty($event['end_date'])) {
            return;
        }

        $event['repeat']['type']        = $event['repeat']['type'] ?? '';
        $event['repeat']['interval']    = $event['repeat']['interval'] ?? '';
        $event['repeat']['stop']        = $event['repeat']['stop'] ?? '';
        $event['repeat']['end_date']    = $event['repeat']['end_date'] ?? '';
        $event['repeat']['amount']      = $event['repeat']['amount'] ?? '';
        $event['location']              = $event['location'] ?? '';
        $event['location_id']           = $event['location-id'] ?? '';
        $event['organizer']             = $event['organizer'] ?? '';
        $event['organizer-id']          = $event['organizer-id'] ?? '';

        //check if anything has changed
        $oldMeta    = get_post_meta($this->postId, 'tsjippy_eventdetails', true);
        if (!is_array($oldMeta)) {
            if (!empty($oldMeta)) {
                $oldMeta    = (array)json_decode($oldMeta, true);
            } else {
                $oldMeta    = [];
            }
        }
        if ($oldMeta != $event) {
            //store meta in db
            update_metadata('post', $this->postId, 'tsjippy_eventdetails', json_encode($event));
        }

        /**
         * events are created using the
         * add_action('added_post_meta', __NAMESPACE__ . '\createEvents', 10, 4);
         * add_action('updated_postmeta', __NAMESPACE__ . '\createEvents', 10, 4);
         * hooks
         */
    }

    /**
     * Creatres a new event in db if it does not exist already
     * @param      string  $startDate        The start_date of the event
     */
    protected function maybeCreateRow($startDate)
    {
        //check if form row already exists
        $event  = TSJIPPY\getFromDb(
            "get_event_for_post_{$this->postId}_startdate_$startDate",
            "events",
            "SELECT * FROM %i WHERE `post_id` = %d AND start_date = %s",
            $this->tableName,
            $this->postId,
            $startDate
        );

        if (empty($event)) {
            TSJIPPY\insertInDb(
                $this->tableName,
                array(
                    'post_id'     => $this->postId,
                    'start_date'  => $startDate
                ),
                [
                    '%d',
                    '%s'
                ],
                'events'
            );
        }
    }
}
