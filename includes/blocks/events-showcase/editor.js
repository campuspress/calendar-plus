( function() {
	const el = wp.element.createElement;
	const { useEffect, useState } = wp.element;
	const { __, sprintf } = wp.i18n;
	const { ComboboxControl, PanelBody, TextControl, SelectControl, RangeControl, ToggleControl, Button, Notice, ColorPalette } = wp.components;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const templates = window.calendarpShowcaseTemplates;
	const presentation = [ 'core/group', 'core/columns', 'core/column', 'core/heading', 'core/paragraph', 'core/post-title', 'core/post-terms', 'core/buttons', 'core/button', 'core/separator', 'core/spacer', 'core/image', 'calendar-plus/showcase-field' ];
	const roles = [ { value: 'toolbar', label: __( 'Heading and filters', 'calendar-plus' ) }, { value: 'featured', label: __( 'Featured event', 'calendar-plus' ) }, { value: 'between', label: __( 'Results heading', 'calendar-plus' ) }, { value: 'card', label: __( 'Event card', 'calendar-plus' ) }, { value: 'footer', label: __( 'Footer and past-events link', 'calendar-plus' ) } ];
	const fields = [ { value: 'image', label: __( 'Event image', 'calendar-plus' ) }, { value: 'dateBadge', label: __( 'Date badge', 'calendar-plus' ) }, { value: 'summary', label: __( 'Summary', 'calendar-plus' ) }, { value: 'date', label: __( 'Occurrence date', 'calendar-plus' ) }, { value: 'time', label: __( 'Occurrence time', 'calendar-plus' ) }, { value: 'location', label: __( 'Location', 'calendar-plus' ) }, { value: 'categories', label: __( 'Categories', 'calendar-plus' ) }, { value: 'tags', label: __( 'Tags', 'calendar-plus' ) }, { value: 'link', label: __( 'Event details link', 'calendar-plus' ) }, { value: 'filters', label: __( 'Search and filters', 'calendar-plus' ) }, { value: 'layoutSwitch', label: __( 'Grid / list switch', 'calendar-plus' ) }, { value: 'resultCount', label: __( 'Result count', 'calendar-plus' ) }, { value: 'pastLink', label: __( 'Past-events link', 'calendar-plus' ) } ];
	const colours = [ [ 'ink', __( 'Main text', 'calendar-plus' ), '#172a35' ], [ 'muted', __( 'Secondary text', 'calendar-plus' ), '#63717a' ], [ 'line', __( 'Borders', 'calendar-plus' ), '#e0e6e8' ], [ 'cream', __( 'Page background', 'calendar-plus' ), '#f7f7f4' ], [ 'accent', __( 'Accent and buttons', 'calendar-plus' ), '#006b64' ], [ 'accent-dark', __( 'Hover accent', 'calendar-plus' ), '#00524d' ], [ 'soft', __( 'Selected controls', 'calendar-plus' ), '#e5f3ef' ], [ 'gold', __( 'Decorative accent', 'calendar-plus' ), '#f5cc68' ], [ 'hero', __( 'Hero background', 'calendar-plus' ), '#103e3d' ], [ 'hero-text', __( 'Hero text', 'calendar-plus' ), '#ffffff' ], [ 'card', __( 'Card background', 'calendar-plus' ), '#ffffff' ], [ 'control', __( 'Input background', 'calendar-plus' ), '#ffffff' ], [ 'control-text', __( 'Input text', 'calendar-plus' ), '#3d5155' ], [ 'button-text', __( 'Button text', 'calendar-plus' ), '#ffffff' ], [ 'date', __( 'Date badge background', 'calendar-plus' ), '#ffffff' ], [ 'past', __( 'Past-events panel', 'calendar-plus' ), '#eaf0ed' ], ...[ '#d9e9e3', '#f3e5d2', '#dfe4ee', '#e8deeb', '#e4e9d8', '#e6e5e2' ].map( ( color, index ) => [ 'tone-' + String.fromCharCode( 97 + index ), __( 'Card fallback colour', 'calendar-plus' ) + ' ' + ( index + 1 ), color ] ) ];

	function StyleControls( { a, set } ) {
		const typography = a.style?.typography || {};
		const update = ( key, value ) => set( { style: { ...a.style, typography: { ...typography, [ key ]: value } } } );
		return el( PanelBody, { title: __( 'Advanced typography', 'calendar-plus' ), initialOpen: false },
			el( 'p', {}, __( 'Use a font already installed on your site or a system font stack. Select nested headings and fields to style them independently.', 'calendar-plus' ) ),
			el( TextControl, { label: __( 'Font family or system stack', 'calendar-plus' ), value: typography.fontFamily || '', onChange: ( value ) => {
				if ( /^[a-z0-9 _,'"-]{0,200}$/i.test( value ) ) {
					update( 'fontFamily', value );
				}
			} } ),
			el( RangeControl, { label: __( 'Custom font size (px)', 'calendar-plus' ), value: /px$/.test( typography.fontSize || '' ) ? parseFloat( typography.fontSize ) : undefined, min: 6, max: 160, allowReset: true, onChange: ( value ) => update( 'fontSize', value === undefined ? undefined : value + 'px' ) } ),
			el( RangeControl, { label: __( 'Line height', 'calendar-plus' ), value: parseFloat( typography.lineHeight ) || undefined, min: 0.7, max: 3, step: 0.05, allowReset: true, onChange: ( value ) => update( 'lineHeight', value === undefined ? undefined : String( value ) ) } ),
			el( RangeControl, { label: __( 'Letter spacing (em)', 'calendar-plus' ), value: /em$/.test( typography.letterSpacing || '' ) ? parseFloat( typography.letterSpacing ) : undefined, min: -0.1, max: 0.5, step: 0.01, allowReset: true, onChange: ( value ) => update( 'letterSpacing', value === undefined ? undefined : value + 'em' ) } ),
			el( SelectControl, { label: __( 'Font weight', 'calendar-plus' ), value: typography.fontWeight || '', options: [ { value: '', label: __( 'Default', 'calendar-plus' ) }, ...[ '100', '200', '300', '400', '500', '600', '700', '800', '900' ].map( ( value ) => ( { value, label: value } ) ) ], onChange: ( value ) => update( 'fontWeight', value ) } ),
			el( SelectControl, { label: __( 'Font style', 'calendar-plus' ), value: typography.fontStyle || '', options: [ { value: '', label: __( 'Default', 'calendar-plus' ) }, { value: 'normal', label: __( 'Normal', 'calendar-plus' ) }, { value: 'italic', label: __( 'Italic', 'calendar-plus' ) } ], onChange: ( value ) => update( 'fontStyle', value ) } ),
			el( SelectControl, { label: __( 'Letter case', 'calendar-plus' ), value: typography.textTransform || '', options: [ { value: '', label: __( 'Default', 'calendar-plus' ) }, { value: 'none', label: __( 'None', 'calendar-plus' ) }, { value: 'uppercase', label: __( 'Uppercase', 'calendar-plus' ) }, { value: 'lowercase', label: __( 'Lowercase', 'calendar-plus' ) }, { value: 'capitalize', label: __( 'Title case', 'calendar-plus' ) } ], onChange: ( value ) => update( 'textTransform', value ) } ),
			el( SelectControl, { label: __( 'Text decoration', 'calendar-plus' ), value: typography.textDecoration || '', options: [ { value: '', label: __( 'Default', 'calendar-plus' ) }, { value: 'none', label: __( 'None', 'calendar-plus' ) }, { value: 'underline', label: __( 'Underline', 'calendar-plus' ) }, { value: 'line-through', label: __( 'Strike through', 'calendar-plus' ) } ], onChange: ( value ) => update( 'textDecoration', value ) } ),
		);
	}

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

	function Controls( { a, set, names } ) {
		return el( wp.element.Fragment, {}, ...names.map( ( [ name, label, kind, options ] ) => {
			const common = { key: name, label, onChange: ( value ) => set( { [ name ]: value } ) };
			if ( kind === 'toggle' ) {
				return el( ToggleControl, { ...common, checked: a[ name ] } );
			}
			if ( kind === 'range' ) {
				return el( RangeControl, { ...common, value: a[ name ], min: options[ 0 ], max: options[ 1 ] } );
			}
			if ( kind === 'select' ) {
				return el( SelectControl, { ...common, value: a[ name ], options } );
			}
			return el( TextControl, { ...common, value: a[ name ], type: kind === 'number' ? 'number' : 'text', onChange: ( value ) => set( { [ name ]: kind === 'number' ? Math.max( 0, Number( value ) || 0 ) : value } ) } );
		} ) );
	}
	// The core style panels remain available on every block and nested core block.
	wp.blocks.registerBlockType( 'calendar-plus/events-showcase', {
		edit: function Edit( { attributes: a, setAttributes: set } ) {
			const palette = a.palette || {};
			const [ colourPart, setColourPart ] = useState( 'accent' );
			const selectedColour = colours.find( ( entry ) => entry[ 0 ] === colourPart );
			const style = Object.fromEntries( Object.entries( palette ).filter( ( [ key, value ] ) => colours.some( ( entry ) => entry[ 0 ] === key ) && /^#[0-9a-f]{3,6}$/i.test( value ) ).map( ( [ key, value ] ) => [ '--cp-' + key, value ] ) );
			const props = useInnerBlocksProps( useBlockProps( { className: 'calendarp-showcase', style } ), { template: templates.shell, templateLock: false } );
			return el( wp.element.Fragment, {},
				el( InspectorControls, {}, el( StyleControls, { a, set } ), el( PanelBody, { title: __( 'Showcase palette', 'calendar-plus' ) },
					el( 'p', {}, __( 'Select individual headings, cards, fields and groups to edit their fonts, colours, borders and spacing. Theme fonts stay local to your site.', 'calendar-plus' ) ),
					el( SelectControl, { label: __( 'Colour to edit', 'calendar-plus' ), value: colourPart, options: colours.map( ( [ value, label ] ) => ( { value, label } ) ), onChange: setColourPart } ),
					el( ColorPalette, { value: palette[ colourPart ] || selectedColour[ 2 ], colors: colours.map( ( [ , name, color ] ) => ( { name, color } ) ), disableCustomColors: false, onChange: ( value ) => set( { palette: { ...palette, [ colourPart ]: value || '' } } ) } ),
					el( Button, { variant: 'secondary', onClick: () => set( { palette: {} } ) }, __( 'Reset showcase palette', 'calendar-plus' ) ),
				) ), el( 'div', props ),
			);
		}, save: () => el( InnerBlocks.Content ),
	} );
	wp.blocks.registerBlockType( 'calendar-plus/showcase-results', {
		edit: function Edit( { attributes: a, setAttributes: set, clientId, context } ) {
			const [ editing, setEditing ] = useState( false );
			const inner = wp.data.useSelect( ( select ) => select( 'core/block-editor' ).getBlocks( clientId ), [ clientId ] );
			const duplicate = wp.data.useSelect( ( select ) => {
				const store = select( 'core/block-editor' );
				const first = store.getClientIdsWithDescendants().find( ( id ) => store.getBlockAttributes( id )?.navigationId === a.navigationId );
				return a.navigationId && first && first !== clientId;
			}, [ clientId, a.navigationId ] );
			useEffect( () => {
				if ( ! a.navigationId || duplicate ) {
					set( { navigationId: clientId } );
				}
			}, [ a.navigationId, clientId, duplicate, set ] );
			const blockProps = useBlockProps();
			const blocks = useInnerBlocksProps( { className: 'calendarp-showcase-template-editor', style: editing ? {} : { display: 'none' } }, { template: templates.results, allowedBlocks: [ 'calendar-plus/showcase-section' ], templateLock: false } );
			return el( wp.element.Fragment, {},
				el( InspectorControls, {},
					el( StyleControls, { a, set } ),
					el( PanelBody, { title: __( 'Event selection', 'calendar-plus' ) }, el( Terms, { taxonomy: 'calendar_event_category', value: a.category, onChange: ( value ) => set( { category: value } ), label: __( 'Categories', 'calendar-plus' ) } ), el( Terms, { taxonomy: 'calendar_event_tag', value: a.tag, onChange: ( value ) => set( { tag: value } ), label: __( 'Tags', 'calendar-plus' ) } ), el( Controls, { a, set, names: [
						[ 'view', __( 'Default date view', 'calendar-plus' ), 'select', [ { value: 'upcoming', label: __( 'Upcoming', 'calendar-plus' ) }, { value: 'past', label: __( 'Past', 'calendar-plus' ) }, { value: 'all', label: __( 'All dates', 'calendar-plus' ) }, { value: 'range', label: __( 'Fixed date range', 'calendar-plus' ) } ] ],
						...( a.view === 'range' ? [ [ 'from', __( 'Start date (YYYY-MM-DD)', 'calendar-plus' ) ], [ 'to', __( 'End date (YYYY-MM-DD)', 'calendar-plus' ) ] ] : [] ),
						[ 'search', __( 'Required keyword filter', 'calendar-plus' ) ],
						[ 'order', __( 'Order', 'calendar-plus' ), 'select', [ { value: '', label: __( 'Automatic', 'calendar-plus' ) }, { value: 'ASC', label: __( 'Earliest first', 'calendar-plus' ) }, { value: 'DESC', label: __( 'Latest first', 'calendar-plus' ) } ] ],
						[ 'perPage', __( 'Cards per page', 'calendar-plus' ), 'range', [ 1, 50 ] ], [ 'showFeatured', __( 'Show featured event', 'calendar-plus' ), 'toggle' ], [ 'featuredId', __( 'Featured event ID (0 = first matching event)', 'calendar-plus' ), 'number' ], [ 'pastFallback', __( 'Show past events when upcoming is empty', 'calendar-plus' ), 'toggle' ],
					] } ), el( 'p', {}, __( 'Visitor category and keyword filters narrow this selection. A fixed date range remains a boundary. Other date views are defaults that visitors can change.', 'calendar-plus' ) ) ),
					el( PanelBody, { title: __( 'Layout', 'calendar-plus' ) }, el( Controls, { a, set, names: [ [ 'layout', __( 'Default layout', 'calendar-plus' ), 'select', [ { value: 'grid', label: __( 'Grid', 'calendar-plus' ) }, { value: 'list', label: __( 'List', 'calendar-plus' ) } ] ], [ 'columns', __( 'Desktop grid columns', 'calendar-plus' ), 'range', [ 1, 4 ] ], [ 'gap', __( 'Card gap (px)', 'calendar-plus' ), 'range', [ 0, 100 ] ], [ 'navigationId', __( 'Unique navigation ID', 'calendar-plus' ) ] ] } ) ),
				),
				el( 'div', blockProps, el( Button, { variant: 'secondary', onClick: () => setEditing( ! editing ), 'aria-pressed': editing }, editing ? __( 'Preview real events', 'calendar-plus' ) : __( 'Edit all event templates', 'calendar-plus' ) ),
					editing && el( Notice, { status: 'info', isDismissible: false }, __( 'These are shared templates. Edit, add, move or remove blocks here to update every event card. Dynamic fields use placeholders while you edit; Preview real events shows your saved structure with live events.', 'calendar-plus' ) ),
					! editing && el( wp.serverSideRender, { block: 'calendar-plus/showcase-results', httpMethod: 'POST', attributes: { ...a, previewContent: wp.blocks.serialize( inner ) }, urlQueryArgs: context.postId ? { post_id: context.postId } : {} } ),
					el( 'div', blocks ),
				),
			);
		}, save: () => el( InnerBlocks.Content ),
	} );
	wp.blocks.registerBlockType( 'calendar-plus/showcase-section', {
		edit: function Edit( { attributes: a, setAttributes: set } ) {
			const props = useInnerBlocksProps( useBlockProps( { className: 'calendarp-showcase-' + a.role } ), { allowedBlocks: presentation, templateLock: false } );
			return el( wp.element.Fragment, {}, el( InspectorControls, {}, el( StyleControls, { a, set } ), el( PanelBody, { title: __( 'Template section', 'calendar-plus' ) }, el( SelectControl, { label: __( 'Section role', 'calendar-plus' ), value: a.role, options: roles, onChange: ( role ) => set( { role } ) } ) ) ), el( 'div', props ) );
		}, save: () => el( InnerBlocks.Content ),
	} );
	wp.blocks.registerBlockType( 'calendar-plus/showcase-field', {
		icon: el( wp.components.Dashicon, { icon: 'calendar-alt', className: 'calendarp-showcase-field-icon' } ),
		__experimentalLabel: ( attributes, { context } ) => {
			// Preserve names supplied through WordPress's Rename action.
			if ( [ 'list-view', 'breadcrumb' ].includes( context ) && attributes.metadata?.name ) {
				return attributes.metadata.name;
			}
			if ( context === 'list-view' ) {
				const field = fields.find( ( item ) => item.value === ( attributes.field || 'summary' ) );
				if ( field ) {
					/* translators: %s: the selected event field, such as Location or Search and filters. */
					return sprintf( __( 'Showcase Event Field: %s', 'calendar-plus' ), field.label );
				}
			}
		},
		edit: function Edit( { attributes: a, setAttributes: set } ) {
			const names = [ [ 'field', __( 'Display', 'calendar-plus' ), 'select', fields ] ];
			if ( a.field === 'summary' ) {
				names.push( [ 'words', __( 'Summary words', 'calendar-plus' ), 'range', [ 5, 100 ] ] );
			}
			if ( [ 'link', 'pastLink', 'filters' ].includes( a.field ) ) {
				names.push( [ 'label', __( 'Link or button text', 'calendar-plus' ) ] );
			}
			if ( a.field === 'image' ) {
				names.push( [ 'imageFit', __( 'Image fit', 'calendar-plus' ), 'select', [ { value: 'contain', label: __( 'Show complete image', 'calendar-plus' ) }, { value: 'cover', label: __( 'Crop to fill', 'calendar-plus' ) } ] ], [ 'imageRatio', __( 'Image frame', 'calendar-plus' ), 'select', [ { value: 'landscape', label: __( 'Landscape', 'calendar-plus' ) }, { value: 'square', label: __( 'Square', 'calendar-plus' ) }, { value: 'portrait', label: __( 'Portrait', 'calendar-plus' ) } ] ], [ 'imagePosition', __( 'Image focal position', 'calendar-plus' ), 'select', [ { value: 'center', label: __( 'Center', 'calendar-plus' ) }, { value: 'top', label: __( 'Top', 'calendar-plus' ) }, { value: 'bottom', label: __( 'Bottom', 'calendar-plus' ) }, { value: 'left', label: __( 'Left', 'calendar-plus' ) }, { value: 'right', label: __( 'Right', 'calendar-plus' ) } ] ] );
			}
			if ( a.field === 'time' ) {
				names.push( [ 'showTimezone', __( 'Show site timezone', 'calendar-plus' ), 'toggle' ] );
			}
			if ( a.field === 'location' ) {
				names.push( [ 'showAddress', __( 'Show full Location address', 'calendar-plus' ), 'toggle' ] );
			}
			if ( a.field === 'filters' ) {
				names.push( [ 'showSearch', __( 'Show search', 'calendar-plus' ), 'toggle' ], [ 'showCategoryFilter', __( 'Show category filter', 'calendar-plus' ), 'toggle' ], [ 'showDateFilter', __( 'Show date filter', 'calendar-plus' ), 'toggle' ], [ 'placeholder', __( 'Search placeholder', 'calendar-plus' ) ], [ 'categoryLabel', __( 'Category accessible label', 'calendar-plus' ) ], [ 'allCategoriesLabel', __( 'All categories text', 'calendar-plus' ) ], [ 'dateLabel', __( 'Date accessible label', 'calendar-plus' ) ] );
			}
			const props = useBlockProps( { className: 'calendarp-showcase-field calendarp-showcase-field-' + a.field } );
			let preview = el( 'p', {}, fields.find( ( field ) => field.value === a.field )?.label || __( 'Event field', 'calendar-plus' ) );
			if ( a.field === 'image' ) {
				preview = el( 'span', { className: 'calendarp-showcase-no-image', 'aria-hidden': true }, '✳' );
			} else if ( a.field === 'dateBadge' ) {
				preview = el( 'div', {}, el( 'strong', {}, '22' ), el( 'small', {}, __( 'Oct', 'calendar-plus' ) ) );
			} else if ( a.label ) {
				preview = el( 'p', {}, a.label );
			}
			return el( wp.element.Fragment, {}, el( InspectorControls, {}, el( StyleControls, { a, set } ), el( PanelBody, { title: __( 'Event field', 'calendar-plus' ) }, el( Controls, { a, set, names } ) ) ), el( 'div', props, preview ) );
		}, save: () => null,
	} );
}() );
