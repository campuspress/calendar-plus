'use strict';

import { merge } from 'webpack-merge'; // webpack-merge v5+ uses named import
import calendar_plus from './build/webpack.calendar-plus';
import FriendlyErrorsWebpackPlugin from '@soda/friendly-errors-webpack-plugin'; // Use the maintained fork for webpack 5

module.exports = (env) => {

	const defaults = {
		include: [],
		production: false,
		mode: 'production'
	};

	env = Object.assign({}, defaults, env);

	if (!Array.isArray(env.include)) {
		env.include = env.include.split(',');
	}

	let config = calendar_plus(env.production);

	if (env.include.length > 0) {
		config = config.filter((c) => env.include.includes(c.name)); // Fix: assign filtered array
	}

	const common_config = {
		plugins: [
			new FriendlyErrorsWebpackPlugin() // Better errors display
		]
	};

	// Add common config to all configs
	config = config.map((c) => merge([c, common_config]));

	// NOTE: Ensure all plugins/loaders are compatible with webpack 5 (see migration guide)

	return config;
};
