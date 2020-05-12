<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function calendarp_before_content() {
	?>
		<div id="main-content">
			<div class="container">
				<div id="content-area" class="with_sidebar with_sidebar_right clearfix">
	<?php
}

function calendarp_after_content() {
	echo '';
}

add_action( 'calendarp_sidebar', function() {
	?>
    </div></div></div>
    <?php
}, 50 );


add_action( 'calendarp_before_events_loop', function() {
	?>
	<div class="et_pb_extra_column_main">
	<?php
});


add_action( 'calendarp_after_events_loop', function() {
	?>
	</div>
	<?php
});


add_action( 'wp_head', function() {
	?>
	<style>
		.calendarp {
			width:100%;
		}
		.calendarp > .et_pb_extra_column_main {
			background:white;
			margin-bottom:0.5rem;
		}
	</style>
	<?php
});
