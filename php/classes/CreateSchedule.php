<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class CreateSchedule extends Schedules
{
    public string $date;
    public string $startTime;
    public string $endTime;
    public string $location;
    public int $hostId;
    public int $scheduleId;
    public string $name;
    public string $title;
    public bool $sessionAtendeesUpdated;

    public function __construct()
    {
        parent::__construct();

        $this->sessionAtendeesUpdated    = false;
    }

    /**
     * Add a new event to the db
     *
     * @param    object    $event           The event
     * @param    array     $settings        The event settings
     *
     * @return     array                    Rows html
     */
    protected function addEventToDb($event, $settings)
    {
        global $wpdb;

        $wpdb->insert(
            $this->events->tableName,
            $event
        );

        if (!empty($wpdb->last_error)) {
            return new WP_Error('schedules', $wpdb->last_error);
        }

        $eventId   = $wpdb->insert_id;

        // Create event warning
        if (isset($settings['reminders'])) {
            foreach ($settings['reminders'] as $minutes) {
                $start    = new \DateTime($event['start_date'] . ' ' . $event['start_time'], new \DateTimeZone(wp_timezone_string()));

                //Warn minutes in advance
                $start    = $start->getTimestamp() - $minutes * MINUTE_IN_SECONDS;

                wp_schedule_single_event($start, 'tsjippy-events-send-event-reminder', [$eventId]);
            }
        }

        return $eventId;
    }

    /**
     * Add new events to the db when a new activity is scheduled
     *
     * @param    bool    $addHostPartner    Whether to add an event for the host partner as well. Default true
     * @param    bool    $addPartner        Whether to add an event for the schedule target partner as well. Default true
     */
    protected function addScheduleEvents($addHostPartner = true, $addPartner = true, $settings = [])
    {
        $family                           = new TSJIPPY\FAMILY\Family();
        $event                            = [];
        $event['start_date']              = $this->date;
        $event['start_time']              = $this->startTime;
        $event['end_date']                = $this->date;
        $event['end_time']                = $this->endTime;
        $event['location']                = $this->location;
        $event['organizer-id']            = $this->hostId;
        if (empty($settings['others'])) {
            $event['atendees']            = serialize([]);
        } else {
            $event['atendees']            = maybe_serialize($settings['others']);
        }

        $hostPartner                    = false;
        if (is_numeric($this->hostId)) {
            if ($addHostPartner) {
                $event['organizer']                = $family->getFamilyName($this->hostId, false, $hostPartner);
            } else {
                $event['organizer']                = get_userdata($this->hostId)->display_name;
            }
        } elseif (!empty($settings['host'])) {
            $event['organizer']                    = $settings['host'];
        }

        if ($addPartner) {
            $partnerId    = $family->getPartner($this->currentSchedule->target);
        } else {
            $partnerId    = false;
        }

        //clean title
        if (!empty($settings['subject'])) {
            $title    = TSJIPPY\sanitize($settings['subject']);
        }

        if (!empty($event['organizer']) && empty($this->defaultSubject)) {
            $ownTitle    = ucfirst($this->title) . " with {$event['organizer']}";
        } else {
            $ownTitle    = ucfirst($this->title);
        }

        if (str_contains(strtolower($ownTitle), 'at home')) {
            if (!empty($event['organizer'])) {
                $ownTitle    = ucfirst($this->title) . ' ' . $event['organizer'];
            }

            $event['location']    = 'Home';
            unset($event['organizer']);
        }

        //New events
        $eventArray        = [
            [
                'title'        => $ownTitle,
                'only_for'    => [$this->currentSchedule->target]
            ]
        ];

        if ($partnerId) {
            $eventArray[0]['only_for'][]    = $partnerId;
        }

        $title    = '';
        if (is_numeric($this->hostId)) {
            if (empty($this->defaultSubject)) {
                $titleString    = "Hosting {$this->name} for $title";
            } else {
                $titleString    = $title;
            }

            $eventArray[] =
                [
                    'title'        =>    $titleString,
                    'only_for'    => [$this->hostId, $hostPartner]
                ];
        }

        if (!empty($settings['others']) && is_array($settings['others'])) {
            foreach ($settings['others'] as $attendee) {
                $eventArray[] =
                    [
                        'title'        => "Attending $title with {$this->name}",
                        'only_for'    => [$attendee]
                    ];
            }
        }

        $events    = $this->createPostsAndEvents($eventArray, $event, $settings);
        extract($events, EXTR_OVERWRITE);

        // store the event and post ids in db
        global $wpdb;

        $wpdb->insert(
            $this->sessionTableName,
            [
                'schedule_id'    => $this->scheduleId,
                'post_ids'       => serialize($postIds),
                'event_ids'      => serialize($eventIds),
                'meal'           => $title == 'lunch' || $title == 'dinner'
            ]
        );

        // add to schedule
        if (!isset($this->currentSchedule->sessions[$this->date])) {
            $this->currentSchedule->sessions[$this->date]    = [];
        }
        $this->currentSchedule->sessions[$this->date][$this->startTime]    = $this->getSessionEvent($wpdb->insert_id);
    }

    /**
     * Creates posts and events from an array
     *
     * @param    array    $eventArray     Array containing a title and a only_for key
     * @param    object   $event          Object with the event details
     * @param    array     $settings      The event settings
     *
     * @return    array                   Array containing the created post and event ids
     */
    public function createPostsAndEvents($eventArray, $event, $settings)
    {
        $eventIds   = [];
        $postIds    = [];

        foreach ($eventArray as $a) {
            $post = array(
                'post_type'     => 'event',
                'post_title'    => $a['title'],
                'post_content'  => $a['title'],
                'post_status'   => "publish",
                'post_author'   => $this->hostId
            );

            $postId     = wp_insert_post($post, true, false);
            if (is_wp_error($postId)) {
                return $postId;
            }

            $postIds[]    = $postId;
            update_post_meta($postId, 'tsjippy_eventdetails', json_encode($event));
            update_post_meta($postId, 'tsjippy_only_for', $a['only_for']);
            update_post_meta($postId, 'tsjippy_reminders', $settings['reminders']);

            // setting the eventdetails meta value also creates the event. Remove it
            $events = new CreateEvents();
            $events->removeDbRows($postId);

            foreach ($a['only_for'] as $userId) {
                if (is_numeric($userId)) {
                    $event['only_for']    = $userId;
                    $event['post-id']    = $postId;
                    $eventId             = $this->addEventToDb($event, $settings);

                    if (is_wp_error($eventId)) {
                        return $eventId;
                    }

                    if (is_numeric($eventId)) {
                        $eventIds[]    = $eventId;
                    }
                }
            }
        }

        return [
            'eventIds'    => $eventIds,
            'postIds'    => $postIds
        ];
    }

    /**
     * Updates events in the db
     */
    protected function updateScheduleEvents($addHostPartner = true, $addPartner = true, $settings = [])
    {
        global $wpdb;
        $family        = new TSJIPPY\FAMILY\Family();

        $updated    = false;

        $hostPartner                    = false;
        if (is_numeric($this->hostId)) {
            if ($addHostPartner) {
                $organizer                = $family->getFamilyName($this->hostId, false, $hostPartner);
            } else {
                $organizer                = get_userdata($this->hostId)->display_name;
            }
        } elseif (!empty($settings['host'])) {
            $organizer                = $settings['host'];
        }

        if ($this->currentSession->events[0]->atendees != $settings['others']) {
            $this->updateSessionAtendees($settings);
            $updated                = true;
        }

        $args    = [];
        foreach ($this->currentSession->events as $event) {
            $event->atendees    = maybe_unserialize($event->atendees);

            if ($event->start_date != $this->date) {
                $args['start_date'] = $this->date;
                $args['end_date']   = $this->date;
                $updated            = true;
            }

            if ($event->start_time != $this->startTime) {
                $args['start_time'] = $this->startTime;
                $updated            = true;
            }

            if ($event->end_time != $this->endTime) {
                $args['end_time']   = $this->endTime;
                $updated            = true;
            }

            if ($event->location != $this->location) {
                $args['location']   = $this->location;
                $updated            = true;
            }

            if ($event->organizer != $organizer) {
                $args['organizer']  = $organizer;

                // update the post title
                wp_update_post(array(
                    'ID'           => $event->post_id,
                    'post_title'   => "{$this->title} with $organizer",
                ));

                $updated            = true;
            }

            if ($event->organizer - id != $this->hostId) {
                $args['organizer-id']  = $this->hostId;
                $updated               = true;
            }

            if (!isset($settings['others'])) {
                $settings['others']    = [];
            }

            if (maybe_unserialize($event->atendees) != $settings['others']) {
                $args['atendees']      = maybe_serialize($settings['others']);
                $updated               = true;
            }

            if (!$updated) {
                return new WP_Error('schedules', 'Nothing to update');
            }

            //Update the database
            $wpdb->update(
                $this->events->tableName,
                $args,
                array(
                    'id'        => $event->id
                ),
            );
        }

        // update the schedule
        if (!isset($this->currentSchedule->sessions[$this->date])) {
            $this->currentSchedule->sessions[$this->date]    = [];
        }
        $this->currentSchedule->sessions[$this->date][$this->startTime]    = $this->getSessionEvent($this->currentSession->id);
    }

    /**
     * Updates the session atendees and the posts and events related to that
     */
    public function updateSessionAtendees($settings)
    {
        global $wpdb;

        if ($this->sessionAtendeesUpdated) {
            return;
        }

        $this->sessionAtendeesUpdated    = true;
        $event                            = $this->currentSession->events[0];
        $event->atendees                = maybe_unserialize($event->atendees);
        $eventArray                        = [];

        // remove old events
        foreach ($this->currentSession->events as $i => $ev) {

            if (
                $ev->only_for != $this->currentSchedule->target     &&         // not an event for the target of the schedule
                $ev->only_for != $event->organizer - id                &&        // not organizer of the event
                !in_array($ev->only_for, $settings['others'])                // and not one of the atendees
            ) {
                // remove the event and all posts related to it
                $this->currentSession->event_ids        = array_diff($this->currentSession->event_ids, [$event->id]);
                foreach ($this->currentSession->posts as $index => $post) {
                    if ($ev->post_id == $post->ID) {
                        $this->currentSession->post_ids  = array_diff($this->currentSession->post_ids, [$post->ID]);

                        unset($this->currentSession->posts[$index]);
                    }
                }

                unset($this->currentSession->events[$i]);

                $this->events->removeDbRows($post->ID, true);
            }
        }

        // prepare the new events
        foreach ($settings['others'] as $atendee) {
            if (is_numeric($atendee) && !in_array($atendee, (array)$event->atendees)) {
                $eventArray[] = [
                    'title'        => "Attending {$this->title} with {$this->name}",
                    'only_for'    => [$atendee]
                ];
            }
        }

        // create new events
        if (!empty($eventArray)) {
            $arrayedEvent    = (array)$event;
            unset($arrayedEvent['id']);
            $ids    = $this->createPostsAndEvents($eventArray, $arrayedEvent, $settings);
            if (is_wp_error($ids)) {
                return $ids;
            }
            extract($ids, EXTR_OVERWRITE);

            $this->currentSession->post_ids        = array_merge($postIds, $this->currentSession->post_ids);
            $this->currentSession->event_ids    = array_merge($eventIds, $this->currentSession->event_ids);
        }

        // Update the session
        $wpdb->update(
            $this->sessionTableName,
            [
                'post_ids'        => serialize($this->currentSession->post_ids),
                'event_ids'        => serialize($this->currentSession->event_ids)
            ],
            [
                'id'        => $this->currentSession->id
            ],
        );
    }

    /**
     * Add a new schedule
     * 
     * @param array     $settings  settings for the schedule
     *
     * @return array    text, new schedules list in html
     */
    public function addSchedule($settings)
    {
        global $wpdb;

        $name        = TSJIPPY\sanitize($settings['target-name']);

        //check if schedule already exists
        if (
            empty($settings['update']) && 
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE `name` = %s",
                    $this->tableName,
                    $name
                )
            ) != null
        ) {
            return new WP_Error('schedule', "A schedule for $name already exists!");
        }

        $info        = TSJIPPY\sanitize($settings['schedule-info']);

        if (empty($settings['skiplunch'])) {
            $lunch    = true;
        } else {
            $lunch    = false;
        }

        if (empty($settings['skiporientation'])) {
            $orientation    = true;
        } else {
            $orientation    = false;
        }

        $startDateStr   = $settings['start_date'];
        $startDate      = strtotime($startDateStr);
        $endDateStr     = $settings['end_date'];
        $endDate        = strtotime($endDateStr);

        if ($orientation) {
            $startTime    = '08:00';
        } elseif ($lunch) {
            $startTime    = $this->lunchStartTime;
        } else {
            $startTime    = $this->dinerTime;
        }

        if ($startDate > $endDate) {
            return new \WP_Error('schedule', "Ending date cannot be before starting date");
        }

        if ($settings['fixedtimeslotsize'] == 'yes') {
            $fixedTimeslotSize    = true;
        } else {
            $fixedTimeslotSize    = false;
        }

        if (empty($settings['subject'])) {
            $subject    = '';
            $diner        = !isset($settings['skipdiner']);
        } else {
            $subject    = $settings['subject'];
            $lunch        = false;
            $diner        = false;
        }

        $arg    = array(
            'target'                => $settings['target-id'],
            'name'                  => $name,
            'info'                  => $info,
            'lunch'                 => $lunch,
            'diner'                 => $diner,
            'orientation'           => $orientation,
            'start_date'            => $startDateStr,
            'end_date'              => $endDateStr,
            'start_time'            => $startTime,
            'end_time'              => $this->dinerTime,
            'timeslot_size'         => $settings['timeslotsize'],
            'fixed_timeslot_size'   => $fixedTimeslotSize,
            'hide_names'            => isset($settings['hide_names']),
            'admin_roles'           => maybe_serialize($settings['admin-roles']),
            'view_roles'            => maybe_serialize($settings['view-roles']),
            'subject'               => $subject,
        );

        if (!empty($settings['update'])) {
            $wpdb->update(
                $this->tableName,
                $arg,
                array('id' => $settings['schedule-id'])
            );
            $action    = 'updated';
        } else {
            $wpdb->insert(
                $this->tableName,
                $arg
            );

            if ($wpdb->last_error !== '') {
                return new WP_Error('schedules', $wpdb->last_error);
            }

            $action    = 'added';
        }

        $this->getSchedules();
        return [
            'message'        => "Succesfully $action a schedule for $name",
            'html'            => $this->showschedules()
        ];
    }

    /**
     * Publishes a new schedule
     *
     * @return string    success message
     */
    public function publishSchedule($settings)
    {
        global $wpdb;
        $family        = new TSJIPPY\FAMILY\Family();

        $scheduleId    = $settings['schedule-id'];

        $family->updateFamilyMeta($settings['schedule-target'], 'schedule', $scheduleId);

        $wpdb->update(
            $this->tableName,
            array(
                'published' => true
            ),
            array(
                'id'        => $scheduleId
            )
        );

        return 'Succesfully published the schedule';
    }

    /**
     * Removes a given schedule
     *
     * @param int    $scheduleId    THe id of the schedule to remove
     *
     * @return string    success message
     */
    public function removeSchedule($scheduleId)
    {
        global $wpdb;

        $family    = new TSJIPPY\FAMILY\Family();

        if (!is_numeric($scheduleId)) {
            return new WP_Error('schedules', 'Schedule id should be numeric');
        }

        $schedule    = $this->schedules[$scheduleId];

        //Remove the schedule from the user meta
        if (is_numeric($schedule->target)) {
            $family->removeFamilyMeta($schedule->target, 'schedule');
        }

        //Delete all the posts and events of this schedule
        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE schedule_id=%d",
                $this->sessionTableName,
                $scheduleId
            )
        );
        foreach ($sessions as $session) {
            $this->removeSession($session);
        }

        //Delete the schedule
        $wpdb->delete(
            $this->tableName,
            ['id' => $scheduleId],
            ['%d'],
        );

        //Remove the schedule from the schedules array
        return 'Succesfully removed the schedule';
    }

    /**
     * Removes a session and all events and posts attached to it
     *
     * @param object|int    $session        A session or session id of a session, if not given the value of $this->currentSession will be used
     *
     */
    protected function removeSession($session = [])
    {
        global $wpdb;

        if (empty($session)) {
            $session    = $this->currentSession;
        }
        if (is_numeric($session)) {
            $session    = $this->getSessionEvent($session);
        }

        foreach (maybe_unserialize($session->post_ids) as $postId) {
            //delete events
            $this->events->removeDbRows($postId, true);
        }

        // Delete the session
        $wpdb->delete(
            $this->sessionTableName,
            ['id' => $session->id],
            ['%d'],
        );
    }

    /**
     * Add a new host for a session
     *
     * @param string $date     The date of the session to add a host for
     * @param array  $settings Array of all settings needed
     *
     * @return array           Success message and new cell html
     */
    public function addHost($date, $settings)
    {
        $family             = new TSJIPPY\FAMILY\Family();
        $message            = '';
        $this->scheduleId   = $settings['schedule-id'];
        $this->startTime    = $settings['start_time'];
        $schedule           = $this->getScheduleById($this->scheduleId);

        // check if available
        $session    = $this->getScheduleSession($date, $this->startTime);
        if ($session && $session->id != $settings['session-id']) {
            return new \WP_Error('schedules', 'This is already booked, sorry');
        }

        if (is_numeric($settings['host-id'])) {
            $this->hostId    = $settings['host-id'];
            $host            = get_userdata($this->hostId);
            $partnerId       = $family->getPartner($this->hostId);

            if (
                !$this->admin                        &&           // We are not admin
                $this->hostId != $this->user->ID    &&            // We are not the host
                $this->hostId != $partnerId                       // Our partner is not the host
            ) {
                return new WP_Error('No permission', $this->noPermissionText);
            }

            if ($partnerId && !isset($settings['subject'])) {
                $hostName        = $family->getFamilyName($host);
            } else {
                $hostName        = $host->display_name;
            }
        } else {
            $this->hostId    = '';
            $hostName        = $settings['host'];
            if (!$this->admin) {
                return new WP_Error('No permission', $this->noPermissionText);
            }
        }

        $this->name      = $schedule->name;
        $this->date      = $date;
        $dateStr         = gmdate('d F Y', strtotime($this->date));
        $isMeal          = false;

        if ($this->startTime == $this->lunchStartTime && $schedule->lunch) {
            $this->endTime      = $this->lunchEndTime;
            $this->title        = 'lunch';
            $this->location     = "House of $hostName";
            $isMeal             = true;
        } elseif ($this->startTime == $this->dinerTime && $schedule->dinner) {
            $this->endTime      = '19:30';
            $this->title        = 'dinner';
            $this->location     = "House of $hostName";
            $isMeal             = true;
        } else {
            $this->title        = $settings['subject'];
            $this->location     = $settings['location'];
            if (empty($settings['end_time'])) {
                $this->endTime  = gmdate('H:i', strtotime("+$this->timeSlotSize minutes", strtotime($this->startTime)));
            } else {
                $this->endTime  = $settings['end_time'];
            }
        }

        if (!empty($settings['session-id'])) {
            $message    = "Succesfully updated this entry";
        } elseif ($this->admin && $hostName != $this->user->display_name) {
            $name    = $hostName;
        } else {
            $name    = "you";
        }

        if (empty($this->defaultSubject)) {
            $message    = "Succesfully added $name as a host for {$this->name} on $dateStr";
        } else {
            $message    = "Succesfully scheduled $name for $dateStr";
        }

        $message    .=  " at $this->startTime";

        if ($session) {
            $result    = $this->updateScheduleEvents($isMeal, true, $settings);

            if (is_wp_error($result)) {
                return $result;
            }
        } else {
            $this->addScheduleEvents($isMeal, true, $settings);
        }

        if ($this->mobile) {
            $html    = $this->getMobileDay($this->date);
        } elseif ($isMeal) {
            $html    = $this->writeMealCell($this->date, $this->startTime);
        } else {
            $html    = $this->writeOrientationCell($this->date, $this->startTime);
        }

        return [
            'message'    => $message,
            'html'        => $html
        ];
    }

    /**
     * Removes a host for a session
     *
     * @param int $sessionId    The id of the session to remove the host from
     *
     * @return array    success message and new cell html
     */
    public function removeHost($sessionId)
    {
        $family                    = new TSJIPPY\FAMILY\Family();

        $this->currentSession    = $this->getSessionEvent($sessionId);

        $this->getScheduleById($this->currentSession->schedule_id);

        $date                = $this->currentSession->events[0]->start_date;
        $startTime            = $this->currentSession->events[0]->start_time;

        $hostId                = $this->currentSession->events[0]->organizer - id;

        $partnerId            = $family->getPartner($this->user->ID);
        if (
            !$this->admin                 &&
            $hostId != $this->user->ID     &&
            $hostId != $partnerId
        ) {
            return new \WP_Error('Permission error', $this->noPermissionText);
        }

        //Remove the session
        $this->removeSession();

        $dateStr        = gmdate(get_option('date_format'), strtotime($date));

        if ($this->admin) {
            $hostName    = $this->currentSession->events[0]->organizer;
            $message    = "Succesfully removed $hostName as a host on $dateStr";
        } else {
            $message    = "Succesfully removed you as a host on $dateStr";
        }

        if ($this->mobile) {
            $html    = $this->getMobileDay($date);
        } elseif ($this->currentSession->meal) {
            $html    = $this->writeMealCell($date, $startTime);
        } else {
            $html    = $this->writeOrientationCell($date, $startTime);
        }

        return [
            'message'    => $message,
            'html'        => $html
        ];
    }

    /**
     * Adds a keyword to a meal session
     *
     * @return string    success message
     */
    public function addMenu($settings)
    {

        $date       = TSJIPPY\sanitize($settings['date']);

        $startTime  = TSJIPPY\sanitize($settings['start_time']);

        $menu       = TSJIPPY\sanitize($settings['recipe-keyword']);

        $events     = $this->getScheduleSession($date, $startTime);

        if (empty($events)) {
            return 'No meal found';
        }

        foreach ($events->post_ids as $postId) {
            update_post_meta($postId, 'tsjippy_recipe_keyword', $menu);
        }

        if (count(explode(' ', $menu)) == 1) {
            return "Succesfully added the keyword $menu";
        }
        return "Succesfully added the keywords $menu";
    }
}
