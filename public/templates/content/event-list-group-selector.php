<?php
$active_group = isset( $_GET['events'] ) ? $_GET['events'] : 'future';
$groups = array(
	'future'    => __( 'Upcoming Events' ),
	'past'      => __( 'Past Events' ),
)
?>
<div class="calendarp">
	<div class="calendarp_calendar agenda-minified-calendar">
		<div class="calendarp-group-selector row">
			<ul>
				<?php
				foreach( $groups as $group_name => $label ) {
					?>
					<li<?php echo $active_group === $group_name ? ' class="active"' : ''; ?>>
						<?php
						$url = add_query_arg( array( 'events' => $group_name ), get_the_permalink() );
						?>
						<a href="<?php echo $url; ?>"><?php echo $label; ?></a>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
	</div>
</div>