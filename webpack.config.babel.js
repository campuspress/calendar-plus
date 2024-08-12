'use strict';

import merge from 'webpack-merge';
import calendar_plus from './build/webpack.calendar-plus.js';
import FriendlyErrorsWebpackPlugin from '@nuxt/friendly-errors-webpack-plugin';

module.exports = function(env, argv) {

	const defaults = {
		include: []
	};

	env = Object.assign({}, defaults, env);

	if (!Array.isArray(env.include)) {
		env.include = env.include.split(',');
	}

	const mode = argv.mode ?? 'production';
	let config = calendar_plus(mode);

	if (env.include.length > 0) {
		config.filter((c) => env.include.includes(c.name));
	}

	const common_config = {
		plugins: [
			new FriendlyErrorsWebpackPlugin() // Better errors display
		]
	};

	// Add common config to all configs
	config = config.map((c) => merge([c, common_config]));

	return config;
};
