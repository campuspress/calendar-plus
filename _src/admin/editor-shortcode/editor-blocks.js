const {registerBlockType} = wp.blocks;
const {createElement, useState} = wp.element;
const {__} = wp.i18n;
const {InspectorControls} = wp.editor;
const {
    TextControl,
    RangeControl,
    SelectControl,
    ServerSideRender,
    PanelBody,
    __experimentalHeading,
    __experimentalView,
    __experimentalSpacer,
    __experimentalScrollable,
    __experimentalVStack,
    CheckboxControl,
    SearchControl,
    ToggleControl
} = wp.components;
const {withSelect} = window.wp.data;

const CategorySelect = function(props) {
    const [ searchText, setSearchText ] = useState('');
    const categories = props.categories ? props.categories : [];
    const [ items, setItems ] = useState(categories);
    const selected = props.selected ? props.selected : [];

    const list = [];
    for (const item of items) {

        list.push(
            createElement(
                CheckboxControl,
                {
                    label: item.label,
                    checked: selected.indexOf( item.value ) !== -1 ,
                    key: item.label,
                    style: { marginBottom: 5 },
                    onChange: function(value) {
                        if ( props.onSelect ) {
                            props.onSelect( item.value, value );
                        }
                    }
                }
            ),
        );
    }
    var elements = [
        createElement(
            __experimentalSpacer,
            { marginBottom: 5 },
            createElement(
                SearchControl,
                {
                    value: searchText,
                    onChange: function(value) {
                        const filtered = props.categories.filter(function(item){
                            return item.label.toLowerCase().indexOf(value) !== -1;
                        });
                        setItems(filtered);
                        setSearchText(value);
                    }
                }
            ),
        ),
        createElement(
            __experimentalScrollable,
            {
                style: { maxHeight: 200 }
            },
            createElement(__experimentalVStack, {}, list)
        )
    ];
    if ( props.label ) {
        elements.unshift(
            createElement(
                __experimentalSpacer,
                { marginBottom: 5 },
                createElement( __experimentalHeading, { level: 5 }, props.label )
            )
        );
    }

     return createElement(
         __experimentalSpacer,
         { marginBottom: 10 },
         createElement(__experimentalView, {}, elements)
     );
}

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
        events: {
            default: 5
        },
        past_events: {
            default: false
        },
        category: {
            default: ''
        },
        display_location: {
            type: 'boolean',
            default: false
        },
        display_excerpt: {
            type: 'boolean',
            default: false
        },
        featured_image: {
            default: false
        }
    },
    edit: withSelect( function( select ) {
        return {
            categories: select('core').getEntityRecords('taxonomy', 'calendar_event_category', {per_page: -1})
        };
    } )( function( props ) {
        var categoryOptions = [ { value: 0, label: __( 'All' ) } ];
        var selectedCategories = props.attributes.category
            .split( ',' )
            .map(
                function( value ){
                    return parseInt( value.trim() );
                }
            )
            .filter(
                function(num) {
                    return ! isNaN( num );
                }
            );

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
                    createElement(
                        CategorySelect,
                        {
                            label: __( 'Category' ),
                            categories: categoryOptions,
                            selected: selectedCategories,
                            onSelect: function(id, value) {
                                if ( ! id ) {
                                    if ( value ) {
                                        selectedCategories = props.categories.map(
                                            function( item ) {
                                                return item.id;
                                            }
                                        );
                                        selectedCategories.push( 0 );
                                        props.setAttributes( { category: selectedCategories.join( ',' ) } );
                                    } else {
                                        props.setAttributes( { category: '' } );
                                    }


                                    return;
                                }

                                const index = selectedCategories.indexOf( id );

                                if ( value && index === -1 ) {
                                    selectedCategories.push( id );
                                } else if ( index !== -1 ) {
                                    delete selectedCategories[ index ];
                                }
                                props.setAttributes( {
                                    category: selectedCategories.join( ',' )
                                } );
                            }
                        }
                    ),
                    createElement(RangeControl, {
                        value: props.attributes.events,
                        label: __( 'Number of events' ),
                        onChange: function(value){
                            props.setAttributes( { events: value } );
                        },
                        min: 1,
                        max: 100,
                    }),
					createElement(ToggleControl, {
						value: props.attributes.past_events,
						checked: props.attributes.past_events,
						label: __( 'Display past events' ),
						onChange: function(value){
							props.setAttributes( { past_events: value } );
						}
					}),

                    createElement(__experimentalView, {}, [
                        createElement(
                            __experimentalSpacer,
                            {},
                            createElement(
                                __experimentalHeading,
                                { level: 5 },
                                __( 'Choose fields to display' )
                            ),
                        ),
                        createElement(
                            CheckboxControl,
                            {
                                label: __( 'Location' ),
                                checked: props.attributes.display_location,
                                onChange: function(value) {
                                    props.setAttributes( { display_location: value } );
                                }
                            }
                        ),
                        createElement(
                            CheckboxControl,
                            {
                                label: __( 'Excerpt' ),
                                checked: props.attributes.display_excerpt,
                                onChange: function(value) {
                                    props.setAttributes( { display_excerpt: value } );
                                }
                            }
                        ),
                        createElement(ToggleControl, {
                            value: props.attributes.featured_image,
                            label: __( 'Featured image' ),
                            checked: props.attributes.featured_image,
                            onChange: function(value){
                                props.setAttributes( {featured_image: value} );
                            }
                        }),
                    ]),
                )
            )
        ] )
    } ),
    save(){
        return null;
    }
});