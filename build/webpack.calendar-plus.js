'use strict';

import path from 'path';
import merge from 'webpack-merge';
import ExtractTextPlugin from 'extract-text-webpack-plugin';

const plugin_path = path.join(__dirname, '..');
const src_path = path.join(plugin_path, '_src');
const calendar_path = path.join(src_path, 'calendar');

// Webpack just understands JS, not CSS or Sass.
// This section, generates a file called foundation.js that contains all CSS styles in a variable
// But then ExtractTextPlugin will move it to a CSS file
const extractSass = new ExtractTextPlugin({
	filename: getPath => getPath('[name].css').replace('css/js', 'css')
});

const postcss_options = {
	plugins: [
		require('autoprefixer')()
	]
};

const configs = [
	{
		name: 'admin',
		entry: {
			admin: path.join(src_path, 'admin/admin/admin.js'),
			settings: path.join(src_path, 'admin/settings/settings.js'),
			'editor-shortcode': path.join(src_path, 'admin/editor-shortcode/editor-shortcode.js'),
			'editor-blocks': path.join(src_path, 'admin/editor-shortcode/editor-blocks.js')
		},
		output: {
			filename: '[name].js',
			path: path.resolve(plugin_path, 'admin/js')
		}
	},
	{
		name: 'front',
		entry: calendar_path,
		output: {
			filename: 'calendar-plus.js',
			path: path.join(plugin_path, 'public/js')
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env', '@babel/preset-react'], // Transform ES6 + React scripts to Vanilla JS
						plugins: ['transform-class-properties'] // This plugin allows to define static properties in ES6. Really useful.
					},
					include: [
						path.resolve(path.join(calendar_path, 'react-calendar'))
					]
				},
				{
					test: /\.css$/,
					use: [
						'style-loader',
					]
				},
				{
					test: /\.css$/,
					loader: 'css-loader',
					options: {
						modules: true
					}
				},
				{
					test: /\.css$/,
					loader: 'postcss-loader',
					options: postcss_options
				},
				{
					test: /\.scss$/,
					use: [
						'style-loader', // creates style nodes from JS strings
						{
							loader: 'css-loader', // translates CSS into CommonJS
							options: {modules: true}
						},
						{
							loader: 'postcss-loader',
							options: postcss_options
						},
						{
							loader: 'sass-loader', // compiles Sass to CSS
							options: {includePaths: [calendar_path]}
						}
					]
				}
			]
		}
	},
	{
		name: 'public',
		entry: {
			'calendar-plus': path.join(src_path, 'public/calendar-plus.scss'),
			'calendar-plus-events-by-cat-shortcode': path.join(src_path, 'public/calendar-plus-events-by-cat-shortcode.scss'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(path.join(plugin_path, 'public/css'))
		},
		module: {
			rules: [{
				test: /\.scss$/,
				// Extract CSS from foundation.js and move it to a css file
				use: extractSass.extract({
					// Last item is first processed
					use: [
						// 3. translates CSS into CommonJS
						{
							loader: 'css-loader',
							options: {sourceMap: true}
						},
						// 2. runs post-compilation transformations
						{
							loader: 'postcss-loader',
							options: postcss_options,
						},
						// 1. compiles Sass to CSS
						{
							loader: 'sass-loader',
							options: {sourceMap: true}
						},
					],
					fallback: 'style-loader'
				})
			}]
		},
		plugins: [extractSass]
	}
];

module.exports = (production) => {
	return configs.map(c => merge(c, {
		devtool: production ? 'source-map' : 'eval-source-map'
	}));
};
