(function () {
    wp.blocks.registerBlockType('calendar-plus/event-location', {
        edit: function (props) {
            return wp.element.createElement(
                'span',
                {},
                '[EVENT LOCATION]'
            );
        },
    });
})();