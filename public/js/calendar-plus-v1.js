(function( $ ) {
	'use strict';

	window.calendar_plus = {
		libs: {}
	};

})( jQuery );

(function( $ ) {

	window.calendar_plus.libs.datepicker = {
		init: function() {
			var datepickers = $( '.calendarp-datepicker' );
			if ( datepickers.length ) {
				datepickers.datepicker(
					{
						dateFormat: 'yy-mm-dd'
					}
				);
			}

		}
	};

})( jQuery );

(function( $ ) {

	window.calendar_plus.libs.googleMapRenderer = {
		init: function() {
			var $mapCanvas = $( '#map_canvas' );
			if ( $mapCanvas.length ) {
				this.drawMap( $mapCanvas );
			}

		},
		drawMap: function( canvas ) {
			var mapOptions = {
				center: new google.maps.LatLng( canvas.data( 'lat' ), canvas.data( 'long' ) ),
				zoom: canvas.data( 'zoom' )
			};

			var jsCanvas = canvas.get( 0 );

			if ( typeof google.maps.Map === 'function' ) {
				var map = new google.maps.Map( jsCanvas, mapOptions );

				var marker = new google.maps.Marker({
					position: new google.maps.LatLng( canvas.data( 'marker-lat' ), canvas.data( 'marker-long' ) ),
					map: map,
					title: 'Hello World!'
				});
			}
		}
	};

})( jQuery );

(function( $ ) {

	window.calendar_plus.libs.calendar = {
		$calendars: [],
		current_day: [],
		init: function() {
			var $calendars = $( '.calendarp_calendar' );
			if ( $calendars.length ) {
				$calendars.each( this.bindEvents );
			}

			var calendarpForm = $( '#calendarp-controls-form' );
			var calendarpHeading = $( '.calendarp_calendar .heading-row' );

			calendarpHeading.css( 'float', 'left' );
			calendarpForm.remove().css( 'float', 'right' ).insertAfter( calendarpHeading ).show();
			var mode = $( 'input[name="calendar_mode"]' ).val();
			if ( mode != 'month' && mode != 'agenda' ) {
				this.fillWeekCalendar();
            }

		},
		bindEvents: function(i) {
			var calendar = $( this );
			calendar.data( 'id','calendarp-' + i );
			window.calendar_plus.libs.calendar.$calendars[i] = calendar;
			calendar.find( '.with-events .day-label' ).on( 'hover', window.calendar_plus.libs.calendar.toggleEventsForDay );

			$( '#calendar_mode' ).change( function() {
				$( this ).closest( 'form' ).submit();
			});
		},
		toggleEventsForDay: function() {

			$( '.day-label' ).removeClass( 'selected' );
			var slotsContainer = $( '#calendar-slots' );

			var day = $( this );
			var dayOfMonth = day.text();
			var calendarId = day.closest( '.calendarp_calendar' ).data( 'id' );
			if ( window.calendar_plus.libs.calendar.current_day[ calendarId ] === dayOfMonth ) {
				return;
			}

			window.calendar_plus.libs.calendar.current_day[ calendarId ] = dayOfMonth;

			day.addClass( 'selected' );
			var daytime_id = day.parent().attr( 'id' );
			var tableRow = day.closest( '.cp-week-row' );

			slotsContainer.slideUp();
			slotsContainer.find( '.event' ).hide();
			slotsContainer.find( '.event-date-id-' + daytime_id ).show();
			slotsContainer.hide().insertAfter( tableRow );
			slotsContainer.fadeIn( 'fast', function() {
				$( this ).clearQueue();

			});

		},
		fillWeekCalendar: function() {
			var events = $( '#calendar-slots .event' );
			events.each( function( i ) {
				var el = $( this );
				var dateClass = el.data( 'date' );
				el.detach().appendTo( $( '#' + dateClass ).addClass( 'with-events' ) );
			});
		}
	};

})( jQuery );



jQuery( document ).ready(function($) {
	for ( var i in calendar_plus.libs ) {
		if ( typeof calendar_plus.libs[ i ].init === 'function' ) {
			calendar_plus.libs[ i ].init();
        }
	}
});
