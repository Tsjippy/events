<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class CreateEvents extends Events{
	public $eventData;
	public $startDates;
	public $partnerId;
	

	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 *
	 * @return	int				the weeknumber
	*/
	protected function yearWeek($date){
		return intval(date('W', $date));
	}

	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 *
	 * @return	int				the weeknumber
	*/
	protected function monthWeek($date){
		$firstDayOfMonth	= strtotime(date('Y-m-01', $date));
		return $this->yearWeek($firstDayOfMonth);
	}

	/**
	 * Creates events in the db
	*/
	public function createEvents(){
		global $wpdb;

		$baseStartDateStr	= $this->eventData['startdate'];
		$baseStartDate		= strtotime($baseStartDateStr);

		// Startdate is in the past
		if($baseStartDate < strtotime(date('Y-m-d', time()))){
			// in the past and not repeated
			if( empty($this->eventData['isrepeated']) ){
				return new \WP_Error('events', "Date cannot be in the past: {$this->eventData['startdate']}");
			}
		}

		$this->startDates	= [$baseStartDateStr];
		if(!empty($this->eventData['isrepeated'])){
			$baseStartDate	= $this->createRepeatedEvents($baseStartDate);
		}

		$baseEndDate		= $this->eventData['enddate'];
		$dayDiff 			= ((new \DateTime($baseStartDateStr))->diff((new \DateTime($baseEndDate))))->d;
		
		foreach($this->startDates as $startDate){
			$enddate	= date('Y-m-d', strtotime("+{$dayDiff} day", strtotime($startDate)));
			$this->maybeCreateRow($startDate);

			$args	= [
				'enddate'	=> $enddate
			];

			// only add the data where there is a column for it
			foreach(['id', 'post_id', 'starttime', 'endtime', 'location', 'organizer', 'location_id', 'organizer_id', 'atendees', 'onlyfor'] as $column){
				if(isset($this->eventData[$column])){
					$args[$column]	= $this->eventData[$column];
				}
			}

			//Update the database
			$wpdb->update($this->tableName,
				$args,
				array(
					'post_id'		=> $this->postId,
					'startdate'		=> $startDate
				),
			);
			
			if($wpdb->last_error !== ''){
				return new WP_Error('event', $wpdb->last_error);
			}
		}

		return true;
	}

	protected function calculateStartDate($repeatParam, $baseStartDate, $index){
		$weekDays	= [];
		if(!empty($repeatParam['weekdays'])){
			$weekDays		= (array)$repeatParam['weekdays'];
		}

		$weeks	= [];
		if(!empty($repeatParam['weeks'])){
			$weeks			= (array)$repeatParam['weeks'];
		}

		$weekDayNames	= ['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

		switch ($repeatParam['type']){
			case 'daily':
				$startDate		= strtotime("+{$index} day", $baseStartDate);
				if(!empty($weekDays) && !in_array(date('w', $startDate), $weekDays)){
					return false;
				}
				break;
			case 'weekly':
				$startDate		= strtotime("+{$index} week", $baseStartDate);
				$monthWeek		= $this->monthWeek($startDate);

				$firstWeek		= date('m', $startDate) != date('m', strtotime('-1 week', $startDate));
				if($firstWeek && in_array('First', $weeks)){
					$monthWeek	= 'First';
				}

				$lastWeek		= date('m', $startDate) != date('m', strtotime('+1 week', $startDate));
				if($lastWeek && in_array('Last', $weeks)){
					$monthWeek	= 'Last';
				}
				if(!in_array($monthWeek, $weeks)){
					return false;
				}
				break;
			case 'monthly' || 'yearly':
				// Get the next month
				if($repeatParam['type'] == 'yearly'){
					$startDate	= strtotime("+{$index} year", $baseStartDate);
				}else{
					$startDate	= strtotime("first day of +{$index} month", $baseStartDate);
				}

				// same day number
				if($repeatParam['datetype'] == 'samedate'){
					// The new month does not have this date
					if(Date('t', $startDate) < Date('d', $baseStartDate)){
						return false;
					}

					$day		= Date('d', $baseStartDate)-1;
					$startDate	= strtotime("+$day days", $startDate);
				// Same week and day i.e. first friday
				}elseif($repeatParam['datetype'] == 'patterned'){
					// The weeknumber of the first week of the month
					$firstWeek	= Date('W', strtotime("first day of 0 month", $baseStartDate));

					// The weeknumber of this week in the month
					$targetWeek	= SIM\numberToWords(Date("W", $baseStartDate)-$firstWeek +1);

					$dayName	= Date('l', $baseStartDate);

					$startDate	= strtotime("$targetWeek $dayName of +{$index} month", $baseStartDate);
				// last day of the month
				}elseif($repeatParam['datetype'] == 'lastday'){
					$startDate	= strtotime("last day +$index month", $baseStartDate);
				// Same last day i.e. last Friday
				}else{
					$dayName	= Date('l', $baseStartDate);
					$startDate	= strtotime("last $dayName of +{$index} month", $baseStartDate);
				}

				break;
			case 'yearly':
				$startDate	= strtotime("+{$index} year", $baseStartDate);


				break;
			case 'custom_days':
				break;
			default:
				$startDate	= strtotime("+{$index} year", $baseStartDate);
				break;
		}

		return $startDate;
	}

	protected function createRepeatedEvents($baseStartDate){
		//first remove any existing events for this post
		$this->removeDbRows();

		//then create the new ones
		$repeatParam	= $this->eventData['repeat'];
		$interval		= max(1, (int)$repeatParam['interval']);
		$amount			= 200; // no more than 200 events to not overload the db

		if($repeatParam['type'] == 'custom_days'){
			foreach($repeatParam['includedates'] as $date){
				// not yet included and not in the past
				if(!in_array($date, $this->startDates) && $date > date('Y-m-d')){
					$this->startDates[]	= $date;
				}
			}

			return;
		}

		// Startdate is in the past, adjust
		$type				= rtrim($repeatParam['type'], 'ly');
		if($type == 'dai'){
			$type	= 'day';
		}
		while($baseStartDate < strtotime(date('Y-m-d'))){
			// Add one day/week/month/year
			$baseStartDate		= strtotime("+1 $type", $baseStartDate);

			//re-adjust the startdate string
			$this->startDates	= [date('Y-m-d', $baseStartDate)];
		}
		
		$repeatStop	= $repeatParam['stop'];
		if($repeatParam['stop'] == 'after' && !empty($repeatParam['amount']) && is_numeric($amount)){
			$amount = intval($repeatParam['amount'])-1;	// The first event is already created
		}

		if($repeatStop == 'date'){
			$repEnddate	= strtotime($repeatParam['enddate']);
		}else{
			$repEnddate	= strtotime("+5 year", $baseStartDate);
		}

		$excludeDates	= [];
		if(isset($repeatParam['excludedates'])){
			$excludeDates	= (array)$repeatParam['excludedates'];
		}

		$includeDates	= [];
		if(isset($repeatParam['includedates'])){
			$includeDates	= (array)$repeatParam['includedates'];
		}
					
		$startDate		= $baseStartDate;
		$i				= 1;
		while($startDate < $repEnddate && $amount > 0){
			$startDate	= $this->calculateStartDate($repeatParam, $baseStartDate, $i);

			if($repeatParam['type'] == 'custom_days'){
				$startDateStr	= $includeDates[$i];
			}elseif($startDate){
				$startDateStr	= date('Y-m-d', $startDate);
			}

			if(!$startDate || in_array($startDateStr, $excludeDates)){
				$i				= $i+$interval;
				continue;
			}
			
			if(
				!in_array($startDateStr, $includeDates)		&&		//we should not exclude this date
				$startDate < $repEnddate					&& 		//falls within the limits
				(!is_numeric($amount) || $amount > 0)				//We have not exeeded the amount
			){
				$this->startDates[]	= $startDateStr;
			}

			$i				= $i+$interval;
			$amount			= $amount-1;
		}

		return $baseStartDate;
	}

	/**
	 * Deletes old celebration events
	 * @param  	int  	$postId		WP_Post if
	 * @param	string	$date		date string
	 * @param	int		$userId		WP_User id
	 * @param	string	$type		the anniverasry type
	 * @param	string	$title		the event title
	*/
	protected function deleteOldCelEvent($postId, $date, $userId, $type, $title){
		if(!is_numeric($postId)){
			return false;
		}
		
		$existingEvent	= $this->retrieveSingleEvent($postId);
		if(
			date('-m-d', strtotime($existingEvent->startdate)) == date('-m-d', strtotime($date)) &&
			$title == $existingEvent->post_title
		){
			return false; //nothing changed
		}else{
			delete_user_meta($userId, $type.'_event_id');

			$this->removeDbRows('', true);
		}
	}

	/**
	 * Deletes creates new celebration events
	 * @param	string		$type		the anniverasry type
	 * @param	int|object	$userId		WP_User id or WP_User object
	 * @param	string		$metaKey	the meta key key
	 * @param	string		$metaValue	the meta value, should be a date string
	*/
	public function createCelebrationEvent($type, $user, $metaKey='', $metaValue=''){
		if(is_numeric($user)){
			$user = get_userdata($user);
		}
		
		if(empty($metaKey)){
			$metaKey = $type;
		}

		if($type != 'birthday' && SIM\isChild($user->ID)){
			//do not create annversaries for children
			return;
		}

		$eventIdMetaKey		= $type.'_event_id';

		$oldMetaValue	= SIM\getMetaArrayValue($user->ID, $metaKey);
		if(!is_array($metaValue) && is_array($oldMetaValue) && count($oldMetaValue) == 1){
			$oldMetaValue	= array_values($oldMetaValue)[0];
		}

		if(empty($metaValue) && empty($oldMetaValue) || $metaValue == $oldMetaValue){
			// nothing to work with or no update
			return;
		}elseif(empty($metaValue)){
			$metaValue = $oldMetaValue;
		}else{
			// check if just the year was updated
			$oldTime	= strtotime($oldMetaValue);
			$newTime	= strtotime($metaValue);

			if(Date('Y', $oldTime) != Date('Y', $newTime) && Date('m-d', $oldTime) == Date('m-d', $newTime)){
				// no need to create new events, just update the meta value
				$postId	= get_user_meta($user->ID, $eventIdMetaKey, true);

				update_post_meta($postId, 'celebrationdate', $metaValue);
				return;
			}
		}

		$title				= ucfirst($type).' '.$user->display_name;
		$this->partnerId	= SIM\hasPartner($user->ID);
		if($this->partnerId){
			$partnerMeta	= SIM\getMetaArrayValue($this->partnerId, $metaKey);
			if(is_array($partnerMeta) && count($partnerMeta) == 1){
				$partnerMeta	= array_values($partnerMeta)[0];
			}

			//only treat as a couples event if they both have the same value
			if($partnerMeta == $metaValue){
				$partnerName	= get_userdata($this->partnerId)->first_name;
				$title			= ucfirst($type)." {$user->first_name} & $partnerName {$user->last_name}";
			}else{
				$this->partnerId	= false;
			}
		}
		
		//get old post
		$this->postId	= get_user_meta($user->ID, $eventIdMetaKey, true);
		$this->deleteOldCelEvent($this->postId, $metaValue, $user->ID, $type, $title);

		if($this->partnerId){
			$this->deleteOldCelEvent(get_user_meta($this->partnerId, $eventIdMetaKey, true), $metaValue, $this->partnerId, $type, $title);
		}

		// Create the post
		$this->createCelebrationPost($user, $metaValue, $title, $type, $eventIdMetaKey);

		$this->createEvents();
	}

	protected function createCelebrationPost($user, $metaValue, $title, $type, $eventIdMetaKey){
		//Get the upcoming celebration date
		$startdate								= date(date('Y').'-m-d', strtotime($metaValue));

		$this->eventData['startdate']			= $startdate;
		$this->eventData['enddate']				= $startdate;
		$this->eventData['location']			= '';
		$this->eventData['organizer']			= $user->display_name;
		$this->eventData['organizer_id']		= $user->ID;
		$this->eventData['starttime']			= '00:00';
		$this->eventData['endtime']				= '23:59';
		$this->eventData['allday']				= true;
		$this->eventData['isrepeated']			= 'Yes';
		$this->eventData['repeat']['interval']	= 1;
		$this->eventData['repeat']['amount']	= 90;
		$this->eventData['repeat']['stop']		= 'never';
		$this->eventData['repeat']['type']		= 'yearly';

		$post = array(
			'post_type'		=> 'event',
			'post_title'    => $title,
			'post_content'  => $title,
			'post_status'   => 'publish',
			'post_author'   => $user->ID
		);

		$this->postId 	= wp_insert_post( $post, true, false);
		update_metadata( 'post', $this->postId, 'eventdetails', json_encode($this->eventData));
		update_metadata( 'post', $this->postId, 'celebrationdate', $metaValue);

		// Set the categories
		if($type == 'birthday'){
			$catName = 'birthday';
		}else{
			$catName = 'anniversary';
		}
		$termId = get_term_by('slug', $catName,'events')->term_id;
		if(empty($termId)){
			$termId = wp_insert_term(ucfirst($catName), 'events')['term_id'];
		}

		wp_set_object_terms($this->postId, $termId, 'events');

		// Set the featured image
		$pictureIds	= SIM\getModuleOption(MODULE_SLUG, 'picture_ids');
		if($type == 'birthday'){
			$pictureId = $pictureIds['birthday_image'];
		}else{
			$pictureId = $pictureIds['anniversary_image'];
		}
		set_post_thumbnail( $this->postId, $pictureId);

		//Store the post id for the user
		update_user_meta($user->ID, $eventIdMetaKey, $this->postId);

		if($this->partnerId){
			update_user_meta($this->partnerId, $eventIdMetaKey, $this->postId);
		}
	}

	/**
	 * Stores all event details in the db, removes any existing events, and creates new ones.
	 * @param  	int|WP_post  $post		The id of a post or the post itself
	*/
	public function storeEventMeta($post){
		if(is_numeric($post)){
			$post	= get_post($post);
		}

		$this->postId					= $post->ID;
	
		$event							= $_POST['event'];
		$event['allday']				= sanitize_text_field($event['allday']);
		$event['startdate']				= sanitize_text_field($event['startdate']);
		$event['starttime']				= sanitize_text_field($event['starttime']);
		$event['enddate']				= sanitize_text_field($event['enddate']);
		$event['endtime']				= sanitize_text_field($event['endtime']);

		if(empty($event['startdate']) || empty($event['enddate'])){
			return;
		}

		$event['repeat']['type']		= sanitize_text_field($event['repeat']['type']);
		$event['repeat']['interval']	= sanitize_text_field($event['repeat']['interval']);
		$event['repeat']['stop']		= sanitize_text_field($event['repeat']['stop']);
		$event['repeat']['enddate']		= sanitize_text_field($event['repeat']['enddate']);
		$event['repeat']['amount']		= sanitize_text_field($event['repeat']['amount']);
		$event['location']				= sanitize_text_field($event['location']);
		$event['location_id']			= sanitize_text_field($event['location_id']);
		$event['organizer']				= sanitize_text_field($event['organizer']);
		$event['organizer_id']			= sanitize_text_field($event['organizer_id']);
	
		//check if anything has changed
		$oldMeta	= get_post_meta($this->postId, 'eventdetails', true);
		if(!is_array($oldMeta)){
			if(!empty($oldMeta)){
				$oldMeta	= (array)json_decode($oldMeta, true);
			}else{
				$oldMeta	= [];
			}
		}
		if($oldMeta != $event){
			//store meta in db
			update_metadata( 'post', $this->postId, 'eventdetails', json_encode($event));
		}

		/**
		 * events are created using the
		 * add_action( 'added_post_meta', __NAMESPACE__.'\createEvents', 10, 4);
		 * add_action( 'updated_postmeta', __NAMESPACE__.'\createEvents', 10, 4);
		 * hooks
		 */

	}

	/**
	 * Creatres a new event in db if it does not exist already
	 * @param  	string  $startdate		The startdate of the event
	*/
	protected function maybeCreateRow($startdate){
		global $wpdb;
		
		//check if form row already exists
		if(!$wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `post_id` = '{$this->postId}' AND startdate = '$startdate'")){
			$wpdb->insert(
				$this->tableName,
				array(
					'post_id'			=> $this->postId,
					'startdate'			=> $startdate
				)
			);
		}
	}
}
