<?php
namespace SIM\EVENTS;
use SIM;

const MODULE_VERSION		= '7.0.43';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module add a custom posttype named event.<br>
		This creates a possibility to add a post with a date, time, location and organizer.<br>
		It also allows the creation of birthdays and anniversaries.<br>
		A calendar displaying all events is accesable at <a href="<?php echo site_url('events');?>"><?php echo site_url('events');?></a>.<br>
		<br>
		It also adds the possibility for schedules: a mealschedule or orientantion schedule or other.<br>
		Add it to any page using the shortcode <code>[schedules]</code>
	</p>

	<p>
		<strong>Auto created page:</strong><br>
		<a href='<?php echo home_url('/events');?>'>Calendar</a><br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<label for="freq">How often should we check for expired events?</label>
	<br>
	<select name="freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['freq']);
		?>
	</select>
	<br>
	<br>
	<label>Minimum age of events before they get removed:<br></label>
	<select name="max_age">
		<option value=''>---</option>
		<?php
			$strings	= [
				'1 day',
				'1 week',
				'1 month',
				'3 months',
				'1 year'
			];

			foreach($strings as $string){
				$selected	= '';
				if($settings["max_age"] == $string){
					$selected	= 'selected=selected';
				}

				echo "<option value='$string' $selected>$string</option>";
			}
		?>
	</select>

	<br>
	<br>
	<h4>Default picture for birthdays</h4>
	<?php
	SIM\pictureSelector('birthday_image', 'Birthday', $settings);
	?>
	<br>
	<br>
	<h4>Default picture for anniversaries</h4>
	<?php
	SIM\pictureSelector('anniversary_image', 'Anniversary', $settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_functions', function($functionHtml, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $functionHtml;
	}

	global $wpdb;
    
    $query  	= "SELECT * FROM `{$wpdb->prefix}sim_events` INNER JOIN $wpdb->posts ON post_id = $wpdb->posts.ID WHERE $wpdb->posts.post_author NOT IN (SELECT ID FROM $wpdb->users)";

    $orphans	= $wpdb->get_results($query);

	$query  	= "SELECT * FROM `{$wpdb->prefix}sim_events` WHERE post_id NOT IN (SELECT ID FROM $wpdb->posts)";

    $orphans	= array_merge($wpdb->get_results($query), $orphans);

	if(empty($orphans)){
		return '';
	}
	
	ob_start();
	?>
	<h4>Orphan events</h4>
	<p>
		There are <?php echo count($orphans);?> events found linked to a valid user or post in the database.
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
			foreach($orphans as $orphan){
				?>
				<tr>
					<th><?php echo $orphan->post_title;?></th>
					<th><?php echo $orphan->startdate;?></th>
					<th><?php echo $orphan->starttime;?></th>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<form method='POST'>
		<button type='submit' name='delete-orphans'>Remove these events</button>
	</form>

	<?php
	return ob_get_clean();
}, 10, 2);

add_action('sim_module_actions', function(){
	global $wpdb;
	if(isset($_POST['delete-orphans'])){
		$query  	= "DELETE `{$wpdb->prefix}sim_events` FROM `{$wpdb->prefix}sim_events` INNER JOIN $wpdb->posts ON post_id = $wpdb->posts.ID WHERE $wpdb->posts.post_author NOT IN (SELECT ID FROM $wpdb->users)";
    	$wpdb->query($query);
	}

});

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	$events	= new Events();
	$events->createEventsTable();

	$schedules	= new Schedules();
	$schedules->createDbTable();

	scheduleTasks();

	$options	= SIM\ADMIN\createDefaultPage($options, 'schedules_pages', 'Schedules', '[schedules]', $oldOptions);

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) {
    
    if (is_array(SIM\getModuleOption(MODULE_SLUG, 'schedules_pages')) && in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'schedules_pages', false))) {
        $states[] = __('Schedules page');
    }

    return $states;
}, 10, 2);