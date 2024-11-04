(function () {
    wp.blocks.registerBlockType('calendar-plus/event-export', {
        edit: function (props) {
            return wp.element.createElement(
                'div',
                {},
                '[EVENT EXPORT]'
            );
        },
    });
})();