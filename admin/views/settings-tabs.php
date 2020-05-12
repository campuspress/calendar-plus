<h2 class="nav-tab-wrapper">
	<?php foreach ( $tabs as $key => $label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', $key ) ); ?>" class="nav-tab <?php echo $current_tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
	<?php endforeach; ?>
</h2>
