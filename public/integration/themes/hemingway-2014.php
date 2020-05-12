<?php

add_action( 'calendarp_after_sidebar', 'calendarp_hemingway_after_sidebar' );
function calendarp_hemingway_after_sidebar() {
	?>
		<div class="clear"></div>
	</div><!-- .wrapper -->
	<?php
}
add_action( 'calendarp_before_content_event', 'calendarp_hemingway_before_content_event' );
function calendarp_hemingway_before_content_event() {
	?>
		<div class="post">
	<?php
}
add_action( 'calendarp_after_content_event', 'calendarp_hemingway_after_content_event' );
function calendarp_hemingway_after_content_event() {
	?>
		</div><!-- .post -->
	<?php
}

function calendarp_before_content() {
	?>
	<div class="wrapper section-inner">
		<div class="content left">
			<div class="posts">
				
	<?php
}

function calendarp_after_content() {
	?>
			</div><!-- .posts -->

			<div class="clear"></div>
		</div><!-- .content left -->
	<?php
}
