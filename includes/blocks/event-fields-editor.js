( function() {
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	[ 'date', 'location', 'add-to', 'status' ].forEach( ( field ) => {
		wp.blocks.registerBlockType( 'calendar-plus/event-' + field, {
			edit: function Edit( { attributes, setAttributes, context } ) {
				return el( 'div', wp.blockEditor.useBlockProps(),
					field === 'location' && el( wp.blockEditor.InspectorControls, {}, el( wp.components.PanelBody, { title: __( 'Location', 'calendar-plus' ) },
						el( wp.components.ToggleControl, { label: __( 'Emphasise Location name', 'calendar-plus' ), checked: attributes.showName, onChange: ( value ) => setAttributes( { showName: value } ) } ),
						el( wp.components.ToggleControl, { label: __( 'Hide if unchanged imported Venue details are already in the content', 'calendar-plus' ), checked: attributes.hideWhenInContent, onChange: ( value ) => setAttributes( { hideWhenInContent: value } ) } ),
					) ),
					context.postId ? el( wp.serverSideRender, { block: 'calendar-plus/event-' + field, attributes, urlQueryArgs: { post_id: context.postId } } ) : el( 'p', {}, __( 'Displays this field on an event. Select an event to preview it.', 'calendar-plus' ) ),
				);
			},
			save: function save() {
				return null;
			},
		} );
	} );
}() );
