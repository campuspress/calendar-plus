<div id="location-type-selector">
	<div id="location-address-wrap">
		<p class="form-field">
			<label for="location-address"><?php _e( 'Address', 'calendar-plus' ); ?></label>
			<input type="text" name="standard[location-address]" id="location-address" value="<?php echo esc_attr( $location->address ); ?>">
		</p>
		<p class="form-field">
			<label for="location-address"><?php _e( 'City', 'calendar-plus' ); ?></label>
			<input type="text" class="small" name="standard[location-city]" id="location-city" value="<?php echo esc_attr( $location->city ); ?>">
		</p>
		<p class="form-field">
			<label for="location-state"><?php _e( 'State/Province', 'calendar-plus' ); ?></label>
			<input type="text" class="small" name="standard[location-state]" id="location-state" value="<?php echo esc_attr( $location->state ); ?>">
		</p>
		<p class="form-field">
			<label for="location-postcode"><?php _e( 'Postcode', 'calendar-plus' ); ?></label>
			<input type="text" class="small" name="standard[location-postcode]" id="location-postcode" value="<?php echo esc_attr( $location->postcode ); ?>">
		</p>
		<p class="form-field">
			<label for="location-address"><?php _e( 'Country', 'calendar-plus' ); ?></label>
			<?php calendarp_countries_dropdown( array( 'selected' => $location->country, 'name' => 'standard[location-country]' ) ); ?>
		</p>
	</div>
</div>

<?php do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $location ); ?>

<?php wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' ); ?>


<script type="text/template" id="location-type-selector-template">
	<div id="location-type-selector-controls">
		<a href="javascript:void(0);" class="location-selector" id="location-selector-standard" data-type="standard"><span class="dashicons dashicons-admin-post"></span> <?php _e( 'Add my own location', 'calendar-plus' ); ?>
		</a>
		<a href="javascript:void(0);" class="location-selector" id="location-selector-gmaps" data-type="gmaps"><span class="dashicons dashicons-location-alt"></span> <?php _e( 'Google Map', 'calendar-plus' ); ?>
		</a>
	</div>

	<div class="clear"></div>
	<div id="location-standard" class="location-selector-tab hidden">
		<div id="location-address-editor"></div>
	</div>

	<div id="location-gmaps" class="location-selector-tab hidden">
		<div class="location-gmaps-map-options">
			<input type="hidden" name="gmaps[location-gmaps-lat]" value="" />
			<input type="hidden" name="gmaps[location-gmaps-long]" value="" />
			<input type="hidden" name="gmaps[location-gmaps-marker-lat]" value="" />
			<input type="hidden" name="gmaps[location-gmaps-marker-long]" value="" />
			<input type="hidden" name="gmaps[location-gmaps-zoom]" value="" />
		</div>

		<div id="map_canvas" style="width: 50%; height: 500px;float:left"></div>
		<div id="location-gmaps-fields" style="margin-left:3%; height: 100%;width:46%;float:left">
			<p>
				<label for="location-address"><?php _e( 'Address', 'calendar-plus' ); ?></label>
				<input type="text" class="large-text" name="gmaps[location-address]" value="<?php echo esc_attr( $location->address ); ?>" />
			</p>
			<p>
				<label for="location-city"><?php _e( 'City', 'calendar-plus' ); ?></label>
				<input type="text" class="large-text" name="gmaps[location-city]" value="<?php echo esc_attr( $location->city ); ?>" />
			</p>
			<p>
				<label for="location-state"><?php _e( 'State/Province', 'calendar-plus' ); ?></label>
				<input type="text" class="large-text" name="gmaps[location-state]" value="<?php echo esc_attr( $location->state ); ?>" />
			</p>
			<p>
				<label for="location-postcode"><?php _e( 'Postcode', 'calendar-plus' ); ?></label><br />
				<input type="text" name="gmaps[location-postcode]" value="<?php echo esc_attr( $location->postcode ); ?>" />
			</p>
			<p>
				<label for="location-country"><?php _e( 'Country', 'calendar-plus' ); ?></label><br />
				<?php calendarp_countries_dropdown( array( 'selected' => $location->country, 'name' => 'gmaps[location-country]' ) ); ?>
			</p>
		</div>
		<div class="clear"></div>
	</div>


	<input type="hidden" name="location-type" value="<?php echo esc_attr( $location->location_type ); ?>" />
</script>


<script>
	jQuery(document).ready(function ($) {

		<?php if ( is_numeric( $map_options['lat'] ) && is_numeric( $map_options['long'] ) && is_numeric( $map_options['zoom'] ) ) : ?>
		var mapOptions = {
			center: { lat: <?php echo (float) $map_options['lat']; ?>, lng: <?php echo (float) $map_options['long']; ?> },
			zoom: <?php echo (int) $map_options['zoom']; ?>,
			mapTypeId: 'roadmap',
		};
		<?php else : ?>
		var mapOptions = {
			center: { lat: 0, lng: 0 },
			zoom: 1,
			mapTypeId: 'roadmap',
		};
		<?php endif; ?>


		<?php if ( is_numeric( $map_options['marker-lat'] ) && is_numeric( $map_options['marker-long'] ) ) : ?>
		var markerOptions = {
			position: { lat: <?php echo (float) $map_options['marker-lat']; ?>, lng: <?php echo (float) $map_options['marker-long']; ?> },
		};
		<?php else : ?>
		var markerOptions = {
			position: null,
		};
		<?php endif; ?>


		var locationSelector = new CalendarPlusAdmin.models.LocationSelector({
			mapOptions: mapOptions,
			markerOptions: markerOptions
		});
		var locationSelectorView = new CalendarPlusAdmin.views.LocationSelector({model: locationSelector});

		$('#location-type-selector').append(locationSelectorView.render().el);
		locationSelector.set('type', '<?php echo $location->location_type; ?>');

		var address_fields = $('#location-address-wrap').detach();
		$("#location-address-editor").append(address_fields.first());

	});
</script>
