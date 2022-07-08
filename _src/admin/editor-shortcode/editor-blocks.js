const {registerBlockType} = wp.blocks;
const {createElement, useState} = wp.element;
const {__} = wp.i18n;
const {InspectorControls} = wp.editor;
const {TextControl, RangeControl, SelectControl, ServerSideRender, PanelBody} = wp.components;
const {withSelect} = window.wp.data;

registerBlockType( 'calendar-plus/calendar', {
    title: __( 'Events Calendar' ),
    description: __( 'Displays full events calendar.' ),
    category:  'widgets',
    icon: {
        src: 'calendar-alt',
    },
    attributes: {
        category: {},
        time_format: {},
        dow_format: {},
        month_name_format: {},
        day_format: {},
        date_format: {}
    },
	edit: withSelect( function( select ) {
        return {
            categories: select('core').getEntityRecords('taxonomy', 'calendar_event_category', {per_page: -1})
        };
    } )( function( props ) {
        var categoryOptions = [ { value: '', label: __( 'All' ) } ];
        
		if( props.categories ) {
			props.categories.forEach((category) => {
				categoryOptions.push({value:category.id, label:category.name});
			});
		}
        
		return createElement('div', {}, [
            createElement( 'div', {}, createElement( 'img', {src: CalPlusBlocksOptions.calendar_image} ) ),
            createElement( InspectorControls, {},
                createElement( PanelBody, { title: __( 'Calendar Settings' ), initialOpen: true },
                    createElement(SelectControl, {
                        value: props.attributes.category,
                        label: __( 'Category' ),
                        onChange: function(value){
                            props.setAttributes( { category: value } );
                        },
                        options: categoryOptions
                    }),
					createElement(SelectControl, {
						value: props.attributes.time_format,
                        label: __( 'Time format' ),
                        onChange: function(value){
                            props.setAttributes( { time_format: value } );
                        },
						options: [
							{value: 'g:i a', label: '11:00 pm'},
							{value: 'H:i', label: '23:00'},
						]
					}),
					createElement(SelectControl, {
						value: props.attributes.dow_format,
                        label: __( 'Day of the week format' ),
                        onChange: function(value){
                            props.setAttributes( { dow_format: value } );
                        },
						options: [
							{value: 'l', label: 'Sunday'},
							{value: 'D', label: 'Sun'},
						]
					}),
					createElement(SelectControl, {
						value: props.attributes.month_name_format,
                        label: __( 'Month name format' ),
                        onChange: function(value){
                            props.setAttributes( { month_name_format: value } );
                        },
						options: [
							{value: 'M', label: 'Jan'},
							{value: 'F', label: 'January'},
						]
					}),
					createElement(SelectControl, {
						value: props.attributes.day_format,
                        label: __( 'Day format' ),
                        onChange: function(value){
                            props.setAttributes( { day_format: value } );
                        },
						options: [
							{value: 'd', label: '09'},
							{value: 'j', label: '9'},
						]
                    }),
					createElement(SelectControl, {
						value: props.attributes.date_format,
                        label: __( 'Date format' ),
                        onChange: function(value){
                            props.setAttributes( { date_format: value } );
                        },
						options: [
							{value: 'd/m', label: '15/09'},
                            {value: 'j/n', label: '15/9'},
                            {value: 'm/d', label: '09/15'},
                            {value: 'n/j', label: '9/15'},
						]
                    }),
                )
			)
		] )
	} ),
	save(){
        return null;
	}
});

registerBlockType( 'calendar-plus/event', {
    title: __( 'Single Event' ),
    description: __( 'Displays single calendar event.' ),
    category:  'widgets',
    icon: {
        src: 'calendar-alt',
    },
    attributes: {
        event_id: {},
    },
    edit(props){
        return createElement('div', {}, [
            createElement( 'div', {}, createElement( ServerSideRender, {
                block: "calendar-plus/event",
                attributes: props.attributes,
                EmptyResponsePlaceholder: function() {
                    return createElement( 'div', {}, 'test' );
                }
            } ) ),
            createElement( InspectorControls, {},
                createElement( PanelBody, { title: __( 'Event Settings' ), initialOpen: true },
                    createElement(TextControl, {
                        value: props.attributes.event_id,
                        label: __( 'Event Id' ),
                        help: __( 'Use Events > Events on admin area to search for your event by title or ID' ),
                        type: 'number',
                        onChange: function(value){
                            props.setAttributes( { event_id: value } );
                        },
                    }),
                )
			)
		] )
    },
	save(){
        return null;
	}
});

registerBlockType( 'calendar-plus/events-list', {
    title: __( 'Events List' ),
    description: __( 'Displays events list.' ),
    category:  'widgets',
    icon: {
        src: 'calendar-alt',
    },
    attributes: {
        events: {default: 5},
        category: {},
    },
	edit: withSelect( function( select ) {
        return {
            categories: select('core').getEntityRecords('taxonomy', 'calendar_event_category', {per_page: -1})
        };
    } )( function( props ) {
        var categoryOptions = [ { value: '', label: __( 'All' ) } ];
        
		if( props.categories ) {
			props.categories.forEach((category) => {
				categoryOptions.push({value:category.id, label:category.name});
			});
		}
        
		return createElement('div', {}, [
            createElement( 'div', {}, createElement( ServerSideRender, {
                block: "calendar-plus/events-list",
                attributes: props.attributes,
            } ) ),
            createElement( InspectorControls, {},
                createElement( PanelBody, { title: __( 'Events Settings' ), initialOpen: true },
                    createElement(SelectControl, {
                        value: props.attributes.category,
                        label: __( 'Category' ),
                        onChange: function(value){
                            props.setAttributes( { category: value } );
                        },
                        options: categoryOptions
                    }),
                    createElement(RangeControl, {
                        value: props.attributes.events,
                        label: __( 'Number of events' ),
                        onChange: function(value){
                            props.setAttributes( { events: value } );
                        },
                        min: 1,
                        max: 100,
                    }),
                )
			)
		] )
	} ),
	save(){
        return null;
	}
});