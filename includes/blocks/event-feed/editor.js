( function() {
	const el = wp.element.createElement;
	const { useEffect, useState } = wp.element;
	const { __ } = wp.i18n;
	const { PanelBody, SelectControl, ToggleControl, RangeControl, TextControl, ComboboxControl, Button, Notice } = wp.components;

	function Terms( { taxonomy, value, onChange, label } ) {
		const [ search, setSearch ] = useState( '' );
		const [ page, setPage ] = useState( 1 );
		const [ records, setRecords ] = useState( {} );
		const [ suggestions, setSuggestions ] = useState( [] );
		const [ more, setMore ] = useState( false );
		const [ error, setError ] = useState( false );
		const ids = value.split( ',' ).filter( Boolean );
		useEffect( () => {
			let active = true;
			const timer = setTimeout( () => {
				wp.apiFetch( { path: '/wp/v2/' + taxonomy + '?per_page=20&page=' + page + '&search=' + encodeURIComponent( search ) } ).then( ( terms ) => {
					if ( ! active ) {
						return;
					}
					setError( false );
					setMore( terms.length === 20 );
					setRecords( ( previous ) => ( { ...previous, ...Object.fromEntries( terms.map( ( term ) => [ term.id, term ] ) ) } ) );
					setSuggestions( ( previous ) => page === 1 ? terms : [ ...previous, ...terms ] );
				} ).catch( () => {
					if ( active ) {
						setError( true );
					}
				} );
			}, 250 );
			return () => {
				active = false; clearTimeout( timer );
			};
		}, [ taxonomy, page, search ] );
		useEffect( () => {
			let active = true;
			if ( value ) {
				wp.apiFetch( { path: '/wp/v2/' + taxonomy + '?per_page=100&include=' + encodeURIComponent( value ) } ).then( ( terms ) => {
					if ( active ) {
						setRecords( ( previous ) => ( { ...previous, ...Object.fromEntries( terms.map( ( term ) => [ term.id, term ] ) ) } ) );
					}
				} ).catch( () => {
					if ( active ) {
						setError( true );
					}
				} );
			}
			return () => {
				active = false;
			};
		}, [ taxonomy, value ] );
		return el( 'div', {},
			el( ComboboxControl, { label, value: null, options: suggestions.filter( ( term ) => ! ids.includes( String( term.id ) ) ).map( ( term ) => ( { value: String( term.id ), label: term.name } ) ), onFilterValueChange: ( input ) => {
				setSearch( input ); setPage( 1 );
			}, onChange: ( id ) => {
				if ( id && ids.length < 100 ) {
					onChange( [ ...new Set( [ ...ids, id ] ) ].join( ',' ) );
				}
			} } ),
			...ids.map( ( id ) => el( Button, { key: id, variant: 'secondary', onClick: () => onChange( ids.filter( ( item ) => item !== id ).join( ',' ) ) }, ( records[ id ]?.name || '#' + id ) + ' ×' ) ),
			more && page < 100 && el( Button, { variant: 'secondary', onClick: () => setPage( page + 1 ) }, __( 'Load more terms', 'calendar-plus' ) ),
			error && el( Notice, { status: 'warning', isDismissible: false }, __( 'Terms could not be loaded. Try searching again.', 'calendar-plus' ) ),
		);
	}

	wp.blocks.registerBlockType( 'calendar-plus/event-feed', {
		edit: function Edit( props ) {
			const { attributes: a, setAttributes: set, clientId } = props;
			const duplicateNavigation = wp.data.useSelect( ( selectStore ) => {
				const store = selectStore( 'core/block-editor' );
				const first = store.getClientIdsWithDescendants().find( ( id ) => store.getBlockAttributes( id )?.navigationId === a.navigationId );
				return Boolean( a.navigationId && first && first !== clientId );
			}, [ a.navigationId, clientId ] );
			useEffect( () => {
				if ( ! a.navigationId || duplicateNavigation ) {
					set( { navigationId: clientId } );
				}
			}, [ a.navigationId, clientId, set, duplicateNavigation ] );
			const select = ( key, label, options ) => el( SelectControl, { key, label, value: a[ key ], options, onChange: ( value ) => set( { [ key ]: value } ) } );
			const text = ( key, label ) => el( TextControl, { key, label, value: a[ key ], onChange: ( value ) => set( { [ key ]: value } ) } );
			const toggle = ( key, label ) => el( ToggleControl, { key, label, checked: a[ key ], onChange: ( value ) => set( { [ key ]: value } ) } );
			const range = ( key, label, min, max ) => el( RangeControl, { key, label, value: a[ key ], min, max, onChange: ( value ) => set( { [ key ]: value } ) } );
			return el( 'div', wp.blockEditor.useBlockProps(),
				el( wp.blockEditor.InspectorControls, {},
					el( PanelBody, { title: __( 'Events', 'calendar-plus' ) },
						select( 'view', __( 'Show', 'calendar-plus' ), [ { value: 'upcoming', label: __( 'Upcoming and ongoing', 'calendar-plus' ) }, { value: 'past', label: __( 'Past', 'calendar-plus' ) }, { value: 'all', label: __( 'All', 'calendar-plus' ) }, { value: 'range', label: __( 'Date range', 'calendar-plus' ) } ] ),
						a.view === 'range' && text( 'from', __( 'Start date (YYYY-MM-DD)', 'calendar-plus' ) ),
						a.view === 'range' && text( 'to', __( 'End date (YYYY-MM-DD)', 'calendar-plus' ) ),
						range( 'perPage', __( 'Occurrences per page', 'calendar-plus' ), 1, 50 ),
						select( 'order', __( 'Order', 'calendar-plus' ), [ { value: '', label: __( 'Automatic', 'calendar-plus' ) }, { value: 'ASC', label: __( 'Earliest first', 'calendar-plus' ) }, { value: 'DESC', label: __( 'Latest first', 'calendar-plus' ) } ] ),
						el( Terms, { taxonomy: 'calendar_event_category', value: a.category, onChange: ( value ) => set( { category: value } ), label: __( 'Categories', 'calendar-plus' ) } ),
						el( Terms, { taxonomy: 'calendar_event_tag', value: a.tag, onChange: ( value ) => set( { tag: value } ), label: __( 'Tags', 'calendar-plus' ) } ),
						text( 'search', __( 'Keyword filter', 'calendar-plus' ) ),
						toggle( 'showSearch', __( 'Show search form', 'calendar-plus' ) ),
						toggle( 'pastFallback', __( 'Show latest past events when upcoming is empty', 'calendar-plus' ) ),
					),
					el( PanelBody, { title: __( 'Appearance', 'calendar-plus' ) },
						toggle( 'showImage', __( 'Featured image', 'calendar-plus' ) ),
						select( 'imageFit', __( 'Image fit', 'calendar-plus' ), [ { value: 'contain', label: __( 'Show complete image', 'calendar-plus' ) }, { value: 'cover', label: __( 'Crop to fill', 'calendar-plus' ) } ] ),
						select( 'imageRatio', __( 'Image frame', 'calendar-plus' ), [ { value: 'landscape', label: __( 'Landscape', 'calendar-plus' ) }, { value: 'square', label: __( 'Square', 'calendar-plus' ) }, { value: 'portrait', label: __( 'Portrait', 'calendar-plus' ) } ] ),
						select( 'imagePosition', __( 'Image position', 'calendar-plus' ), [ { value: 'right', label: __( 'Right', 'calendar-plus' ) }, { value: 'left', label: __( 'Left', 'calendar-plus' ) } ] ),
						toggle( 'showDate', __( 'Date cue', 'calendar-plus' ) ), toggle( 'monthHeadings', __( 'Month headings', 'calendar-plus' ) ),
						toggle( 'showLocation', __( 'Location', 'calendar-plus' ) ), toggle( 'showSummary', __( 'Summary', 'calendar-plus' ) ), toggle( 'showReadMore', __( 'Read more link', 'calendar-plus' ) ),
						range( 'summaryWords', __( 'Summary words', 'calendar-plus' ), 5, 100 ), range( 'headingLevel', __( 'Event heading level', 'calendar-plus' ), 2, 6 ),
						text( 'navigationId', __( 'Unique navigation ID', 'calendar-plus' ) ),
					),
				),
				el( wp.serverSideRender, { block: 'calendar-plus/event-feed', attributes: a } ),
			);
		},
		save: function save() {
			return null;
		},
	} );
}() );
