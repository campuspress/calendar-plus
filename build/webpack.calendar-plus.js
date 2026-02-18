'use strict';

const path = require('path');
const { merge } = require('webpack-merge'); // webpack-merge v5+ uses named import
const MiniCssExtractPlugin = require('mini-css-extract-plugin'); // Use mini-css-extract-plugin for webpack 5
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

const plugin_path = path.join(__dirname, '..');
const src_path = path.join(plugin_path, '_src');
const calendar_path = path.join(src_path, 'calendar');

// Webpack just understands JS, not CSS or Sass.
// This section, generates a file called foundation.js that contains all CSS styles in a variable
// But then ExtractTextPlugin will move it to a CSS file
const extractSass = new MiniCssExtractPlugin({
	filename: ({ chunk }) => {
		// Replicate the old filename logic if needed
		return '[name].css';
	}
});

const postcss_options = {
	postcssOptions: {
		plugins: [
			require('autoprefixer')()
		]
	}
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
					use: [MiniCssExtractPlugin.loader, 'css-loader', 'postcss-loader']
				},
				{
					test: /\.scss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: { modules: true }
						},
						{
							loader: 'postcss-loader',
							options: postcss_options
						},
						{
							loader: 'sass-loader', // compiles Sass to CSS
							options: {
								sassOptions: { includePaths: [calendar_path] },
							}
						}
					]
				}
			]
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
						presets: ['@babel/preset-env', '@babel/preset-react'],
						plugins: ['transform-class-properties']
					},
					include: [
						path.resolve(path.join(calendar_path, 'react-calendar'))
					]
				},
				{
					test: /\.css$/,
					use: [MiniCssExtractPlugin.loader, 'css-loader', 'postcss-loader']
				},
				{
					test: /\.scss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: { modules: true }
						},
						{
							loader: 'postcss-loader',
							options: postcss_options
						},
						{
							loader: 'sass-loader', // compiles Sass to CSS
							options: {
								sassOptions: { includePaths: [calendar_path] },
							}
						}
					]
				}
			]
		},
		plugins: [extractSass]
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
			rules: [
				{
					test: /\.scss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: { sourceMap: true }
						},
						{
							loader: 'postcss-loader',
							options: postcss_options
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: true
							}
						}
					]
				}
			]
		},
		plugins: [extractSass, new RemoveEmptyScriptsPlugin()]
	},
	{
		name: 'public-legacy',
		entry: {
			'calendar-plus': path.join(src_path, 'public/legacy/calendar-plus.scss'),
		},
		output: {
			filename: '[name].js',
			path: path.resolve(path.join(plugin_path, 'public/legacy/css'))
		},
		module: {
			rules: [
				{
					test: /\.scss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: { sourceMap: true }
						},
						{
							loader: 'postcss-loader',
							options: postcss_options
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: true
							}
						}
					]
				}
			]
		},
		plugins: [extractSass, new RemoveEmptyScriptsPlugin()]
	}
];

module.exports = (production) => {
	return configs.map(c => merge(c, {
		devtool: production ? 'source-map' : 'eval-source-map'
	}));
};
