# Calendar Plus

[Calendar+](https://campuspress.com/accessible-wordpress-calendar-plugin/) is a WordPress calendar and events plugin developed by the team at [CampusPress](https://campuspress.com).

Calendar+ is specifically developed to be friendly for visitors using screen-readers and other assistive technologies. Calendar owners can import events from iCal and Google Calendar feeds and choose form multiple display options.

Site visitors can easily add events to their own Google Calendar, Outlook, or download an ical file. 

## More info

Calendar Plus uses several technologies to generate the CSS/JS bundles:

- React for the Calendar shortcode
- Sass for styling
- Webpack to minimize and generate JS
- npm to run tasks

Calendar Plus is built on Webpack. It generates 4 bundles:
- admin.js
- settings.js
- editor-shortcode.js
- calendar-plus.css with Foundation styles

## Getting started

1. Install [Node.js](https://nodejs.org)
2. Run `npm install` in plugin root folder. All node dependencies will be downloaded to `node_modules`
3. Run `composer install` to install Composer packages in `vendor`
3. Run `npm run build` to generate the production CSS and JavaScript
4. Run `npm run gulp` to update translation files 

## React Calendar

The front-end calendar is a combination of React + ES6 JS structure. Everything begins with `_src/calendar/react-calendar/index.js`

React Calendar will get a calendarPlusi18n object that is generated in `includes/shortcodes/class-calendar-plus-calendar-shortcode.php` which contains everything needed to render the calendar.

React Calendar uses the [React Big Calendar](https://github.com/intljusticemission/react-big-calendar) component.

### Watching files

You can run `npm run watch:front` to begin watching Calendar files.

### The bundle

All React/ES6 scripts are bundled into `public/js/calendar-plus.js`

## Admin Scripts

### Watching files

Run `npm run watch:admin` to watch JS files. These are:

#### _src/admin/admin/admin.js

wp-admin scripts are written Backbone

`admin.js` will require every js file the `collections`, `misc`, `models` and `views` directories.

#### _src/admin/settings/settings.js

Scripts for the admin Events > Settings screen.

#### _src/admin/editor-shortcode/editor-shortcode.js

Scripts for the Calendar Plus button in the WordPress visual editor.

### The bundle

Every script is minified and bundled in:

`admin/js/admin.js`
`admin/js/editor-shortcode.js`
`admin/js/settings.js`

Webpack will do everything for you.

## Styles

Webpack will also transform Sass to CSS and will bundle them in `.css` files.

### Watching files

Run `npm run watch:public` to watch Sass files. These are:

#### _src/public/calendar-plus.scss

The main styles for React Calendar

#### _src/public/calendar-plus-events-by-cat-shortcode.scss

Styles for the Events By Category Shortcode

### The bundle

Every stylesheet is minified and bundled in:

`public/css/calendar-plus.css`
`public/css/calendar-plus-events-by-cat-shortcode.css`

Webpack will do everything for you.

#### Some notes about the bundles

Webpack just understands JS but `webpack.calendar-plus.js` uses a plugin that will allow Webpack understand Sass too. The process is the following one:
- Process Sass and transform to CSS
- Get that CSS and add it to a .js file and use `eval()` to add those styles to your website
- Thanks to `extract-text-webpack-plugin` in Webpack, the plugin will get the `eval()` contents and will place them in a `.css` file which is loaded by Calendar Plus.
- The `.js` file will be still there but is useless. These files are:

`public/css/calendar-plus.js`
`public/css/calendar-plus-events-by-cat-shortcode.js`

Right now, they are included in the commits but do not worry about them.

#### Watch all bundles

Just run `npm run watch` and all bundles will be watched at the same time.

#### Troubleshooting

On Windows platforms it may be necessary to run `npm install --global --production windows-build-tools` before installing node modules.
