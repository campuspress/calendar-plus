( function() {
	// Ordinary links remain the fallback; this enhancement only changes presentation.
	document.addEventListener( 'click', function( event ) {
		const link = event.target.closest( '[data-calendarp-layout]' );
		if ( ! link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		const root = link.closest( '[data-calendarp-key]' );
		const layout = link.dataset.calendarpLayout;
		if ( ! root || ! [ 'grid', 'list' ].includes( layout ) ) {
			return;
		}
		event.preventDefault();
		root.dataset.layout = layout;
		root.querySelectorAll( '[data-calendarp-layout]' ).forEach( ( item ) => {
			if ( item.dataset.calendarpLayout === layout ) {
				item.setAttribute( 'aria-current', 'true' );
			} else {
				item.removeAttribute( 'aria-current' );
			}
		} );
		const name = 'cps_layout_' + root.dataset.calendarpKey;
		root.querySelectorAll( 'input[type="hidden"]' ).forEach( ( input ) => {
			if ( input.name === name ) {
				input.value = layout;
			}
		} );
		root.querySelectorAll( 'a[href]' ).forEach( ( item ) => {
			if ( ! item.hasAttribute( 'data-calendarp-layout' ) ) {
				const url = new URL( item.href, window.location.href );
				if ( url.origin === window.location.origin && url.searchParams.has( name ) ) {
					url.searchParams.set( name, layout );
					item.href = url.href;
				}
			}
		} );
	} );
}() );
