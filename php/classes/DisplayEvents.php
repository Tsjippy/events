<?php

namespace TSJIPPY\EVENTS;

use TSJIPPY;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class DisplayEvents extends Events
{
    public $calendarRows;
    public $events;

    public function __construct()
    {
        parent::__construct();

        $this->calendarRows    = [];
    }

    /**
     * Get all events from the db, filtered by the optional parameters
     * @param    string            $startDate        The minimum start date
     * @param    string            $endDate        The maximum end date
     * @param    int                $amount            The amount of events to return
     * @param    string            $extraQuery        Any extra sql query
     * @param    int                $offset            The offset to apply to the results
     * @param    array            $cats            The categories the events should have
     */
    public function retrieveEvents($startDate = '', $endDate = '', $amount = '', $extraQuery = '', $offset = '', $cats = [])
    {
        global $wpdb;

        //select all events attached to a post with publish status
        $query     = "SELECT DISTINCT posts.*, events.* FROM %i as posts INNER JOIN %i as events ON posts.ID=events.post_id";
        $values     = [
            $wpdb->posts,
            $this->tableName
        ];

        if (!empty($cats)) {
            $query    .= " LEFT JOIN %i as relations ON posts.ID=relations.object_id";
            $values[] = $wpdb->prefix . 'term_relationships';
        }

        $query     .= " WHERE posts.post_status='publish'";

        //start date is greater than or the requested date falls in between a multiday event
        if (!empty($startDate)) {
            $query    .= " AND(events.`start_date` >= %s OR %s BETWEEN events.start_date and events.end_date)";
            $values[] = $startDate;
            $values[] = $startDate;
        }

        //any event who starts before the end_date
        if (!empty($endDate)) {
            $query        .= " AND events.`start_date` <= %s";
            $values[] = $endDate;
        }

        //get events with a specific category
        if (!empty($cats)) {
            $placeholders   = implode(', ', array_fill(0, count($cats), '%s'));
            $query        .= " AND (relations.`term_taxonomy_id` IN ($placeholders) OR relations.`term_taxonomy_id` IS NULL)";
            $values = array_merge($values, $cats);
        }

        //extra query
        if (!empty($extraQuery)) {
            $query    .= " AND $extraQuery";
        }

        //exclude private events which are not ours
        $query    .= " AND (events.`only_for` IS NULL OR events.`only_for`=%d)";
        $values[] = get_current_user_id();

        //sort on start_date
        $query    .= " ORDER BY events.`start_date`, events.`start_time` ASC";

        //LIMIT must be the very last
        if (is_numeric($amount)) {
            $query    .= "  LIMIT %d";
            $values[] = $amount;
        }
        if (is_numeric($offset)) {
            $query    .= "  OFFSET %d";
            $values[] = $offset;
        }

        // phpcs:disable
        $this->events     =  $wpdb->get_results(
            $wpdb->prepare(
                $query,
                $values
            )
        );
        // phpcs:enable
    }

    /**
     * Get all all events of the coming X months with a maximum of X
     *
     * @param    int        $max            The maximum total of items
     * @param    int        $months            The amount of months we should get events for
     * @param    array    $include        The categories to include
     * @param    string    $title            The title
     *
     * @return    string                    The html containg an event list
     */
    public function upcomingEvents($max, $months, $include, $title = 'Upcoming Events')
    {

        $events    = $this->upcomingEventsArray($max, $months, $include);

        ob_start();
?>
        <h4 class="title"><?php echo esc_attr($title); ?></h4>
        <div class="upcoming-events-wrapper">
            <?php
            if (!$events) {
            ?>
                <div class="no-events">
                    No events found!
                </div>
            <?php
            } else {
            ?>
                <div class="eventlist">
                    <?php
                    foreach ($events as $event) {
                    ?>
                        <article class="event-article">
                            <div class="event-wrapper">
                                <div class="event-date">
                                    <span>
                                        <?php echo esc_attr($event['day']); ?>
                                    </span>
                                    <?php echo esc_attr($event['month']); ?>
                                </div>
                                <div>
                                    <h4 class="event-title">
                                        <a href="<?php echo esc_url($event['url']) ?>">
                                            <?php echo esc_html($event['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="event-detail">
                                        <?php echo esc_html($event['time']); ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php
                    }
                    ?>
                </div>
            <?php
            }
            ?>
            <a class='calendar button' href="<?php echo esc_url(TSJIPPY\SITEURL); ?>/events" class="button tsjippy">
                Calendar
            </a>
        </div>
    <?php
        return ob_get_clean();
    }

    public function upcomingEventsArray($max, $months, $include)
    {

        $this->retrieveEvents(gmdate("Y-m-d"), gmdate('Y-m-d', strtotime("+$months month")), $max, "", '', $include);

        foreach ($this->events as $key => $event) {
            // do not keep events who already happened
            if ($event->start_date == gmdate("Y-m-d") && $event->end_time < gmdate('H:i', current_time('U'))) {
                unset($this->events[$key]);
            }
        }

        if (empty($this->events)) {
            return false;
        }

        $events    = [];
        foreach ($this->events as $event) {
            $startDate        = strtotime($event->start_date);
            $eventDayNr        = gmdate('d', $startDate);
            $eventDay        = gmdate('l', $startDate);
            $eventMonth        = gmdate('M', $startDate);
            $eventTitle        = get_the_title($event->post_id);
            $endDateStr        = gmdate('d M', strtotime(($event->end_date)));

            $userId = get_post_meta($event->post_id, 'tsjippy_user', true);

            $eventUrl    = apply_filters('tsjippy-events-event-url', get_permalink($event->post_id), $userId, $this);

            $e    = [
                'day'            => $eventDayNr,
                'month'            => $eventMonth,
                'url'            => $eventUrl,
                'title'            => $eventTitle
            ];

            if ($event->start_date == $event->end_date) {
                if ($event->start_time == $this->dayStartTime && $event->end_time == $this->dayEndTime) {
                    $e['time']    = 'All day';
                } else {
                    $e['time']    = "$eventDay {$event->start_time}";
                }
            } else {
                $e['time']    = "Until $endDateStr {$event->end_time}";
            }

            $events[]    = $e;
        }
        return $events;
    }

    /**
     * Get the date string of an event
     *
     * @param    object        $event        The event to get the date of
     *
     * @return    string                    The date of the event. Startdate and end date in case of an multiday event
     */
    public function getDate(object $event)
    {
        if (empty($event->end_date)) {
            $event->end_date    = $event->start_date;
        }
        if ($event->start_date != $event->end_date) {
            $date        = gmdate(TSJIPPY\DATEFORMAT, strtotime($event->start_date)) . ' - ' . gmdate(TSJIPPY\DATEFORMAT, strtotime($event->end_date));
        } else {
            $date        = gmdate(TSJIPPY\DATEFORMAT, strtotime($event->start_date));
        }

        return $date;
    }

    /**
     * Get the time string of an event
     *
     * @param    object        $event        The event to get the time of
     *
     * @return    string                    The time of the event. Start time and end time in case of an multiday event
     */
    public function getTime($event)
    {
        if ($event->start_date == $event->end_date) {
            if ($event->allday || ($event->start_time == $this->dayStartTime && $event->end_time == $this->dayEndTime)) {
                $time            = 'ALL DAY';
            } else {
                $time            = $event->start_time . ' - ' . $event->end_time;
            }
        } else {
            $time            = gmdate(TSJIPPY\DATEFORMAT, strtotime($event->start_date)) . ' - ' . $event->start_time . ' - ' . gmdate(TSJIPPY\DATEFORMAT, strtotime($event->end_date)) . ' - ' . $event->end_time;
        }

        return $time;
    }

    /**
     * Get the author details of an event
     *
     * @param    object        $event        The event to get the author of
     *
     * @return    string                    Html with the author name linking to the user page of the author. E-mail and phonenumbers
     */
    public function getAuthorDetail($event, $echo)
    {
        $userId        = $event->organizer_id;
        $user        = get_userdata($userId);

        if (empty($userId)) {
            if(!$echo){
                return $event->organizer;
            }

            echo esc_html($event->organizer);
        } else {
            $url    = TSJIPPY\maybeGetUserPageUrl($userId);
            $email    = $user->user_email;
            $phone    = get_user_meta($userId, 'tsjippy_phonenumbers');

            
            if(!$echo){
                ob_start();
            }

            ?>
            <a href='<?php esc_url($url);?>'>
                <?php esc_html($user->display_name);?>
            </a>
            <br>
            <br>
            <a href='mailto:<?php esc_url($email);?>'>
                <?php esc_html($email);?>
            </a>

            <?php
            if (is_array($phone)) {
                foreach ($phone as $p) {
                    ?>
                    <br>
                    <?php esc_html($p); 
                }
            }

            if(!$echo){
                return ob_get_clean();
            }
        }
    }

    /**
     * Get the location details of an event
     *
     * @param    object        $event        The event to get the location of
     *
     * @return    string                    The location name or location url linking to the location page
     */
    public function getLocationDetail($event, $echo)
    {
        $postId    = $event->location_id;
        $location  = get_post_meta($postId, 'tsjippy_location', true);


        if (!is_numeric($postId)) {
            if(!$echo){
                return $event->location;
            }
            echo esc_html($event->location);
        } else {
            $url    = get_permalink($postId);

            if(!$echo){
                ob_start();
            }

            ?>
            <a href='<?php echo esc_url($url);?>'>
                <?php echo esc_html($event->location);?>
            </a>
            <br>

            <?php
            if (!empty($location['address'])) {
                ?>
                <br>
                <a onclick='Locations.getRoute(this, <?php echo esc_html($location["latitude"]);?>, <?php echo esc_html($location["longitude"]);?>)'>
                    <?php echo esc_html($location['address']);?>
                </a>
                <?php
            }

            if(!$echo){
                return ob_get_clean();
            }
        }
    }

    /**
     * Get the repitition details of an event
     *
     * @param    object        $meta        The event meta data
     *
     * @return    string                    String describing the repetition
     */
    public function getRepeatDetail($meta, $echo)
    {
        if(!$echo){
            ob_start();
        }
        ?>
        Repeats 
        <?php
        if ($meta['repeat']['type'] == 'custom_days') {
            foreach ($meta['repeat']['includedates'] as $date) {
                echo esc_html(gmdate('j F Y', strtotime($date))) . '<br>';
            }
        }else{
            echo esc_html($meta['repeat']['type'] ?? '');
        }

        if ($meta['repeat']['type'] == 'weekly' && !empty($meta['repeat']['weeks'])) {
            $when    = strtolower(implode(' and ', $meta['repeat']['weeks']));
            $day    = strtolower(gmdate('l', strtotime($meta['start_date'])));
            
            ?>
            on the 
            <?php 
            echo esc_html(strtolower(implode(' and ', $meta['repeat']['weeks']))); 
            echo esc_html(strtolower(gmdate('l', strtotime($meta['start_date']))));
            ?>
             of the month
             <?php
        }

        if (!empty($meta['repeat']['end_date'])) {
            ?> until <?php esc_html( gmdate('j F Y', strtotime($meta['repeat']['end_date'])));
        }

        if (!empty($meta['repeat']['amount'])) {
            $repeatAmount = $meta['repeat']['amount'];
            if ($repeatAmount != 90) {
                ?> for <?php echo esc_html($repeatAmount);?> times
                <?php
            }
        }

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Get the export buttons for an event
     *
     * @param    object        $meta        The event meta data
     *
     * @return    string                    Html containing buttons to export the event
     */
    public function eventExportHtml($event, $echo)
    {
        $eventMeta        = get_post_meta($event->post_id, 'tsjippy_eventdetails', true);
        if (!is_array($eventMeta)) {
            if (!empty($eventMeta)) {
                $eventMeta    = (array)json_decode($eventMeta, true);
            } else {
                $eventMeta    = [];
            }
        }

        $title       = urlencode($event->post_title);
        $description = urlencode("<a href='" . get_permalink($event->post_id) . "'>Read more on " . TSJIPPY\SITEURLWITHOUTSCHEME . "</a>");
        $location    = urlencode($event->location);
        $startDate   = gmdate('Ymd', strtotime($event->start_date));
        $endDate     = gmdate('Ymd', strtotime($event->end_date));

        if ($event->allday) {
            $startdt = '';
            $enddt   = gmdate('Ymd', strtotime('+1 day', $event->end_date));
        } else {
            $startdt = $startDate . "T" . gmdate('His', strtotime($event->start_time)) . 'Z';
            $enddt   = $endDate . "T" . gmdate('His', strtotime($event->end_time)) . 'Z';
        }

        $gmail       = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$title&dates={$startdt}/{$enddt}&details={$description}&location={$location}&ctz=Africa/Lagos&sprop=website:" . TSJIPPY\SITEURLWITHOUTSCHEME . "&sprop=name:TSJIPPY";
        if (!empty($eventMeta['isrepeated'])) {
            $gmail   .= "&recur=RRULE:FREQ=" . strtoupper($eventMeta['repeat']['type']) . ";INTERVAL=" . $eventMeta['repeat']['interval'] . ';';
            $weeks    = $eventMeta['repeat']['weeks'];
            $weekDays = $eventMeta['repeat']['weekDays'];
            if (is_array($weeks)) {
                $gmail    .= 'BYDAY=';
                foreach ($weeks as $index => $week) {
                    if ($index > 0) {
                        $gmail .= ',';
                    }
                    switch ($week) {
                        case 'First':
                            $gmail    .= '1';
                            break;
                        case 'Second':
                            $gmail    .= '2';
                            break;
                        case 'Third':
                            $gmail    .= '3';
                            break;
                        case 'Fourth':
                            $gmail    .= '4';
                            break;
                        case 'Last':
                            $gmail    .= '-1';
                            break;
                        default:
                            break;
                    }
                    $gmail    .= substr($weekDays[0], 0, 2);
                }

                $gmail    .= ';';
            }
        }

        $startTime        = urlencode($event->start_time . ':00');
        $endTime        = urlencode($event->end_time . ':00');

        $outlook            = "https://outlook.office.com/calendar/0/deeplink/compose/?path=/calendar/action/compose/&body={$description}&startdt={$event->start_date}T{$startTime}&enddt={$event->end_date}T{$endTime}&location={$location}&rru=addevent&subject=$title";
        if ($event->allday) {
            $outlook .= '&amp;allday=true';
        }

        
        if(!$echo){
            ob_start();
        }

        ?>
        <div class='event-export'>
            <a class='button agenda-export' href='<?php echo esc_url($gmail);?>' target='_blank'>
                Add to Google Calendar
            </a>
            <br>
            <a class='button agenda-export' href='<?php echo esc_url($outlook);?>' target='_blank'>
                Add to your Outlook agenda
            </a>
        </div>
        <?php

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Get the month calendar
     *
     * @param    int        $cat        The category of events to display
     *
     * @return    string                Html of the calendar
     */
    public function monthCalendar($day, $month, $year, $cat = [], $echo=false)
    {
        $dateStr    = "$year-$month-$day";

        if(!$echo){
            ob_start();
        }

        $date          = strtotime($dateStr);
        $monthStr      = gmdate('m', $date);
        $yearStr       = gmdate('Y', $date);
        $minusMonth    = strtotime('first day of last month', $date);
        $minusMonthStr = gmdate('m', $minusMonth);
        $minusYearStr  = gmdate('Y', $minusMonth);
        $plusMonth     = strtotime('first day of next month', $date);
        $plusMonthStr  = gmdate('m', $plusMonth);
        $plusYearStr   = gmdate('Y', $plusMonth);

        $weekDay       = gmdate("w", strtotime(gmdate('Y-m-01', $date)));
        $workingDate   = strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));

        $calendarRows  = TSJIPPY\addElement('div', '', ['class' => 'calendar-rows-wrapper']);
        $details       = TSJIPPY\addElement('div', '', ['class' => 'calendar-details']);

        //loop over all weeks of a month
        while (true) {
            $calendar    = TSJIPPY\addElement('dl', $calendarRows, ['class' => 'calendar-row']);

            //loop over all days of a week
            while (true) {
                $workingDateStr     = gmdate('Y-m-d', $workingDate);
                $month              = gmdate('m', $workingDate);
                $day                = gmdate('j', $workingDate);

                //get the events for this day
                $this->retrieveEvents($workingDateStr, $workingDateStr, '', '', '', $cat);

                if (
                    $workingDateStr == gmdate('Y-m-d') ||            // date is today
                    (
                        $monthStr != gmdate('m') &&                    // We are requesting another month than this month
                        gmdate('j', $workingDate) == 1 &&                // This is the first day of the month
                        gmdate('m', $workingDate) == $monthStr        // We are in the requested month
                    )
                ) {
                    $class = 'selected';
                    $hidden = '';
                } else {
                    $class = '';
                    $hidden = 'hidden';
                }

                if ($month != gmdate('m', $date)) {
                    $class .= ' nullday';
                }

                if (!empty($this->events)) {
                    $class    .= ' has-event';
                }

                TSJIPPY\addElement('dt', $calendar, ['class' => "calendar-day $class", 'data-date' => gmdate('Ymd', $workingDate)], $day);

                $this->weekDetails($workingDateStr, $workingDate, $hidden, $details);

                //calculate the next week
                $workingDate    = strtotime('+1 day', $workingDate);
                //if the next day is the first day of a new week
                if (gmdate('w', $workingDate) == 0) {
                    break;
                }
            }

            //stop if next month
            if ($month != gmdate('m', $date)) {
                break;
            }
        }

    ?>
        <div class="events-wrap" data-date="<?php echo esc_attr("$yearStr-$monthStr"); ?>">
            <div class="event overview">
                <div class="navigator">
                    <div class="prev">
                        <a href="#" class="prevnext" data-month="<?php echo esc_attr($minusMonthStr); ?>" data-year="<?php echo esc_attr($minusYearStr); ?>">
                            <span>
                                << </span> <?php echo esc_attr(gmdate('F', $minusMonth)); ?>
                        </a>
                    </div>
                    <div class="current">
                        <?php echo esc_attr(gmdate('F Y', $date)); ?>
                    </div>
                    <div class="next">
                        <a href="#" class="prevnext" data-month="<?php echo esc_attr($plusMonthStr); ?>" data-year="<?php echo esc_attr($plusYearStr); ?>">
                            <?php echo esc_attr(gmdate('F', $plusMonth)); ?> <span>></span>
                        </a>
                    </div>
                </div>
                <div class="calendar-table">
                    <div class="month-container">
                        <dl>
                            <?php
                            $workingDate    = strtotime("-$weekDay day", strtotime(gmdate('Y-m-01', $date)));
                            for ($y = 0; $y <= 6; $y++) {
                                $name    = gmdate('D', $workingDate);
                            ?>
                                <dt class='calendar-day-head'>
                                    <?php echo esc_attr($name); ?>
                                </dt>
                            <?php
                                $workingDate    = strtotime("+1 days", $workingDate);
                            }
                            ?>
                        </dl>
                        <?php
                        // phpcs:ignore
                        echo $calendarRows->ownerDocument->saveHTML($calendarRows);
                        ?>
                    </div>
                </div>
            </div>
            <div class="event details-wrapper">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $details->ownerDocument->saveHTML($details);
                ?>
            </div>
        </div>
    <?php

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Builds the event detail html for each events
     *
     * @param    string    $workingDateStr    The date in this format: yyyy-mm-dd
     * @param    int        $workingDate    timesamp'
     *
     * @return    string                    The detail html
     */
    private function weekDetails($workingDateStr, $workingDate, $hidden = 'hidden', $parent = '')
    {
        if (empty($parent)) {
            $details    = TSJIPPY\addElement('div', '', ['class' => 'calendar-details']);
        } else {
            $details    = $parent;
        }

        $baseUrl    = TSJIPPY\pathToUrl(PLUGINPATH . 'pictures');

        $wrapper    = TSJIPPY\addElement('div', $details, ['class' => "event-details-wrapper $hidden", 'data-date' => gmdate('Ymd', $workingDate)]);
        $heading    = TSJIPPY\addElement('h6', $wrapper, ['class' => 'event-title'], "Events for ");
        TSJIPPY\addElement('span', $heading, ['class' => 'day'], gmdate('j', $workingDate) . "st");
        /** @disregard */
        $heading->append(gmdate('F', $workingDate));

        foreach ($this->events as $event) {
            $meta        = get_post_meta($event->ID, 'tsjippy_eventdetails', true);
            if (!is_array($meta)) {
                if (!empty($meta)) {
                    $meta    = (array)json_decode($meta, true);
                } else {
                    $meta    = [];
                }
            }

            $url    = get_permalink($event->ID);

            //do not re-add event details for a multiday event in the same week
            if ($event->start_date != $event->end_date && $event->start_date != $workingDateStr && gmdate('w', $workingDate) > 0) {
                continue;
            }

            if (empty($this->events)) {
                $article    = TSJIPPY\addElement('article', $wrapper, ['class' => 'event-article']);
                TSJIPPY\addElement('h4', $article, ['class' => 'event-title'], 'No events');
            } else {
                foreach ($this->events as $event) {
                    $meta        = get_post_meta($event->ID, 'tsjippy_eventdetails', true);
                    if (!is_array($meta)) {
                        if (!empty($meta)) {
                            $meta    = (array)json_decode($meta, true);
                        } else {
                            $meta    = [];
                        }
                    }

                    $article    = TSJIPPY\addElement('article', $wrapper, ['class' => 'event-article']);
                    $header        = TSJIPPY\addElement('div', $article, ['class' => 'event-header']);
                    if (has_post_thumbnail($event->post_id)) {
                        $imageWrapper    = TSJIPPY\addElement('div', $header, ['class' => 'event-image']);
                        TSJIPPY\addRawHtml(get_the_post_thumbnail($event->post_id), $imageWrapper);
                    }

                    $timeWrapper    = TSJIPPY\addElement('div', $header, ['class' => 'event-time']);
                    TSJIPPY\addElement('img', $timeWrapper, ['src' => TSJIPPY\pathToUrl(PLUGINPATH . 'pictures/time_red.png'), 'loading' => 'lazy', 'alt' => 'time', 'class' => 'event-icon']);

                    TSJIPPY\addElement('span', $timeWrapper, ['class' => 'time'], $this->getTime($event));

                    $title    = TSJIPPY\addElement('h4', $article, ['class' => 'event-title']);
                    TSJIPPY\addElement('a', $title, ['href' => get_permalink($event->ID)], $event->post_title);

                    $detail    = TSJIPPY\addElement('div', $article, ['class' => 'event-detail']);
                    if (!empty($event->location)) {
                        $wrapper    = TSJIPPY\addElement('div', $detail, ['class' => 'location']);
                        TSJIPPY\addElement('img', $wrapper, ['src' => "$baseUrl/location_red.png", 'alt' => 'time', 'loading' => 'lazy', 'class' => 'event-icon']);

                        TSJIPPY\addRawHtml($this->getLocationDetail($event, false), $wrapper);
                    }
                    if (!empty($event->organizer)) {
                        $wrapper    = TSJIPPY\addElement('div', $detail, ['class' => 'organizer']);
                        TSJIPPY\addElement('img', $wrapper, ['src' => "$baseUrl/organizer.png", 'alt' => 'organizer', 'loading' => 'lazy', 'class' => 'event-icon']);

                        TSJIPPY\addRawHtml($this->getAuthorDetail($event, false), $wrapper);
                    }
                    if (!empty($meta['repeat']['type'])) {
                        $wrapper    = TSJIPPY\addElement('div', $detail, ['class' => 'repeat']);
                        TSJIPPY\addElement('img', $wrapper, ['src' => "$baseUrl/repeat_small.png", 'alt' => 'repeat', 'loading' => 'lazy', 'class' => 'event-icon']);

                        TSJIPPY\addRawHtml($this->getRepeatDetail($meta, false), $wrapper);
                    }
                    TSJIPPY\addRawHtml($this->eventExportHtml($event, false), $detail);
                }
            }
        }

        if (empty($parent)) {
 echo $details->ownerDocument->saveHTML($details);
        }
    }

    protected function prepareWeekTable()
    {
        $this->calendarRows['allday']    = [];

        for ($x = 0; $x < 48; $x++) {
            $this->calendarRows[$x]    = [];

            for ($day = 0; $day < 7; $day++) {
                $this->calendarRows['allday'][$day]    = [];

                $this->calendarRows[$x][$day]    = [];
            }
        }
    }

    protected function getWeekDayEvents($weekDayTimestamp)
    {
        if (empty($this->events)) {
            return;
        }

        $weekDay        = gmdate('w', $weekDayTimestamp);
        $dateStr        = gmdate('Y-m-d', $weekDayTimestamp);

        // Add each event
        foreach ($this->events as $event) {
            $startTime            = $event->start_time;
            $endTime            = $event->end_time;

            if (empty($startTime) || empty($endTime)) {
                continue;
            }

            $timeIndex            = gmdate('H', strtotime($startTime)) * 2; //index is amount of hours times 2

            //multi day event
            if ($event->start_date != $event->end_date) {
                if ($event->start_date == $dateStr) {
                    $endTime    = $this->dayEndTime;
                } elseif ($event->end_date == $dateStr) {
                    $startTime    = $this->dayStartTime;
                } else {
                    $startTime    = $this->dayStartTime;
                    $endTime    = $this->dayEndTime;
                }
            }

            //plus one if starting at :30
            if (gmdate('i', strtotime($startTime)) != '00') {
                $timeIndex++;
            }

            // Check if whole day event
            if (
                $startTime == $this->dayStartTime        &&
                $endTime == $this->dayEndTime            &&
                $event->start_date == $event->end_date
            ) {
                $this->calendarRows['allday'][$weekDay][]    = $event->post_title;
            } else {
                $duration    = strtotime($endTime) - strtotime($startTime);
                $halfHours    = round($duration / 60 / 30);
                $endIndex    = (int)round($duration / 60 / 30) + $timeIndex;
                $dateString    = gmdate('Ymd', strtotime($event->start_date));

                //add the event
                $td =  "<td rowspan='$halfHours' class='calendar-hour has-event' data-date='$dateString' data-start_time='{$event->start_time}'>";
                $td    .= $event->post_title;
                $td    .= "</td>";

                $this->calendarRows[$timeIndex][$weekDay][]    = $td;

                //add hidden cells as many as needed
                for ($y = $timeIndex + 1; $y < $endIndex; $y++) {
                    $this->calendarRows[$y][$weekDay][] = "<td class='hidden'></td>";
                }
            }
        }
    }

    private function getCalendarRows($weekDayTimestamp, $cat)
    {
        //loop over all days of a week
        while (true) {
            $weekDayStr        = gmdate('Y-m-d', $weekDayTimestamp);

            //get the events for this day
            $this->retrieveEvents($weekDayStr, $weekDayStr, '', '', '', $cat);

            $this->getWeekDayEvents($weekDayTimestamp);

            $this->weekDetails($weekDayStr, $weekDayTimestamp);

            //calculate the next week
            $weekDayTimestamp    = strtotime('+1 day', $weekDayTimestamp);

            //if the next day is the first day of a new week
            if (gmdate('w', $weekDayTimestamp) == 0) {
                break;
            }
        }
    }

    /**
     * Get the week calendar
     *
     * @param    int        $cat        The category of events to display
     *
     * @return    string                Html of the calendar
     */
    public function weekCalendar($weekNr='', $year='', $cat = [], $echo = true)
    {
        if(empty($weekNr)){
            $weekNr  = gmdate('W');
        }

        if(empty($year)){
            $year    = gmdate('Y');
        }

        $dto     = new \DateTime();
        $dto->setISODate($year, $weekNr);
        $dateStr = $dto->format('Y-m-d');

        $this->prepareWeekTable();

        $date            = strtotime($dateStr);
        $weekNr            = gmdate('W', $date);
        $dateTime        = new \DateTime();

        // Get the date of the first day of this week
        $firstWeekDay    = $dateTime->setISODate(gmdate('Y', $date), $weekNr, "0")->getTimestamp();
        $year            = gmdate('Y', $firstWeekDay);
        $prevWeekNr        = strftime("%U", strtotime("-1 week", $firstWeekDay));
        $nextWeekNr        = strftime("%U", strtotime("+1 week", $firstWeekDay));

        // Calculate the amount of columns
        $colSizes    = [1, 1, 1, 1, 1, 1, 1];
        foreach ($this->calendarRows as $index => $row) {
            if ($index == 'allday') {
                continue;
            }

            foreach ($row as $i => $day) {
                $colSizes[$i]    = max(count($day), $colSizes[$i]);
            }
        }

        if(!$echo){
            ob_start();
        }

    ?>
        <div class="events-wrap" data-weeknr="<?php echo esc_attr($weekNr); ?>" data-year="<?php echo esc_attr($year); ?>">
            <div class="event overview">
                <div class="navigator">
                    <div class="prev">
                        <a href="#" class="prevnext" data-weeknr="<?php echo esc_attr($prevWeekNr); ?>" data-year="<?php echo esc_attr($year); ?>">
                            <span>
                                << /span> <?php echo esc_html($prevWeekNr); ?>
                        </a>
                    </div>
                    <div class="current">
                        Week <?php echo esc_html($weekNr); ?>
                    </div>
                    <div class="next">
                        <a href="#" class="prevnext" data-weeknr="<?php echo esc_attr($nextWeekNr); ?>" data-year="<?php echo esc_attr($year); ?>">
                            <?php echo esc_html($nextWeekNr); ?> <span>></span>
                        </a>
                    </div>
                </div>
                <div class="calendar-table">
                    <table class="week-container">
                        <caption>Week overview for week <?php echo esc_html($weekNr); ?></caption>
                        <thead>
                            <th> </th>
                            <?php
                            $weekDayTimestamp    = $firstWeekDay;
                            for ($day = 0; $day <= 6; $day++) {
                            ?>
                                <th<?php if ($colSizes[$day] > 1) echo "colspan='".esc_attr($colSizes[$day])."'";  ?>>
                                    <?php echo esc_html(gmdate('D', $weekDayTimestamp)); ?><br>
                                    <?php echo esc_html(gmdate('d', $weekDayTimestamp)); ?>
                                    </th>
                                <?php
                                $weekDayTimestamp    = strtotime("+1 days", $weekDayTimestamp);
                            }
                                ?>
                        </thead>

                        <tbody>
                            <?php
                            // Write all day events
                            if (!empty($this->calendarRows['allday'])) {
                            ?>
                                <tr class='calendar-row'>
                                    <td class=''>
                                        <strong>All day</strong>
                                    </td>

                                    <?php
                                    //loop over the dayweeks
                                    $weekDayTimestamp    = $firstWeekDay;
                                    for ($day = 0; $day <= 6; $day++) {
                                        $content    = $this->calendarRows['allday'][$day];
                                    ?>
                                        <td
                                            class='calendar-hour<?php if (!empty($content)) echo ' has-event'; ?>'
                                            data-date='<?php echo esc_attr(gmdate('Ymd', $weekDayTimestamp)); ?>'
                                            data-start_time='<?php echo esc_attr($this->dayStartTime); ?>'
                                            <?php if ($colSizes[$day] > 1) echo " colspan='".esc_attr($colSizes[$day])."'";
                                        ?>>
                                            <?php
                                            foreach ($this->calendarRows['allday'][$day] as $event) {
                                                echo wp_kses_post("$event<br>");
                                            }
                                            ?>
                                        </td>
                                    <?php
                                        $weekDayTimestamp    = strtotime("+1 days", $weekDayTimestamp);
                                    }
                                    ?>
                                </tr>
                            <?php
                            }

                            // make sure we do not output them again
                            unset($this->calendarRows['allday']);

                            //one row per half an hour
                            foreach ($this->calendarRows as $i => $row) {
                            ?>
                                <tr class='calendar-row'>
                                    <?php
                                    if ($i % 2 == 0) {
                                        // Write the whole hour
                                    ?>
                                        <td class='calendar-hour-head' rowspan='2'>
                                            <strong>
                                                <?php echo esc_html($i / 2); ?>:00
                                            </strong>
                                        </td>
                                    <?php
                                    } else {
                                        // add a hidden cell as the first cell with the hour has a rowspan of 2
                                    ?>
                                        <td class='hidden'></td>
                                        <?php
                                    }

                                    //loop over the dayweeks
                                    $weekDayTimestamp    = $firstWeekDay;
                                    foreach ($row as $day => $cell) {
                                        $colspan    = $colSizes[$day];
                                        for ($col = 0; $col < $colspan; $col++) {
                                            if (isset($cell[$col])) {
                                                echo esc_html($cell[$col]);
                                            } else {
                                                $span    = $colspan - $col;

                                                ?>
                                                <td class='calendar-hour' data-date='<?php echo esc_attr(gmdate('Ymd', $weekDayTimestamp)); ?>' <?php if ($span > 1) echo esc_attr(" colspan='$span'"); ?>>

                                                </td>
                                                <?php
                                                break;
                                            }
                                            $weekDayTimestamp    = strtotime("+1 days", $weekDayTimestamp);
                                        }
                                    }
                                    ?>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="event details-wrapper">
                <div class="event-details-wrapper" data-date="empty">
                    <article class="event-article">
                        <h4 class="event-title">
                            <a>
                                No Event selected
                            </a>
                        </h4>
                    </article>
                </div>
                <?php
                $this->getCalendarRows($firstWeekDay, $cat);
                ?>
            </div>
        </div>
        <?php

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Get the list calendar
     *
     * @param    int        $cat        The category of events to display
     *
     * @return    string                Html of the calendar
     */
    public function listCalendar($offset='', $month='', $year='', $cat = [], $echo=false)
    {
        if (!is_numeric($month) || strlen($month) != 2) {
            $month    = gmdate('m');
        }
        if (!is_numeric($year) || strlen($year) != 4) {
            $year    = gmdate('Y');
        }

        $day    = gmdate('d');
        $dateStr    = "$year-$month-$day";

        $this->retrieveEvents($dateStr, '', 10, '', $offset, $cat);
        $html = '';

        $baseUrl    = TSJIPPY\pathToUrl(PLUGINPATH . 'pictures');

        if(!$echo){
            ob_start();
        }

        foreach ($this->events as $event) {
            $meta        = get_post_meta($event->ID, 'tsjippy_eventdetails', true);
            if (!is_array($meta)) {
                if (!empty($meta)) {
                    $meta    = (array)json_decode($meta, true);
                } else {
                    $meta    = [];
                }
            }

            $url        = get_permalink($event->ID);

            ?>
            <article class='event-article'>
                <div class='event-wrapper'>
                    <?php echo wp_kses_post(get_the_post_thumbnail($event->post_id, 'medium'));?>

                    <h3 class='event-title'>
                        <a href='<?php echo esc_url($url);?>'>
                            <?php echo esc_html($event->post_title); ?>
                        </a>
                    </h3>
                    <div class='event-detail'>
                        <div class='date'>
                            <img src='<?php echo esc_url($baseUrl);?>/date.png' alt='' loading='lazy' class='event-icon'>
                            <?php echo esc_html($this->getDate($event));?>
                        </div>
                        <div class='time'>
                            <img src='<?php echo esc_url($baseUrl);?>/time_red.png' alt='' loading='lazy' class='event-icon'>
                            <?php echo esc_html($this->getTime($event)); ?>
                        </div>

                        <?php
                        if (!empty($event->location)) {
                            ?>
                            <div class='location'>
                                <img src='<?php echo esc_url($baseUrl);?>/location_red.png' alt='time' loading='lazy' class='event-icon'>
                                <?php $this->getLocationDetail($event, true); ?>
                            </div>
                            <?php
                        }
                        if (!empty($event->organizer)) {
                            ?>
                            <div class='organizer'>
                                <img src='<?php echo esc_url($baseUrl);?>/organizer.png' alt='time' loading='lazy' class='event-icon'>
                                <?php $this->getAuthorDetail($event, true);?>
                            </div>
                            <?php
                        }
                        if (!empty($meta['repeat']['type'])) {
                            ?>
                            <div class='repeat'>
                                <img src='<?php echo esc_url($baseUrl);?>/repeat_small.png' alt='repeat' loading='lazy' class='event-icon'>
                                <?php $this->getRepeatDetail($meta, true);?>
                            </div>
                            <?php
                        }
                        $this->eventExportHtml($event, true);
                        ?>
                    </div>
                    <div class='readmore'>
                        <a class='button' href='<?php echo esc_url($url);?>'>
                            Read more
                        </a>
                    </div>
                </div>
            </article>
            <?php
        }

        if(!$echo){
            return ob_get_clean();
        }
    }

    /**
     * Sends a message to anyone who has an event which is about to start
     * @param     int     $eventId  The id of the event
     *
     * @return    bool              True if succesfull false if no event found
     */
    public function sendEventReminder($eventId)
    {
        $results    = TSJIPPY\getFromDb("get_event_$eventId", 'events', "SELECT * FROM %i WHERE id=%d",  $this->tableName, $eventId);

        if (empty($results)) {
            return false;
        }

        $event    = $results[0];

        if (is_numeric($event->only_for)) {
            $today    = gmdate('Y-m-d');
            $tomorrow    = gmdate('Y-m-d', strtotime('+1 day', strtotime($event->start_date)));

            if ($today == $event->start_date) {
                $timeString    = "starts at $event->start_time";
            } elseif ($tomorrow == $event->start_date) {
                $timeString    = "starts tomorrow at $event->start_time";
            } elseif (strtotime($event->start_date) > time()) {
                $date    = gmdate('d F', strtotime($event->start_date));
                $timeString    = "starts $date at $event->start_time";
            } else {
                $timeString    = "is already started";
            }
            $title    = get_the_title($event->post_id);

            do_action('tsjippy-events-event-reminder', "'$title' is about to start\nIt $timeString", $event->only_for);
        }

        return true;
    }
}
