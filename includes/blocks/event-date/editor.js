(function () {
    wp.blocks.registerBlockType('calendar-plus/event-date', {
        edit: function (props) {
            return wp.element.createElement(
                'span',
                {},
                '[EVENT DATE]'
            );
        },
    });
})();