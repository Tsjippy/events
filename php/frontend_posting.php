<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_frontend_posting_modals', function($types){
	$types[]	= 'event';
	return $types;
});

add_action('init', function(){
	SIM\registerPostTypeAndTax('event', 'events');
	add_action('sim_frontend_post_before_content', __NAMESPACE__.'\eventSpecificFields');
	add_action('sim_frontend_post_content_title', __NAMESPACE__.'\eventTitle');
	
	add_filter(
		'widget_categories_args',
		function ( $catArgs) {
			//if we are on a events page, change to display the event types
			if(is_tax('events') || is_page('event') || get_post_type()=='event'){
				$catArgs['taxonomy'] 		= 'events';
				$catArgs['hierarchical']	= true;
				$catArgs['hide_empty'] 		= false;
			}
			
			return $catArgs;
		}
	);
}, 999);

function eventTitle($postType){
	$class = 'property event';
	if($postType != 'event'){
		$class .= ' hidden';
	}
	
	echo "<label class='$class' name='event_content_label'>";
		echo '<h4>Describe the event</h4>';
	echo "</label>";
}

function eventSpecificFields($frontEndContent){
	$categories	= get_categories( array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'taxonomy'=> 'events',
		'hide_empty' => false,
	) );
	
	$frontEndContent->showCategories('event', $categories);
	
	$eventDetails	= get_post_meta($frontEndContent->postId, 'eventdetails', true);
	if(!is_array($eventDetails)){
		if(!empty($eventDetails)){
			$eventDetails	= (array)json_decode($eventDetails, true);
		}else{
			$eventDetails	= [];
		}
	}
	
	?>
	<br>
	<div class="property event <?php if($frontEndContent->postType != 'event'){echo 'hidden';} ?>">
		<label>
			<input type='checkbox' name='event[allday]' value='allday' <?php if(!empty($eventDetails['allday'])){echo 'checked';}?>>
			All day event
		</label>
	
		<label name="startdate_label">
			<h4>Startdate</h4>
			<input type='date'						name='event[startdate]' value='<?php if(isset($eventDetails['startdate'])){echo $eventDetails['startdate'];} ?>' required>
			<input type='time' class='eventtime<?php if(!empty($eventDetails['starttime'])){echo " hidden";}?>'	name='event[starttime]' value='<?php echo $eventDetails['starttime']; ?>' required>
		</label>
		
		<label name="enddate_label" <?php if(!empty($eventDetails['allday'])){echo "class='hidden'";}?>>
			<h4>Enddate</h4>
			<input type='date'						name='event[enddate]' value='<?php if(isset($eventDetails['enddate'])){echo $eventDetails['enddate'];} ?>' required>
			<input type='time' class='eventtime'	name='event[endtime]' value='<?php if(isset($eventDetails['endtime'])){echo $eventDetails['endtime'];} ?>' required>
		</label>

		<label name="location">
			<h4>Location</h4>
			<input type='hidden' class='datalistvalue'	name='event[location_id]' 	value='<?php if(isset($eventDetails['location_id'])){echo $eventDetails['location_id'];} ?>'>
			<input type='text'							name='event[location]' 		value='<?php if(isset($eventDetails['location'])){echo $eventDetails['location'];} ?>' list="locations">
			<datalist id="locations">
				<?php
				$locations = get_posts(
					array(
						'post_type'      	=> 'location',
						'posts_per_page'	=> -1,
						'orderby'			=> 'title',
						'order' 			=> 'ASC'
					)
				);
				foreach($locations as $location){
					echo "<option data-value='{$location->ID}' value='{$location->post_title}'></option>";
				}
				?>
			</datalist>
		</label>

		<label name="organizer">
			<h4>Organizer</h4>
			<input type='hidden' class='datalistvalue'	name='event[organizer_id]'	value='<?php if(isset($eventDetails['organizer_id'])){echo $eventDetails['organizer_id'];} ?>'>
			<input type='text'							name='event[organizer]'		value='<?php if(isset($eventDetails['organizer'])){echo $eventDetails['organizer'];} ?>' list="users">
			<datalist id="users">
				<?php
				foreach(SIM\getUserAccounts(false, true, true) as $user){
					echo "<option data-value='{$user->ID}' value='{$user->display_name}'></option>";
				}
				?>
			</datalist>
		</label>
		
		<label class='block'>
			<button class='button' type='button' name='enable_event_repeat'>
				Repeat this event
			</button>
			<input type='hidden' name='event[isrepeated]' value='<?php if(isset($eventDetails['isrepeated'])){echo $eventDetails['isrepeated'];}?>'>
		</label>
		
		<?php
		displayRepetitionParameters($eventDetails);
		?>
	</div>
	<?php
}

