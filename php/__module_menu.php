<?php
namespace SIM\EVENTS;
use SIM;

const MODULE_VERSION		= '8.1.4';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_events_description', __NAMESPACE__.'\subMenuDescription');
function subMenuDescription($description){
	ob_start();
	?>
	<p>
		<strong>Auto created page:</strong><br>
		<a href='<?php echo home_url('/events');?>'>Calendar</a><br>
	</p>
	<?php

	return $description.ob_get_clean();
}

add_filter('sim_submenu_events_options', __NAMESPACE__.'\subMenuOptions', 10, 2);
function subMenuOptions($optionsHtml, $settings){
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

	return $optionsHtml.ob_get_clean();
}

add_filter('sim_module_events_functions', __NAMESPACE__.'\moduleFunctions');
function moduleFunctions($functionHtml){
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
	return $functionHtml.ob_get_clean();
}

add_action('sim_module_actions', __NAMESPACE__.'\moduleActions');
function moduleActions(){
	global $wpdb;
	if(isset($_POST['delete-orphans'])){
		$query  	= "DELETE `{$wpdb->prefix}sim_events` FROM `{$wpdb->prefix}sim_events` INNER JOIN $wpdb->posts ON post_id = $wpdb->posts.ID WHERE $wpdb->posts.post_author NOT IN (SELECT ID FROM $wpdb->users)";
    	$wpdb->query($query);
	}
}

add_filter('sim_module_events_after_save', __NAMESPACE__.'\moduleUpdated', 10, 3);
function moduleUpdated($options, $oldOptions){
	$events	= new Events();
	$events->createEventsTable();

	$schedules	= new Schedules();
	$schedules->createDbTable();

	scheduleTasks();

	$options	= SIM\ADMIN\createDefaultPage($options, 'schedules_pages', 'Schedules', '[schedules]', $oldOptions);

	return $options;
}

add_filter('display_post_states', __NAMESPACE__.'\postStates', 10, 2);
function postStates( $states, $post ) {
    
    if (is_array(SIM\getModuleOption(MODULE_SLUG, 'schedules_pages')) && in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'schedules_pages', false))) {
        $states[] = __('Schedules page');
    }

    return $states;
}