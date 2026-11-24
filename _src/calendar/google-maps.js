/* global jQuery, google */

const gmapsReady = require('../common/gmaps-ready');

(function ($) {

	window.calendar_plus.libs.googleMapRenderer = {
		init: function () {
			let $mapCanvas = $('#map_canvas');
			if (!$mapCanvas.length) {
				return;
			}

			let self = this;
			gmapsReady(function () {
				self.drawMap($mapCanvas);
			});
		},
		drawMap: function (canvas) {
			let mapOptions = {
				center: new google.maps.LatLng(canvas.data('lat'), canvas.data('long')),
				zoom: canvas.data('zoom')
			};

			let jsCanvas = canvas.get(0);

			if (typeof google.maps.Map === 'function') {
				let map = new google.maps.Map(jsCanvas, mapOptions);

				let marker = new google.maps.Marker({
					position: new google.maps.LatLng(canvas.data('marker-lat'), canvas.data('marker-long')),
					map: map
				});
			}
		}
	};

})(jQuery);
