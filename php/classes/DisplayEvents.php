<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class DisplayEvents extends Events{
	public $calendarRows;
	public $events;

	public function __construct(){
		parent::__construct();
		
		$this->calendarRows	= [];
	}

	/**
	 * Get all events from the db, filtered by the optional parameters
	 * @param	string			$startDate		The minimum start date
	 * @param	string			$endDate		The maximum end date
	 * @param	int				$amount			The amount of events to return
	 * @param	string			$extraQuery		Any extra sql query
	 * @param	int				$offset			The offset to apply to the results
	 * @param	array			$cats			The categories the events should have
	*/
	public function retrieveEvents($startDate = '', $endDate = '', $amount = '', $extraQuery = '', $offset='', $cats=[]){
		global $wpdb;

		//select all events attached to a post with publish status
		$query	 = "SELECT DISTINCT {$wpdb->prefix}posts.*, {$wpdb->prefix}sim_events.* FROM {$wpdb->prefix}posts ";
		$query	.= "INNER JOIN `{$this->tableName}` ON {$wpdb->prefix}posts.ID={$this->tableName}.post_id";
		
		if(!empty($cats)){
			$query	.= " LEFT JOIN `{$wpdb->prefix}term_relationships` ON {$wpdb->prefix}posts.ID={$wpdb->prefix}term_relationships.object_id";
		}
		
		$query	 .= " WHERE  {$wpdb->prefix}posts.post_status='publish'";
		
		//start date is greater than or the requested date falls in between a multiday event
		if(!empty($startDate)){
			$query	.= " AND(`{$this->tableName}`.`startdate` >= '$startDate' OR '$startDate' BETWEEN `{$this->tableName}`.startdate and `{$this->tableName}`.enddate)";
		}
		
		//any event who starts before the enddate
		if(!empty($endDate)){
			$query		.= " AND `{$this->tableName}`.`startdate` <= '$endDate'";
		}

		//get events with a specific category
		if(!empty($cats)){
			$cats		= implode(', ', $cats);
			$query		.= " AND (`{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IN ($cats) OR `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IS NULL)";
		}
		
		//extra query
		if(!empty($extraQuery)){
			$query	.= " AND $extraQuery";
		}
		
		//exclude private events which are not ours
		$userId	 = get_current_user_id();
		$query	.= " AND (`{$this->tableName}`.`onlyfor` IS NULL OR `{$this->tableName}`.`onlyfor`='$userId')";

		//sort on startdate
		$query	.= " ORDER BY `{$this->tableName}`.`startdate`, `{$this->tableName}`.`starttime` ASC";

		//LIMIT must be the very last
		if(is_numeric($amount)){
			$query	.= "  LIMIT $amount";
		}
		if(is_numeric($offset)){
			$query	.= "  OFFSET $offset";
		}

		$this->events 	=  $wpdb->get_results($query);
	}

	/**
	 * Get all all events of the coming X months with a maximum of X
	 *
	 * @param	int		$max			The maximum total of items
	 * @param	int		$months			The amount of months we should get events for
	 * @param	array	$include		The categories to include
	 * @param	string	$title			The title
	 *
	 * @return	string					The html containg an event list
	*/
	public function upcomingEvents($max, $months, $include, $title='Upcoming Events'){

		$events	= $this->upcomingEventsArray($max, $months, $include);

		ob_start();
		?>
		<h4 class="title"><?php echo $title;?></h4>
		<div class="upcomingevents_wrapper">
			<?php
			if(!$events){
				?>
				<div class="no-events">
					No events found!
				</div>
				<?php
			}else{
				?>
				<div class="eventlist">
					<?php
					foreach($events as $event){
						?>
						<article class="event-article">
							<div class="event-wrapper">
								<div class="event-date">
									<?php echo "<span>{$event['day']}</span> {$event['month']}";?>
								</div>
								<div>
								<h4 class="event-title">
									<a href="<?php echo $event['url'] ?>">
										<?php echo $event['title'];?>
									</a>
								</h4>
								<div class="event-detail">
									<?php
									echo $event['time'];
									?>
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
			<a class='calendar button' href="<?php echo SITEURL;?>/events" class="button sim">
				Calendar
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	public function upcomingEventsArray($max, $months, $include){

		$this->retrieveEvents(date("Y-m-d"), date('Y-m-d', strtotime("+$months month")), $max, "", '', $include);

		foreach($this->events as $key=>$event){
			// do not keep events who already happened
			if($event->startdate == date("Y-m-d") && $event->endtime < date('H:i', current_time('U'))){
				unset($this->events[$key]);
			}
		}

		if(empty($this->events)){
			return false;
		}

		$events	= [];
		foreach($this->events as $event){
			$startDate		= strtotime($event->startdate);
			$eventDayNr		= date('d', $startDate);
			$eventDay		= date('l', $startDate);
			$eventMonth		= date('M', $startDate);
			$eventTitle		= get_the_title($event->post_id);
			$endDateStr		= date('d M', strtotime(($event->enddate)));

			$userId = get_post_meta($event->post_id,'user',true);
			if(is_numeric($userId) && function_exists('SIM\USERPAGE\getUserPageLink')){
				//Get the user page of this user
				$eventUrl	= SIM\USERPAGE\getUserPageLink($userId);
			}else{
				$eventUrl	= get_permalink($event->post_id);
			}

			$e	= [
				'day'			=> $eventDayNr,
				'month'			=> $eventMonth,
				'url'			=> $eventUrl,
				'title'			=> $eventTitle
			];

			if($event->startdate == $event->enddate){
				if($event->starttime == $this->dayStartTime && $event->endtime == $this->dayEndTime){
					$e['time']	= 'All day';
				}else{
					$e['time']	= "$eventDay {$event->starttime}";
				}
			}else{
				$e['time']	= "Until $endDateStr {$event->endtime}";
			}

			$events[]	= $e;
		}
		return $events;
	}

	/**
	 * Get the date string of an event
	 *
	 * @param	object		$event		The event to get the date of
	 *
	 * @return	string					The date of the event. Startdate and end date in case of an multiday event
	*/
	public function getDate(object $event){
		if(empty($event->enddate)){
			$event->enddate	= $event->startdate;
		}
		if($event->startdate != $event->enddate){
			$date		= date(DATEFORMAT, strtotime($event->startdate)).' - '.date(DATEFORMAT, strtotime($event->enddate));
		}else{
			$date		= date(DATEFORMAT, strtotime($event->startdate));
		}

		return $date;
	}

	/**
	 * Get the time string of an event
	 *
	 * @param	object		$event		The event to get the time of
	 *
	 * @return	string					The time of the event. Start time and end time in case of an multiday event
	*/
	public function getTime($event){
		if($event->startdate == $event->enddate){
			if($event->allday || ($event->starttime == $this->dayStartTime && $event->endtime == $this->dayEndTime)){
				$time			= 'ALL DAY';
			}else{
				$time			= $event->starttime.' - '.$event->endtime;
			}
		}else{
			$time			= date(DATEFORMAT, strtotime($event->startdate)).' - '.$event->starttime.' - '.date(DATEFORMAT, strtotime($event->enddate)).' - '.$event->endtime;
		}

		return $time;
	}

	/**
	 * Get the author details of an event
	 *
	 * @param	object		$event		The event to get the author of
	 *
	 * @return	string					Html with the author name linking to the user page of the author. E-mail and phonenumbers
	*/
	public function getAuthorDetail($event){
		$userId		= $event->organizer_id;
		$user		= get_userdata($userId);

		if(empty($userId)){
			return $event->organizer;
		}else{
			$url	= SIM\maybeGetUserPageUrl($userId);
			$email	= $user->user_email;
			$phone	= get_user_meta($userId,'phonenumbers',true);

			$html	= "<a href='$url'>{$user->display_name}</a><br>";
			$html	.="<br><a href='mailto:$email'>$email</a>";
			if(is_array($phone)){
				foreach($phone as $p){
					$html	.="<br>$p";
				}
			}
			return $html;
		}
	}

	/**
	 * Get the location details of an event
	 *
	 * @param	object		$event		The event to get the location of
	 *
	 * @return	string					The location name or location url linking to the location page
	*/
	public function getLocationDetail($event){
		$postId		= $event->location_id;
		$location	= get_post_meta($postId,'location',true);

		if(!is_numeric($postId)){
			return $event->location;
		}else{
			$url	= get_permalink($postId);

			$html	= "<href='$url'>{$event->location}</a><br>";
			if(!empty($location['address'])){
				$html	.="<br><a onclick='Main.getRoute(this,{$location['latitude']},{$location['longitude']})'>{$location['address']}</a>";
			}
			return $html;
		}
	}

	/**
	 * Get the repitition details of an event
	 *
	 * @param	object		$meta		The event meta data
	 *
	 * @return	string					String describing the repetition
	*/
	public function getRepeatDetail($meta){
		$type	= $meta['repeat']['type'];
		if($meta['repeat']['type'] == 'custom_days'){
			$type	= '';
			foreach($meta['repeat']['includedates'] as $date){
				$type	.= date('j F Y', strtotime($date)).'<br>';
			}
		}
		$html = "Repeats $type";

		if($meta['repeat']['type'] == 'weekly' && !empty($meta['repeat']['weeks'])){
			$when	= strtolower(implode(' and ', $meta['repeat']['weeks']));
			$day	= strtolower(Date('l', strtotime($meta['startdate'])));
			$html	.= " on the $when $day of the month";
		}

		if(!empty($meta['repeat']['enddate'])){
			$html	.= " until ".date('j F Y',strtotime($meta['repeat']['enddate']));
		}

		if(!empty($meta['repeat']['amount'])){
			$repeatAmount = $meta['repeat']['amount'];
			if($repeatAmount != 90){
				$html	.= " for $repeatAmount times";
			}
		}

		return $html;
	}

	/**
	 * Get the export buttons for an event
	 *
	 * @param	object		$meta		The event meta data
	 *
	 * @return	string					Html containing buttons to export the event
	*/
	public function eventExportHtml($event){
		$eventMeta		= get_post_meta($event->post_id, 'eventdetails', true);
		if(!is_array($eventMeta)){
			if(!empty($eventMeta)){
				$eventMeta	= (array)json_decode($eventMeta, true);
			}else{
				$eventMeta	= [];
			}
		}

		//set the timezone
		date_default_timezone_set(wp_timezone_string());

		$title			= urlencode($event->post_title);
		$description	= urlencode("<a href='".get_permalink($event->post_id)."'>Read more on ".SITEURLWITHOUTSCHEME."</a>");
		$location		= urlencode($event->location);
		$startDate		= date('Ymd', strtotime($event->startdate));
		$endDate		= date('Ymd', strtotime($event->enddate));

		if($event->allday){
			$enddt		= date('Ymd',strtotime('+1 day', $event->enddate));
		}else{
			$startdt	= $startDate."T".gmdate('His',strtotime($event->starttime)).'Z';
			$enddt		= $endDate."T".gmdate('His',strtotime($event->endtime)).'Z';
		}

		$gmail			= "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$title&dates={$startdt}/{$enddt}&details={$description}&location={$location}&ctz=Africa/Lagos&sprop=website:".SITEURLWITHOUTSCHEME."&sprop=name:SIM";
 		if(!empty($eventMeta['isrepeated'])){
			$gmail		.= "&recur=RRULE:FREQ=".strtoupper($eventMeta['repeat']['type']).";INTERVAL=".$eventMeta['repeat']['interval'].';';
			$weeks 		= $eventMeta['repeat']['weeks'];
			$weekDays	= $eventMeta['repeat']['weekDays'];
			if(is_array($weeks)){
				$gmail	.= 'BYDAY=';
				foreach($weeks as $index=>$week){
					if($index>0){
						$gmail .= ',';
					}
					switch($week){
						case 'First':
							$gmail	.= '1';
							break;
						case 'Second':
							$gmail	.= '2';
							break;
						case 'Third':
							$gmail	.= '3';
							break;
						case 'Fourth':
							$gmail	.= '4';
							break;
						case 'Last':
							$gmail	.= '-1';
							break;
						default:
							break;
					}
					$gmail	.= substr($weekDays[0], 0, 2);
				}

				$gmail	.= ';';
			}
		}

		$startTime		= urlencode($event->starttime.':00');
		$endTime		= urlencode($event->endtime.':00');

		$sim			= "https://outlook.office.com/calendar/0/deeplink/compose/?path=/calendar/action/compose/&body={$description}&startdt={$event->startdate}T{$startTime}&enddt={$event->enddate}T{$endTime}&location={$location}&rru=addevent&subject=$title";
		if($event->allday){
			$sim .= '&amp;allday=true';
		}

		$html = "<div class='event-export'>";
			$html .= "<a class='button agenda-export' href='$gmail' target='_blank'>Add to Google Calendar</a>";
			$html .= "<br>";
			$html .= "<a class='button agenda-export' href='$sim' target='_blank'>Add to your SIM agenda</a>";
		$html	.= '</div>';

		return $html;
	}

	/**
	 * Get the month calendar
	 *
	 * @param	int		$cat		The category of events to display
	 *
	 * @return	string				Html of the calendar
	*/
	public function monthCalendar($cat=[]){
		if(defined('REST_REQUEST')){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
			//events
			$day	= date('d');
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
			if(!is_numeric($month) || strlen($month)!=2){
				$month	= date('m');
			}
			if(!is_numeric($year) || strlen($year)!=4){
				$year	= date('Y');
			}
			$dateStr	= "$year-$month-$day";
		}
		ob_start();
		
		$date			= strtotime($dateStr);
		$monthStr		= date('m', $date);
		$yearStr		= date('Y', $date);
		$minusMonth		= strtotime('first day of last month', $date);
		$minusMonthStr	= date('m', $minusMonth);
		$minusYearStr	= date('Y', $minusMonth);
		$plusMonth		= strtotime('first day of next month', $date);
		$plusMonthStr	= date('m', $plusMonth);
		$plusYearStr	= date('Y', $plusMonth);

		$weekDay		= date("w", strtotime(date('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));

		$calendarRows	= '';
		$detailHtml		= '';

		$baseUrl	= plugins_url('../pictures', __DIR__);

		//loop over all weeks of a month
		while(true){
			$calendarRows .= "<dl class='calendar-row'>";

			//loop over all days of a week
			while(true){
				$monthName			= date('F', $workingDate);
				$workingDateStr		= date('Y-m-d', $workingDate);
				$month				= date('m', $workingDate);
				$day				= date('j', $workingDate);
				
				//get the events for this day
				$this->retrieveEvents($workingDateStr, $workingDateStr, '', '', '', $cat);

				if(
					$workingDateStr == date('Y-m-d') ||			// date is today
					(
						$monthStr != date('m') &&						// We are requesting another mont than this month
						date('j', $workingDate) == 1 &&				// This is the first day of the month
						date('m', $workingDate) == $monthStr			// We are in the requested month
					)
				){
					$class = 'selected';
					$hidden = '';
				}else{
					$class = '';
					$hidden = 'hidden';
				}

				if($month != date('m',$date)){
					$class.= ' nullday';
				}

				if(!empty($this->events)){
					$class	.= ' has-event';
				}
				
				$calendarRows .=  "<dt class='calendar-day $class' data-date='".date('Ymd', $workingDate)."'>";
					$calendarRows	.= $day;
				$calendarRows	.= "</dt>";

				$detailHtml .= "<div class='event-details-wrapper $hidden' data-date='".date('Ymd', $workingDate)."'>";
					$detailHtml .= "<h6 class='event-title'>";
						$detailHtml .= "Events for <span class='day'> ".date('j', $workingDate)."st</span>$monthName";
					$detailHtml .= "</h6>";
						if(empty($this->events)){
							$detailHtml .= "<article class='event-article'>";
								$detailHtml .= "<h4 class='event-title'><a>No Events</a></h4>";
							$detailHtml .= "</article>";
						}else{
							foreach($this->events as $event){
								$meta		= get_post_meta($event->ID, 'eventdetails', true);
								if(!is_array($meta)){
									if(!empty($meta)){
										$meta	= (array)json_decode($meta, true);
									}else{
										$meta	= [];
									}
								}

								$detailHtml .= "<article class='event-article'>";
									$detailHtml .= "<div class='event-header'>";
										if(has_post_thumbnail($event->post_id)){
											$detailHtml .= "<div class='event-image'>";
												$detailHtml .= get_the_post_thumbnail($event->post_id);
											$detailHtml .= '</div>';
										}
										$detailHtml .= "<div class='event-time'>";
											$detailHtml .= "<img src='{$baseUrl}/time_red.png' loading='lazy' alt='time' class='event_icon'>";
											$detailHtml .=  $this->getTime($event);
										$detailHtml .= "</div>";
									$detailHtml .= "</div>";
									$detailHtml .= "<h4 class='event-title'>";
										$url	= get_permalink($event->ID);
										$detailHtml .= "<a href='$url'>";
											$detailHtml .= $event->post_title;
										$detailHtml .= "</a>";
									$detailHtml .= "</h4>";
									$detailHtml .= "<div class='event-detail'>";
										if(!empty($event->location)){
											$detailHtml .= "<div class='location'>";
												$detailHtml .= "<img src='{$baseUrl}/location_red.png' loading='lazy' alt='time' class='event_icon'>";
												$detailHtml .= $this->getLocationDetail($event);
											$detailHtml .= "</div>";
										}
										if(!empty($event->organizer)){
											$detailHtml .= "<div class='organizer'>";
												$detailHtml .= "<img src='{$baseUrl}/organizer.png' alt='time' loading='lazy' class='event_icon'>";
												$detailHtml .= $this->getAuthorDetail($event);
											$detailHtml .= "</div>";
										}
										if(!empty($meta['repeat']['type'])){
											$detailHtml .= "<div class='repeat'>";
												$detailHtml .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' loading='lazy' class='event_icon'>";
												$detailHtml .= $this->getRepeatDetail($meta);
											$detailHtml .= "</div>";
										}
										$detailHtml .= $this->eventExportHtml($event);
									$detailHtml .= "</div>";
								$detailHtml .= "</article>";
							}
						}
				$detailHtml .=  "</div>";
				
				//calculate the next week
				$workingDate	= strtotime('+1 day', $workingDate);
				//if the next day is the first day of a new week
				if(date('w', $workingDate) == 0){
					break;
				}
			}
			$calendarRows .= '</dl>';

			//stop if next month
			if($month != date('m', $date)){
				break;
			}
		}

		?>
		<div class="events-wrap" data-date="<?php echo "$yearStr-$monthStr";?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-month="<?php echo $minusMonthStr;?>" data-year="<?php echo $minusYearStr;?>">
							<span><</span> <?php echo date('F', $minusMonth);?>
						</a>
					</div>
					<div class="current">
						<?php echo date('F Y', $date);?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-month="<?php echo $plusMonthStr;?>" data-year="<?php echo $plusYearStr;?>">
							<?php echo date('F', $plusMonth);?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<div class="month-container">
						<dl>
							<?php
							$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
							for ($y = 0; $y <= 6; $y++) {
								$name	= date('D', $workingDate);
								echo "<dt class='calendar-day-head'>$name</dt>";
								$workingDate	= strtotime("+1 days",$workingDate);
							}
							?>
						</dl>
						<?php
						echo $calendarRows;
						?>
					</div>
				</div>
			</div>
			<div class="event details-wrapper">
				<?php
				echo $detailHtml;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Builds the event detail html for each events
	 *
	 * @param	string	$workingDateStr	The date in this format: yyyy-mm-dd
	 * @param	int		$workingDate	timesamp'
	 *
	 * @return	string					The detail html
	 */
	private function weekDetails($workingDateStr, $workingDate){
		
		$detailHtml		= '';
		$baseUrl		= plugins_url('../pictures', __DIR__);

		foreach($this->events as $event){
			$meta		= get_post_meta($event->ID, 'eventdetails', true);
			if(!is_array($meta)){
				if(!empty($meta)){
					$meta	= (array)json_decode($meta, true);
				}else{
					$meta	= [];
				}
			}

			$url	= get_permalink($event->ID);

			//do not re-add event details for a multiday event in the same week
			if($event->startdate != $event->enddate && $event->startdate != $workingDateStr && date('w', $workingDate)>0){
				continue;
			}

			$detailHtml .= "<div class='event-details-wrapper hidden' data-date='".date('Ymd', strtotime($event->startdate))."' data-starttime='{$event->starttime}'>";
				$detailHtml .= "<article class='event-article'>";
					if(has_post_thumbnail($event->post_id)){
						$detailHtml .= "<div class='event-image'>";
							$detailHtml .= get_the_post_thumbnail($event->post_id);
						$detailHtml .= '</div>';
					}
					$detailHtml .= "<div class='event-time'>";
						$detailHtml .= "<img src='{$baseUrl}/time_red.png' alt='time' loading='lazy' class='event_icon'>";
						$detailHtml .=  $this->getDate($event).'   '.$this->getTime($event);
					$detailHtml .= "</div>";

					$detailHtml .= "<h4 class='event-title'>";
						$detailHtml .= "<a href='$url'>";
							$detailHtml .= $event->post_title;
						$detailHtml .= "</a>";
					$detailHtml .= "</h4>";
					$detailHtml .= "<div class='event-detail'>";
						if(!empty($event->location)){
							$detailHtml .= "<div class='location'>";
								$detailHtml .= "<img src='{$baseUrl}/location_red.png' alt='time' loading='lazy' class='event_icon'>";
								$detailHtml .= $this->getLocationDetail($event);
							$detailHtml .= "</div>";
						}
						if(!empty($event->organizer)){
							$detailHtml .= "<div class='organizer'>";
								$detailHtml .= "<img src='{$baseUrl}/organizer.png' alt='time' loading='lazy' class='event_icon'>";
								$detailHtml .= $this->getAuthorDetail($event);
							$detailHtml .= "</div>";
						}

						if(!empty($meta['repeat']['type'])){
							$detailHtml .= "<div class='repeat'>";
								$detailHtml .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' loading='lazy' class='event_icon'>";
								$detailHtml .= $this->getRepeatDetail($meta);
							$detailHtml .= "</div>";
						}
						$detailHtml .= $this->eventExportHtml($event);
					$detailHtml .= "</div>";
				$detailHtml .= "</article>";
			$detailHtml .= "</div>";
		}

		return $detailHtml;
	}

	protected function prepareWeekTable(){
		$this->calendarRows['allday']	= [];

		for ($x = 0; $x < 48; $x++) {
			$this->calendarRows[$x]	= [];

			for ($day = 0; $day < 7; $day++) {
				$this->calendarRows['allday'][$day]	= [];
			
				$this->calendarRows[$x][$day]	= [];
			}
		}
	}

	protected function getWeekDayEvents($weekDayTimestamp){
		if(empty($this->events )){
			return;
		}

		$weekDay		= date('w', $weekDayTimestamp);
		$dateStr		= date('Y-m-d', $weekDayTimestamp);

		// Add each event
		foreach($this->events as $event){
			$startTime			= $event->starttime;
			$endTime			= $event->endtime;

			if(empty($startTime) || empty($endTime)){
				continue;
			}
			
			$timeIndex			= date('H', strtotime($startTime)) * 2; //index is amount of hours times 2

			//multi day event
			if($event->startdate != $event->enddate){
				if($event->startdate == $dateStr){
					$endTime	= $this->dayEndTime;
				}elseif($event->enddate == $dateStr){
					$startTime	= $this->dayStartTime;
				}else{
					$startTime	= $this->dayStartTime;
					$endTime	= $this->dayEndTime;
				}
			}

			//plus one if starting at :30
			if(date('i', strtotime($startTime)) != '00'){
				$timeIndex++;
			}

			// Check if whole day event
			if(
				$startTime == $this->dayStartTime		&&
				$endTime == $this->dayEndTime			&&
				$event->startdate == $event->enddate
			){
				$this->calendarRows['allday'][$weekDay][]	= $event->post_title;
			}else{
				$duration	= strtotime($endTime) - strtotime($startTime);
				$halfHours	= round($duration/60/30);
				$endIndex	= (int)round($duration/60/30) + $timeIndex;
				$dateString	= date('Ymd', strtotime($event->startdate));

				//add the event
				$td =  "<td rowspan='$halfHours' class='calendar-hour has-event' data-date='$dateString' data-starttime='{$event->starttime}'>";
					$td	.= $event->post_title;
				$td	.= "</td>";

				$this->calendarRows[$timeIndex][$weekDay][]	= $td;
				
				//add hidden cells as many as needed
				for ($y = $timeIndex + 1; $y < $endIndex; $y++) {
					$this->calendarRows[$y][$weekDay][] = "<td class='hidden'></td>";
				}
			}
		}
		
	}

	private function getCalendarRows($weekDayTimestamp, $cat){
		$detailHtml		= '';

		//loop over all days of a week
		while(true){
			$weekDayStr		= date('Y-m-d', $weekDayTimestamp);

			//get the events for this day
			$this->retrieveEvents($weekDayStr, $weekDayStr, '', '', '', $cat);
			
			$this->getWeekDayEvents($weekDayTimestamp);

			$detailHtml	.= $this->weekDetails($weekDayStr, $weekDayTimestamp);
			
			//calculate the next week
			$weekDayTimestamp	= strtotime('+1 day', $weekDayTimestamp);

			//if the next day is the first day of a new week
			if(date('w', $weekDayTimestamp) == 0){
				break;
			}
		}

		return $detailHtml;
	}

	/**
	 * Get the week calendar
	 *
	 * @param	int		$cat		The category of events to display
	 *
	 * @return	string				Html of the calendar
	*/
	public function weekCalendar($cat=[]){
		$weekNr	= date('W');
		$year	= date('Y');

		if(defined('REST_REQUEST')){
			$weekNr		= $_POST['wknr'];
			$year		= $_POST['year'];
		}else{
			if(isset($_GET['week']) && strlen($_GET['week']) == 2){
				$weekNr		= $_GET['week'];
			}
			if(isset($_GET['yr']) && strlen($_GET['yr']) == 4){
				$year		= $_GET['yr'];
			}
		}
		$dto 		= new \DateTime();
		$dto->setISODate($year, $weekNr);
		$dateStr 	= $dto->format('Y-m-d');

		$this->prepareWeekTable();

		$date			= strtotime($dateStr);
		$weekNr			= date('W', $date);
		$dateTime		= new \DateTime();

		// Get the date of the first day of this week
		$firstWeekDay	= $dateTime->setISODate(date('Y', $date), $weekNr, "0")->getTimestamp();

		$detailHtml		= $this->getCalendarRows($firstWeekDay, $cat);

		$year			= date('Y', $firstWeekDay);
		$prevWeekNr		= strftime("%U", strtotime("-1 week", $firstWeekDay));
		$nextWeekNr		= strftime("%U", strtotime("+1 week", $firstWeekDay));

		// Calculate the amount of columns
		$colSizes	= [1,1,1,1,1,1,1];
		foreach($this->calendarRows as $index=>$row){
			if($index == 'allday'){
				continue;
			}

			foreach($row as $i=>$day){
				$colSizes[$i]	= max(count($day), $colSizes[$i]);
			}
		}

		ob_start();

		?>
		<div class="events-wrap" data-weeknr="<?php echo $weekNr;?>" data-year="<?php echo $year;?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-weeknr="<?php echo $prevWeekNr;?>" data-year="<?php echo $year;?>">
							<span><</span> <?php echo $prevWeekNr;?>
						</a>
					</div>
					<div class="current">
						Week <?php echo $weekNr;?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-weeknr="<?php echo $nextWeekNr;?>" data-year="<?php echo $year;?>">
							<?php echo $nextWeekNr;?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<table class="week-container">
						<caption>Week overview for week <?php echo $weekNr;?></caption>
						<thead>
							<th> </th>
							<?php
							$weekDayTimestamp	= $firstWeekDay;
							for ($day = 0; $day <= 6; $day++) {
								$name		= date('D', $weekDayTimestamp);
								$nr			= date('d', $weekDayTimestamp);
								$colspan	= '';

								if($colSizes[$day] > 1){
									$colspan	= " colspan='{$colSizes[$day]}'";
								}
								echo "<th$colspan>$name<br>$nr</th>";
								$weekDayTimestamp	= strtotime("+1 days", $weekDayTimestamp);
							}
							?>
						</thead>

						<tbody>
							<?php
							// Write all day events
							if(!empty($this->calendarRows['allday'])){
								echo "<tr class='calendar-row'>";
									echo "<td class=''><strong>All day</strong></td>";
									//loop over the dayweeks
									$weekDayTimestamp	= $firstWeekDay;
									for ($day = 0; $day <= 6; $day++) {
										$content	= $this->calendarRows['allday'][$day];
										$dateString	= date('Ymd', $weekDayTimestamp);

										if(empty($content)){
											$class = '';
										}else{
											$class = ' has-event';
										}

										$colspan	= '';
										if($colSizes[$day] > 1){
											$colspan	= " colspan='{$colSizes[$day]}'";
										}

										echo "<td class='calendar-hour$class' data-date='$dateString' data-starttime='$this->dayStartTime'$colspan>";
											foreach($this->calendarRows['allday'][$day] as $event){
												echo "$event<br>";
											}
										echo "</td>";
										$weekDayTimestamp	= strtotime("+1 days", $weekDayTimestamp);
									}
								echo '</tr>';
							}

							// make sure we do not output them again
							unset($this->calendarRows['allday']);

							//one row per half an hour
							foreach($this->calendarRows as $i=>$row){
								$time	= $i/2;
								echo "<tr class='calendar-row'>";
									if($i % 2 == 0){
										// Write the whole hour
										echo "<td class='calendar-hour-head' rowspan='2'><strong>$time:00</strong></td>";
									}else{
										// add an hidden cell as the first cell with the hour has a rowspan of 2
										echo "<td class='hidden'></td>";
									}

									//loop over the dayweeks
									$weekDayTimestamp	= $firstWeekDay;
									foreach($row as $day=>$cell){
										$colspan	= $colSizes[$day];
										for ($col = 0; $col < $colspan; $col++) {
											if(isset($cell[$col])){
												echo $cell[$col];
											}else{
												$span	= $colspan-$col;
												if($span > 1){
													$span	= " colspan='$span'";
												}else{
													$span	= '';
												}
												echo "<td class='calendar-hour' data-date='".date('Ymd', $weekDayTimestamp)."'$span></td>";
												break;
											}
											$weekDayTimestamp	= strtotime("+1 days", $weekDayTimestamp);
										}
									}
								echo "</tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="event details-wrapper">
				<div class="event-details-wrapper" data-date="empty">
					<article class="event-article">
						<h4 class="event-title"><a>No Event selected</a></h4>
					</article>
				</div>
				<?php
				echo $detailHtml;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the list calendar
	 *
	 * @param	int		$cat		The category of events to display
	 *
	 * @return	string				Html of the calendar
	*/
	public function listCalendar($cat=[]){
		$offset='';
		if(defined('REST_REQUEST')){
			$offset	= $_POST['offset'];
			$month	= $_POST['month'];
			$year	= $_POST['year'];
		}else{
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
		}

		if(!is_numeric($month) || strlen($month)!=2){
			$month	= date('m');
		}
		if(!is_numeric($year) || strlen($year)!=4){
			$year	= date('Y');
		}
		
		$day	= date('d');
		$dateStr	= "$year-$month-$day";

		$this->retrieveEvents($dateStr, '', 10, '', $offset, $cat);
		$html ='';

		$baseUrl	= plugins_url('../pictures', __DIR__);

		foreach($this->events as $event){
			$meta		= get_post_meta($event->ID, 'eventdetails', true);
			if(!is_array($meta)){
				if(!empty($meta)){
					$meta	= (array)json_decode($meta, true);
				}else{
					$meta	= [];
				}
			}

			$url		= get_permalink($event->ID);

			$html .= "<article class='event-article'>";
				$html .= "<div class='event-wrapper'>";
					$html .= get_the_post_thumbnail($event->post_id,'medium');
					$html .= "<h3 class='event-title'>";
						$html .= "<a href='$url'>";
							$html .= $event->post_title;
						$html .= "</a>";
					$html .= "</h3>";
					$html .= "<div class='event-detail'>";
						$html .= "<div class='date'>";
							$html .="<img src='{$baseUrl}/date.png' alt='' loading='lazy' class='event_icon'>";
							$html .= $this->getDate($event);
						$html .= "</div>";
						$html .= "<div class='time'>";
							$html .="<img src='{$baseUrl}/time_red.png' alt='' loading='lazy' class='event_icon'>";
							$html .= $this->getTime($event);
						$html .= "</div>";
						if(!empty($event->location)){
							$html .= "<div class='location'>";
								$html .= "<img src='{$baseUrl}/location_red.png' alt='time' loading='lazy' class='event_icon'>";
								$html .= $this->getLocationDetail($event);
							$html .= "</div>";
						}
						if(!empty($event->organizer)){
							$html .= "<div class='organizer'>";
								$html .= "<img src='{$baseUrl}/organizer.png' alt='time' loading='lazy' class='event_icon'>";
								$html .= $this->getAuthorDetail($event);
							$html .= "</div>";
						}
						if(!empty($meta['repeat']['type'])){
							$html .= "<div class='repeat'>";
								$html .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' loading='lazy' class='event_icon'>";
								$html .= $this->getRepeatDetail($meta);
							$html .= "</div>";
						}

						$html .= $this->eventExportHtml($event);

					$html .= "</div>";
					$html .= "<div class='readmore'>";
						$html .= "<a class='button' href='{$url}'>Read more</a>";
					$html .= "</div>";
				$html .= "</div>";
			$html .= "</article>";
		}

		return $html;
	}

	/**
	 * Sends a message to anyone who has an event which is about to start
	 * @param  	int 	$eventId		The id of the event
	 *
	 * @return	bool					true if succesfull false if no event found
	 */
	public function sendEventReminder($eventId){
		global $wpdb;
		$query		= "SELECT * FROM $this->tableName WHERE id=$eventId";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}

		$event	= $results[0];

		if(is_numeric($event->onlyfor)){
			$today	= date('Y-m-d');
			$tomorrow	= date('Y-m-d', strtotime('+1 day', strtotime($event->startdate)));

			if($today == $event->startdate){
				$timeString	= "starts at $event->starttime";
			}elseif($tomorrow == $event->startdate){
				$timeString	= "starts tomorrow at $event->starttime";
			}elseif(strtotime($event->startdate) > time()){
				$date	= date('d F', strtotime($event->startdate));
				$timeString	= "starts $date at $event->starttime";
			}else{
				$timeString	= "is already started";
			}
			$title	= get_the_title($event->post_id);
			SIM\trySendSignal("'$title' is about to start\nIt $timeString", $event->onlyfor);
		}

		return true;
	}
}

