module.exports = {
    copy: {
        process: true,
        watch: true,
        logColor: 'cyan',
        areas: [
            {
                paths: {
                    src: ['./**/*', '!./node_modules/**/*'],
                    dest: './calendar-plus',
                }
            },
        ],
        pipes: {
            watch: {
                events: 'all',
            },
        }
    }
}