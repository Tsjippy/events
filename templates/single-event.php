<?php
namespace TSJIPPY\EVENTS;
use TSJIPPY;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_style('tsjippy_events_css');

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
		<?php TSJIPPY\showComments(); ?>
	</div>

<?php

if(!$skipFooter){
	get_footer();
}