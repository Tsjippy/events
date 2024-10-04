<?php
namespace SIM\EVENTS;
use SIM;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_style('sim_events_css');

if(!isset($skipHeader) || !$skipHeader){
	get_header(); 
}
?>
	<div id="primary" style="width:100vw;">
		<main>
			<?php
			while ( have_posts() ) :

				the_post();

				include(__DIR__.'/content.php');

			endwhile;
			?>
		</main>
		<?php SIM\showComments(); ?>
	</div>

<?php

if(!$skipFooter){
	get_footer();
}