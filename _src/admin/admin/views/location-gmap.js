const gmapsReady = require('../../../common/gmaps-ready');

window.gm_authFailure = function (err) {
	document.getElementById('map_canvas').innerHTML = CalendarPlusi18n.gmaps_api_key_error;
};

(function ($) {
	window.CalendarPlusAdmin.views.GMap = Backbone.View.extend({
		canvas: false,
		$mapOptionsFields: false,
		initialize: function () {
			this.canvas = document.getElementById('map_canvas');
			this.$mapOptionsFields = $('.location-gmaps-map-options');
			this.listenTo(this.model, 'change', this.updateMapFields);
		},
		render: function () {
			// The Maps API is not loaded when no API key is set.
			if (!CalendarPlusi18n.gmaps_enabled) {
				this.canvas.innerHTML = CalendarPlusi18n.gmaps_api_key_error;
				return this;
			}

			let self = this;
			gmapsReady(function () {
				self.initMap();
			});
			return this;
		},
		initMap: function () {
			let mapOptions = this.model.get('mapOptions');
			let markerOptions = this.model.get('markerOptions');

			// Create the new map
			let map = new google.maps.Map(this.canvas, $.extend({
				// Keep the top-left corner free for the search box.
				mapTypeControlOptions: {position: google.maps.ControlPosition.LEFT_BOTTOM}
			}, mapOptions));

			// Drop the marker
			if (markerOptions.position) {
				this.dropMarker(markerOptions.position.lat, markerOptions.position.lng, map);
			}

			// Place search (Places API New). Replaces the legacy places.SearchBox.
			let search = new google.maps.places.PlaceAutocompleteElement();
			search.id = 'location-gmaps-search';
			map.controls[google.maps.ControlPosition.TOP_LEFT].push(search);

			let self = this;
			search.addEventListener('gmp-select', function (e) {
				let place = e.placePrediction.toPlace();
				place.fetchFields({fields: ['location', 'viewport', 'addressComponents', 'formattedAddress']}).then(function () {
					self.dropMarker(place.location.lat(), place.location.lng(), map);
					self.fillAddressFields(place.addressComponents, place.formattedAddress);
					if (place.viewport) {
						map.fitBounds(place.viewport);
					} else {
						map.setCenter(place.location);
					}
				}).catch(function () {
					self.showMapError(CalendarPlusi18n.gmaps_places_error);
				});
			});
			search.addEventListener('gmp-error', function () {
				self.showMapError(CalendarPlusi18n.gmaps_places_error);
			});

			// Clicking the map drops the pin there. The pin is independent of
			// the address fields, which only search fills in.
			map.addListener('click', function (e) {
				self.dropMarker(e.latLng.lat(), e.latLng.lng(), map);
			});

			this.model.set('center', map.center);
			this.model.set('zoom', map.getZoom());

			google.maps.event.addListener(map, 'bounds_changed', function (event) {
				self.model.set('center', map.center);
				self.model.set('zoom', map.getZoom());
			});
		},
		showMapError: function (message) {
			if (!this.$mapError) {
				// Next to the fields search fills; above the map it would push the
				// map out of its fixed-height box.
				this.$mapError = $('<div class="notice notice-error inline"><p></p></div>');
				$('#location-gmaps-fields').prepend(this.$mapError);
			}
			this.$mapError.find('p').text(message);
		},
		/**
		 * The number goes before the street in some countries ("1600 Pennsylvania
		 * Ave") and after it in others ("Południowa 40"). Google's formatted
		 * address already uses the local order, so take the street line from it.
		 */
		streetLine: function (number, route, routeShort, formattedAddress) {
			let parts = (formattedAddress || '').split(',');
			for (let i = 0; i < parts.length; i++) {
				let part = parts[i].trim();
				if (number && part.indexOf(number) !== -1 && (part.indexOf(route) !== -1 || part.indexOf(routeShort) !== -1)) {
					return part;
				}
			}
			return (number + ' ' + route).trim();
		},
		/**
		 * Fill the visible address fields from a Places (New) result.
		 */
		fillAddressFields: function (components, formattedAddress) {
			let get = function (type, key) {
				for (let i = 0; i < (components || []).length; i++) {
					if (components[i].types.indexOf(type) !== -1) {
						return components[i][key || 'longText'] || '';
					}
				}
				return '';
			};
			let $fields = $('#location-gmaps-fields');
			let set = function (name, value) {
				$fields.find('[name="gmaps[location-' + name + ']"]').val(value).trigger('change');
			};

			set('address', this.streetLine(get('street_number'), get('route'), get('route', 'shortText'), formattedAddress));
			set('city', get('locality') || get('postal_town') || get('sublocality'));
			set('state', get('administrative_area_level_1'));
			set('postcode', get('postal_code'));
			set('country', get('country', 'shortText'));
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
