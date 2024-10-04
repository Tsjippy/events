<?php
/**
 * The layout specific for the page with the slug 'event' i.e. sim.org/event.
 * Displays all the post of the event type
 * 
 */

namespace SIM\EVENTS;
use SIM;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
wp_enqueue_style('sim_events_css');
wp_enqueue_script('sim_event_script');

global $wp_query;
if($wp_query->is_embed){
	showCalendar();
}else{
	if(!isset($skipHeader) || !$skipHeader){
		get_header();
	}
	?>
	<div id="primary" style="width:100%;">
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<?php showCalendar(); ?>
		</main>
	</div>
	
	<?php
	
	if(!$skipFooter){
		get_footer();
	}
}

function showCalendar(){
	
	if(isset($_GET['view'])){
		$view 		= $_GET['view'];
	}else{
		$view = 'month';
	}

	$events	= new DisplayEvents();

	global $wp_query;
	$cat	= [];
	if(!empty($wp_query->queried_object_id)){
		$cat	= [$wp_query->queried_object_id];
	}

	while ( have_posts() ) :
		the_post();
	endwhile;
	?>
	<div class="calendar-wrap">
		<?php
		do_action('sim_before_archive', 'event');
		?>
		<button id='add_calendar' class='button small'>Add this calendar to your personal agenda</button>
		<div id='calendaraddingoptions' class='hidden'>
			<p>
				To add the calendar to your sim.org calendar go to <a href='https://outlook.office.com/calendar/addcalendar' target="_blank">email.sim.org</a>.<br>
				Then add <code class='calendarurl'>webcal://<?php echo SITEURLWITHOUTSCHEME;?>/public_calendar/sim_events.ics?id=<?php echo get_current_user_id();?></code> in the 'subscribe via internet' screen
			</p>
			<p>
				To add the calendar to your gmail calendar go to <a href='https://calendar.google.com/calendar/u/1/r/settings/addbyurl' target="_blank">calendar.google.com</a>.<br>
				Then paste this url in the agenda-url field <code class='calendarurl'>webcal://<?php echo SITEURLWITHOUTSCHEME;?>/public_calendar/sim_events.ics?id=<?php echo get_current_user_id();?></code>.
			</p>
			<p>
				To add the calendar to outlook click on 'open agenda'->'from the internet'.<br>
				Then paste this url in the agenda field <code class='calendarurl'>webcal://<?php echo SITEURLWITHOUTSCHEME;?>/public_calendar/sim_events.ics?id=<?php echo get_current_user_id();?></code>.
			</p>
		</div>
		<div class="search-form">
			<div class="date-selector">
				<div class="date-search">
					<?php
					$baseUrl	= plugins_url('pictures', __DIR__);
					echo "<img src='{$baseUrl}/date.png' alt='time' loading='lazy' class='event_icon'>";
					?>
					<select class='month_selector<?php if($view=='week'){echo ' hidden';}?>' placeholder="Select month">
						<?php
						for ($m=1; $m<13; $m++){
							$monthName	= date("F", mktime(0, 0, 0, $m, 10));
							$monthNum	= sprintf("%02d", $m);
							if(isset($_GET['month']) && $_GET['month'] == $m){
								$selected	= ' selected=selected';
							}else{
								$selected	= '';
							}
							echo "<option value='$monthNum'$selected>$monthName</option>";
						}
						?>
					</select>
					<select class='week_selector<?php if($view!='week'){echo ' hidden';}?>' placeholder="Select week">
						<?php
						for ($w=1;$w<=53;$w++){
							if($_GET['week'] == $w){
								$selected	= ' selected=selected';
							}else{
								$selected	= '';
							}
							echo "<option value='$w'$selected>Week $w</option>";
						}
						?>
					</select>
					<select class="year_selector" placeholder="Select year">
						<?php
						$start 	= date('Y');
						$end	= date("Y",strtotime('+10 year'));
						for ($y=$start;$y<$end;$y++){
							if($y == $_GET['year']){
								$selected = 'selected=selected';
							}else{
								$selected = '';
							}
							echo "<option value='$y' $selected>$y</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div class="view-style">
				<span class="viewselector<?php if($view=='month'){echo ' selected';}?>" data-type="monthview">Monthly</span>
				<span class="viewselector<?php if($view=='week'){echo ' selected';}?>" data-type="weekview">Weekly</span>
				<span class="viewselector<?php if($view=='list'){echo ' selected';}?>" data-type="listview">List</span>
			</div>
		</div>

		<div id='monthview' class='calendarview<?php if($view!='month'){echo ' hidden';}?>'>
			<?php
			echo $events->monthCalendar($cat);
			?>
		</div>
		<div id='weekview' class='calendarview<?php if($view!='week'){echo ' hidden';}?>'>
			<?php
			echo $events->weekCalendar($cat);
			?>
		</div>
		<div id='listview' class='calendarview<?php if($view!='list'){echo ' hidden';}?>'>
			<?php
			echo $events->listCalendar($cat);
			?>
		</div>		
	</div>
	<?php
}