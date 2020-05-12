import 'core-js';

Node.prototype.calAria = function ( ariaProp, value ) {
	const method = undefined === value ? 'getAttribute' : 'setAttribute';
	if ( true === value ) value = 'true';
	if ( false === value ) value = 'false';
	const aria = `aria-${ ariaProp }`;
	return this[method]( aria, value );
};

Node.prototype.calAriaIs = function( ariaProp ) {
	return 'true' === this.calAria( ariaProp );
};

(function () {
	'use strict';

	const calendar = document.querySelector('.calendar-plus-accessible-calendar');
	if (!calendar) return;

	Array.from(
		calendar.getElementsByClassName('toggle-event-details') || []
	).forEach( btn => {
		btn.addEventListener( 'click', e => {
			const event = btn.closest( '.event' );
			if ( ! event ) {
				return false;
			}

			const collapsed = btn.calAriaIs( 'expanded' );
			const callback = collapsed ? 'add' : 'remove';

			const details = event.getElementsByClassName( 'event-details' );
			if ( ! details ) {
				return false;
			}

			Array.from( details ).forEach( detail => {
				detail.calAria( 'hidden', collapsed );
			} );

			const title = event.getElementsByClassName( 'event-title' );
			const rpl = title
				? title[0].textContent
				: calendar.getAttribute( 'data-title-fallback' );
			console.log( rpl );

			btn.calAria( 'expanded', ! collapsed );
			btn.textContent = calendar.getAttribute(
				`data-${ collapsed ? 'show' : 'hide' }-details-text`
			).replace( /%EVENT%/, rpl || '' );
			event.classList[ callback ]( 'collapsed' );
		} );
	} );
})();
