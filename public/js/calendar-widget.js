( function( $ ) {

    function fetchCalendar( year, month, cb ) {
        $.ajax({
            url: CalendarPlusWidgeti18n.ajaxurl,
            type: 'GET',
            data: {
                action: 'calendarp_widget_fetch_calendar',
                calendar_year: year,
                calendar_month: month
            }
        })
            .always( function( response ) {
                var data = [];
                data.response = response;
                cb.call( this, data );
            });
    }

    function attachEvents() {
        var calendarWidgets = $( '.calendarp_calendar_wrap' );
        calendarWidgets.each( function( i, el ) {

            var $el = $( el );
            var $navLinks = $el.find( '.calendar-plus-nav > a' );
            var $backdrop = $el.find( '.calendarp-backdrop' );

            $navLinks.click( function( e ) {
                e.preventDefault();
                var $link = $( this );
                $backdrop.show();
                fetchCalendar( $link.data( 'year' ), $link.data( 'month' ), function( data ) {
                    $backdrop.hide();
                    if ( data.response ) {
                        $el.html( data.response );
                    }
                    attachEvents();
                });
            });
        });
    }

    attachEvents();
})( jQuery );
