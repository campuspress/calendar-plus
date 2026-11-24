/**
 * Google Maps readiness.
 *
 * The Maps API loads asynchronously and calls `window.calendarpGmapsLoaded`
 * (its `callback` loader parameter) once it is ready. Map code must not
 * assume `google` exists on document ready; it waits with ready( callback ).
 *
 * The bundle that requires this is a WordPress dependency of the `gmaps-api`
 * script, so this always runs before Google can call back.
 */
let loaded = false;
const queue = [];

window.calendarpGmapsLoaded = function () {
	loaded = true;
	while (queue.length) {
		queue.shift()();
	}
};

module.exports = function ready(callback) {
	if (loaded) {
		callback();
	} else {
		queue.push(callback);
	}
};
