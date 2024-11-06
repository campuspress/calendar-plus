(function () {
    wp.blocks.registerBlockType('calendar-plus/event-add-to', {
        edit: function (props) {
            return wp.element.createElement(
                'div',
                {},
                '[EVENT ADD TO]'
            );
        },
    });
})();