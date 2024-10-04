<?php
namespace SIM\EVENTS;
use SIM;

/**
 * The content of an event.
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="cat_card inside-article">
		<?php do_action( 'sim_before_content' );?>
		<div class='entry-content'>
			<div class="description event">
				<?php
				displayEventCategories();
				
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="page-links">Pages:',
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<?php
			displayEventMeta();
			?>
		</div>
	</div>
</article>

<?php
function displayEventCategories(){
	$baseUrl	= plugins_url('pictures', __DIR__);

	$categories = wp_get_post_terms(
		get_the_ID(),
		'events',
		array(
			'orderby'   => 'name',
			'order'     => 'ASC',
			'fields'    => 'id=>name'
		)
	);

	if(!empty($categories)){
		?>
		<span class='category eventmeta'>
			<?php
			echo "<img src='{$baseUrl}/event_category.png' alt='category' loading='lazy' class='event_icon'>";
			
			//First loop over the cat to see if any parent cat needs to be removed
			foreach($categories as $id=>$category){
				//Get the child categories of this category
				$children = get_term_children($id,'events');
				
				//Loop over the children to see if one of them is also in he cat array
				foreach($children as $child){
					if(isset($categories[$child])){
						unset($categories[$id]);
						break;
					}
				}
			}
			
			//now loop over the array to print the categories
			$lastKey	 = array_key_last($categories);
			foreach($categories as $id=>$category){
				//Only show the category if all of its subcats are not there
				$url = get_term_link($id);
				$category = ucfirst($category);
				echo "<a href='$url'>$category</a>";
				
				if($id != $lastKey){
					echo ', ';
				}
			}
			?>
		</span>
		<?php
		}
}

function displayEventMeta(){
	$events		= new DisplayEvents();
	$event		= $events->retrieveSingleEvent(get_the_ID());
	if(!$event){
		return;
	}
	
	$date		= $events->getDate($event);
	$time		= $events->getTime($event);
	$meta		= get_post_meta($event->ID, 'eventdetails', true);
	if(!is_array($meta)){
		if(!empty($meta)){
			$meta	= (array)json_decode($meta, true);
		}else{
			$meta	= [];
		}
	}
	$baseUrl	= plugins_url('pictures', __DIR__);

	?>
	<div class='event metas' style='margin-top:10px;'>
		<div class="event-meta">
			<div class="single-event-date">
				<?php
				echo "<img src='{$baseUrl}/date.png' alt='date' loading='lazy' class='event_icon'>";
				?>
				<h4>DATE</h4>
				<dl>
					<dd>
						<?php
						echo $date;
						?>
					</dd>
				</dl>
			</div>
			<div class="event-time">
				<?php
				echo "<img src='{$baseUrl}/time_red.png' alt='time' loading='lazy' class='event_icon'>";
				?>
				<h4 class="time">TIME</h4>
				<dl>
					<dd>
						<?php
						echo $time;
						?>
					</dd>
				</dl>
			</div>

			<?php 
			if(!empty($meta['repeat']['type'])){
			?>
				<div class="event-repeat">
					<?php
					echo "<img src='{$baseUrl}/repeat_small.png' alt='repeat' loading='lazy' class='event_icon'>";
					?>
					<h4 class="repeat">REPEATS</h4>
					<dl>
						<dd>
							<?php
							$type	= $meta['repeat']['type'];
							if($meta['repeat']['type'] == 'custom_days'){
								$type	= '';
								foreach($meta['repeat']['includedates'] as $date){
									$type	.= date('j F Y', strtotime($date)).'<br>';
								}
							}
							echo ucfirst($type);
							if(!empty($meta['repeat']['enddate'])){
								echo " until ".date('j F Y',strtotime($meta['repeat']['enddate']));
							}
							if(!empty($meta['repeat']['amount'])){
								$repeatAmount = $meta['repeat']['amount'];
								if($repeatAmount != 90){
									echo " for $repeatAmount times";
								}
							}
							?>
						</dd>
					</dl>
				</div>
			<?php 
			} 
			if(!empty($event->location)){
			?>
				<div class="event-location">
					<?php
					echo "<img src='{$baseUrl}/location_red.png' alt='' loading='lazy' class='event_icon'>";
					?>
					<h4>LOCATION</h4>
					<div class='location_details'>
						<?php
						echo $events->getLocationDetail($event);
						?>
					</div>
				</div>
			<?php
			}
			if(!empty($event->organizer)){
			?>
				<div class="event-organizer">
					<?php
					echo "<img src='{$baseUrl}/organizer.png' alt='' loading='lazy' class='event_icon'>";
					?>
					<h4>ORGANIZER</h4>
					<div class='author_details'>
						<?php
						echo $events->getAuthorDetail($event);
						?>
					</div>
				</div>
			<?php
			}
			echo $events->eventExportHtml($event);	
			?>
		</div>
	</div>
	<?php
}