window.gm_authFailure = function (err) {
	document.getElementById('map_canvas').innerHTML = CalendarPlusi18n.gmaps_api_key_error;
};

(function ($) {
	window.CalendarPlusAdmin.views.GMap = Backbone.View.extend({
		canvas: false,
		searchBox: false,
		$mapOptionsFields: false,
		initialize: function () {
			this.canvas = document.getElementById('map_canvas');
			this.searchBox = document.getElementById('location-gmaps-search');
			this.$mapOptionsFields = $('.location-gmaps-map-options');
			this.listenTo(this.model, 'change', this.updateMapFields);
		},
		render: function () {
			if (typeof google.maps.Map === 'function') {
				let mapOptions = this.model.get('mapOptions');
				let markerOptions = this.model.get('markerOptions');

				// Create the new map
				let map = new google.maps.Map(this.canvas, mapOptions);

				// Drop the marker
				this.dropMarker(markerOptions.position.lat(), markerOptions.position.lng(), map);

				// Move the search box inside the map
				map.controls[google.maps.ControlPosition.TOP_LEFT].push(this.searchBox);
				this.searchBox = new google.maps.places.SearchBox(this.searchBox);

				// Avoid submit form if the user presses return key
				$("#location-gmaps-search").keydown(function (e) {
					if (e.keyCode == 13) {
						e.preventDefault();
					}
				});

				this.model.set('center', map.center);
				this.model.set('zoom', map.getZoom());

				let self = this;
				google.maps.event.addListener(this.searchBox, 'places_changed', function () {
					//If the user searches another place, let's drop the new marker
					let markers;
					let places = self.searchBox.getPlaces();
					let marker = self.model.get('marker');

					if (marker === false)
						markers = [];
					else
						markers = [marker];

					if (places.length === 0)
						return;

					for (let i = 0; marker = markers[i]; i++)
						marker.setMap(null);

					// For each place, get the icon, place name, and location.
					markers = [];
					let bounds = new google.maps.LatLngBounds();
					let place;
					for (i = 0, place; place = places[i]; i++) {

						// Create a marker for each place.
						self.dropMarker(place.geometry.location.lat(), place.geometry.location.lng(), map);
						markers.push(marker);

						bounds.extend(place.geometry.location);

					}

					map.fitBounds(bounds);
				});

				google.maps.event.addListener(map, 'bounds_changed', function (event) {
					self.model.set('center', map.center);
					self.model.set('zoom', map.getZoom());
				});

			}
			return this;
		},
		dropMarker: function (lat, lng, map) {
			let marker = this.model.get('marker');
			let self = this;

			if (marker !== false) {
				// There's already a marker
				marker = this.moveMarker(lat, lng);
				marker.setMap(map);
			}
			else {
				// create a new marker
				marker = new google.maps.Marker({
					position: new google.maps.LatLng(lat, lng),
					draggable: true,
					map: map
				});

				marker.set("editing", false);
			}


			// Marker events
			google.maps.event.addListener(marker, 'drag', function (event) {
				self.moveMarker(event.latLng.lat(), event.latLng.lng(), map);
			});


			this.model.set('marker', marker);
			this.model.set('center', marker.map.center);
			this.model.set('zoom', marker.map.getZoom());

		},
		moveMarker: function (lat, lng) {
			let marker = this.model.get('marker');
			marker.setPosition(new google.maps.LatLng(lat, lng));
			this.model.set('marker', marker);

			// If we move the model a little changes are not detected by Backbone
			this.updateMapFields();

			return marker;

		},
		updateMapFields: function () {
			let marker = this.model.get('marker');
			let center = this.model.get('center');
			let zoom = this.model.get('zoom');
			let address = this.model.get('address');

			if (marker !== false) {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-marker-lat]"]').first().val(marker.getPosition().lat());
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-marker-long]"]').first().val(marker.getPosition().lng());
			}
			else {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-marker-lat]"]').first().val('');
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-marker-long]"]').first().val('');
			}
			if (center !== false) {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-lat]"]').first().val(center.lat());
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-long]"]').first().val(center.lng());
			}
			else {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-lat]"]').first().val('');
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-long]"]').first().val('');
			}
			if (zoom !== false) {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-zoom]"]').first().val(zoom);
			}
			else {
				this.$mapOptionsFields.find('[name="gmaps[location-gmaps-zoom]"]').first().val(2);
			}

			this.$mapOptionsFields.find('[name="gmaps[location-gmaps-address]"]').first().val(address);
		}
	});
})(jQuery);