function displayRepetitionParameters($eventDetails){
	$repeatParam['type']		= '';
	$repeatParam['datetype']	= '';

	if(isset($eventDetails['repeat'])){
		$repeatParam	= $eventDetails['repeat'];
	}

	$list	=	array(
		'daily'			=> 'Daily',
		'weekly'		=> 'Weekly',
		'monthly'		=> 'Monthly',
		'yearly'		=> 'Yearly',
		'custom_days'	=> 'Custom Days',
	);

	$dayName	= 'monday';
	$occurence	= 'first';
	if(!empty($eventDetails)){
		$dayName	= strtolower(date('l', strtotime($eventDetails['startdate'])));
		$dayNum		= explode('-', $eventDetails['startdate'])[2];
		$occurence	= 1;
		while($dayNum - 7 >= 0){
			$occurence++;
			$dayNum	= $dayNum - 7;
		}

		$occurence		= SIM\numberToWords($occurence);

		$hideLast		= 'hidden';
		$hideLastDay	= 'hidden';

		// Selected date is the last day of the week of the month
		if(!empty($eventDetails['startdate']) && strtotime("last $dayName of this month") == strtotime($eventDetails['startdate'])){
			$hideLast	= '';

			if(strtotime($eventDetails['startdate']) == strtotime("last day of this month")){
				$hideLastDay	= '';
			}
		}
	}

	?>
	<fieldset class='repeat_wrapper <?php if(empty($eventDetails['isrepeated'])){echo 'hidden';}?>'>
		<legend><span class='text'>Repeat parameters</span></legend>
		<h4>Select repeat type:</h4>
		<select name="event[repeat][type]">
			<option value="">---</option>
			<?php
				foreach($list as $key=>$item){
					if($repeatParam['type'] == $key){
						$selected	= 'selected=selected';
					}else{
						$selected	= '';
					}
					echo "<option value=$key $selected>$item</option>";
				}
			?>
		</select>

		<div class='repeatdatetype <?php echo $repeatParam['type'] == 'monthly' || $repeatParam['type'] == 'yearly' ? 'hide' : 'hidden';?>'>
			<h4>Select repeat pattern:</h4>
			<!-- Same day number of the month -->
			<label class='optionlabel'>
				<input type='radio' name='event[repeat][datetype]' value='samedate' <?php if($repeatParam['datetype'] == 'samedate'){echo 'checked';}?>>
				On the same day
			</label>

			<!-- Same Day of the week -->
			<label class='optionlabel'>
				<input type='radio' name='event[repeat][datetype]' value='patterned' <?php if($repeatParam['datetype'] == 'patterned'){echo 'checked';}?>>
				On the <span class='weekword'><?php echo $occurence;?></span> <span class='dayname'><?php echo $dayName;?></span> of the month
			</label>

			<!-- Last dayname of the month-->
			<label class='optionlabel last-dayname-of-month <?php echo $hideLast;?>'>
				<input type='radio' name='event[repeat][datetype]' value='last' <?php if($repeatParam['datetype'] == 'last'){echo 'checked';}?>>
				On the last <span class='dayname'><?php echo $dayName;?></span> of the month
			</label>

			<!-- On the very last day of the month-->
			<label class='optionlabel last-day-of-month <?php echo $hideLastDay;?>'>
				<input type='radio' name='event[repeat][datetype]' value='lastday' <?php if($repeatParam['datetype'] == 'lastday'){echo 'checked';}?>>
				On the last day of the month
			</label>
		</div>
					
		<?php
		repetitionIntervalSettings($eventDetails);
		?>
		
		<div class="custom_dates_selector <?php echo $repeatParam['type'] == 'custom_days' ? 'hide' : 'hidden';?>">
			<h4> Specify repeat days</h4>
			<div class="clone_divs_wrapper">
				<?php
				if(!empty($eventDetails['repeat']['includedates']) && is_array($eventDetails['repeat']['includedates'])){
					$includeDates	= $eventDetails['repeat']['includedates'];
				}else{
					$includeDates	= [''];
				}
				
				foreach($includeDates as $index=>$includeDate){
					?>
					<div id="includedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
						<label>Include date <?php echo $index+1;?></label>
						<div class='buttonwrapper'>
							<input type="date" name="event[repeat][includedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $includeDate;?>">
							<button type="button" class="add button" style="flex: 1;">+</button>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		
		<h4>Stop repetition</h4>
		<label>
			<input type='radio' name='event[repeat][stop]' value='never' <?php if(empty($eventDetails['repeat']['stop']) || $eventDetails['repeat']['stop'] == 'never'){echo 'checked';}?>>
			Never
		</label><br>
		<div class='repeat_type_option'>
			<label>
				<input type='radio' name='event[repeat][stop]' value='date' <?php if(!empty($eventDetails['repeat']['stop']) && $eventDetails['repeat']['stop'] == 'date'){echo 'checked';}?>>
				On this date:
			</label><br>
			
			<label class='repeat_type_option_specifics <?php if(empty($eventDetails['repeat']['stop']) || $eventDetails['repeat']['stop'] != 'date'){echo 'hidden';}?>'>
				<input type='date' name='event[repeat][enddate]' value='<?php if(!empty($eventDetails['repeat']['enddate'])){echo $eventDetails['repeat']['enddate'];}?>'>
			</label>
		</div>

		<div class='repeat_type_option'>
			<label>
				<input type='radio' name='event[repeat][stop]' value='after' <?php if(!empty($eventDetails['repeat']['stop']) && $eventDetails['repeat']['stop'] == 'after'){echo 'checked';}?>>
				After this amount of repeats:<br>
			</label>
			
			<label class='repeat_type_option_specifics <?php if(empty($eventDetails['repeat']['stop']) || $eventDetails['repeat']['stop'] != 'after'){echo 'hidden';}?>'>
				<input type='number' name='event[repeat][amount]' value='<?php if(!empty($eventDetails['repeat']['amount'])){echo $eventDetails['repeat']['amount'];}?>'>
			</label>
		</div>
		
		<div>
			<h4>Exclude dates from this pattern</h4>
			<div class="clone_divs_wrapper">
			<?php
			if(empty($eventDetails['repeat']['excludedates'])){
				$excludeDates	= [''];
			}else{
				$excludeDates	= (array)$eventDetails['repeat']['excludedates'];
			}
			
			foreach($excludeDates as $index=>$excludeDate){
				?>
				<div id="excludedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
					<label>Exclude date <?php echo $index+1;?></label>
					<div class='buttonwrapper'>
						<input type="date" name="event[repeat][excludedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $excludeDate;?>">
						<button type="button" class="add button" style="flex: 1;">+</button>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</fieldset>
	<?php
}

function repetitionIntervalSettings($eventDetails){
	if(empty($eventDetails['repeat'])){
		$repeatParam	= [
			'interval'	=> '',
			'datetype'	=> '',
			'type'		=> ''
		];
	}else{
		$repeatParam	= $eventDetails['repeat'];
	}
	$weekNames 		= ['First','Second','Third','Fourth','Fifth','Last'];

	if(empty($repeatParam['interval'])){
		$interval	= 1;
	}else{
		$interval	= $repeatParam['interval'];
	}

	if($repeatParam['datetype'] == 'samedate'){
		$samedate	= 'hide';
	}else{
		$samedate	= 'hidden';
	}

	?>
	<label class='repeatinterval <?php echo $samedate;?>'>
		<h4>Repeat every </h4>
		
		<input type='number' name='event[repeat][interval]' value='<?php echo $interval;?>'>
		<span id='repeattype'>days</span>
	</label>

	<!-- Weekly repetition options -->
	<div class="selector_wrapper weeks <?php echo $repeatParam['type'] == 'weekly' ? 'hide' : "hidden";?>">
		<h4 class='checkbox'>Select weeks(s) of the month this event should be repeated on</h4>
		<label class='selectall_wrapper optionlabel'>
			<input type='checkbox' class='selectall' name='allweekss' value='all'>Select all weeks<br>
		</label>

		<?php
		foreach($weekNames as $weekName){
			if(!empty($eventDetails['repeat']['weeks']) && is_array($eventDetails['repeat']['weeks']) && in_array($weekName, $eventDetails['repeat']['weeks'])){
				$checked = 'checked';
			}else{
				$checked = '';
			}
			echo "<label class='optionlabel'>";
				echo "<input type='checkbox' name='event[repeat][weeks][]' value='$weekName' $checked>$weekName";
			echo "</label>";
		}
		?>
	</div>

	<!-- Daily repetition options -->
	<div class="selector_wrapper days <?php echo $repeatParam['type'] == 'daily' ? 'hide' : "hidden";?>">
		<h4 class='checkbox'>Select day(s) this event should be repeated on</h4>
		<label class='selectall_wrapper optionlabel'>
			<input type='checkbox' class='selectall' name='alldays' value='all'>Select all days<br>
		</label>

		<?php
		foreach(['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $key=>$dayName){
			if(!empty($eventDetails['repeat']['weekdays']) && is_array($eventDetails['repeat']['weekdays']) && in_array($dayName, $eventDetails['repeat']['weekdays'])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}
			echo "<label class='optionlabel'>";
				echo "<input type='checkbox' name='event[repeat][weekdays][]' value='$key'$checked>$dayName";
			echo "</label>";
		}
		?>
	</div>
	<?php
}

add_filter('sim_signal_post_notification_message', function($excerpt, $post){
	if($post->post_type == 'event'){
		$events		= new DisplayEvents();
		$event		= $events->retrieveSingleEvent($post->ID);
		$startDate	= date(DATEFORMAT, strtotime($event->startdate));
		if($event->startdate == $event->enddate){
			$excerpt .= "\n\nDate: $startDate";
		}else{
			$enddate	 = date(DATEFORMAT, strtotime($event->enddate));
			$excerpt 	.= "\n\nStart date: $startDate";
			$excerpt 	.= "\nEnd date: $enddate";
		}
		if(!empty($event->starttime) && !empty($event->endtime)){
			$excerpt .= "\nTime: $event->starttime till $event->endtime";
		}

		if(!empty($event->location)){
			$excerpt .= "\nLocation: $event->location";
		}

		if(!empty($event->organizer)){
			if(is_numeric($event->organizer)){
				$event->organizer	= get_user_by('id', $event->organizer)->display_name;
			}
			$excerpt .= "\nOrganized by : $event->organizer";
		}
	}

	return $excerpt;
}, 10, 2);

add_action('sim_after_post_save', function($post, $frontEndPost){
	if($post->post_type != 'event'){
		return;
	}
	
    $events = new CreateEvents();
    $events->storeEventMeta($post, $frontEndPost->update);

	$frontEndPost->storeCustomCategories($post, 'events');
}, 1, 2);