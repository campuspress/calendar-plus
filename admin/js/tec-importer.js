/* global calendarpTECImport */
/**
 * Sequential, resumable TEC import requests.
 */
( function() {
	'use strict';
	const root = document.getElementById( 'calendarp-tec-progress' );
	if ( ! root || typeof calendarpTECImport === 'undefined' ) {
		return;
	}
	const config = calendarpTECImport;
	if ( config.redirect ) {
		window.location.replace( config.redirect );
		return;
	}
	const status = root.querySelector( '[role="status"]' );
	const progress = root.querySelector( 'progress' );
	const warnings = root.querySelector( 'ul' );
	const start = root.querySelector( '[data-import-start]' );
	const cancel = root.querySelector( '[data-import-cancel]' );
	let running = false;
	let cancelRequested = false;

	function render( summary ) {
		progress.value = summary.cursor;
		progress.max = summary.total;
		status.textContent = summary.done
			? config.complete
			: summary.cursor + ' / ' + summary.total;
		warnings.replaceChildren();
		const counts = document.createElement( 'li' );
		counts.textContent =
			config.labels.created +
			': ' +
			summary.created +
			'; ' +
			config.labels.reused +
			': ' +
			summary.reused +
			'; ' +
			config.labels.updated +
			': ' +
			( summary.updated || 0 ) +
			'; ' +
			config.labels.failed +
			': ' +
			summary.failed +
			'; ' +
			config.labels.warnings +
			': ' +
			summary.warning_count;
		warnings.appendChild( counts );
		summary.warnings.forEach( function( message ) {
			const item = document.createElement( 'li' );
			item.textContent = message;
			warnings.appendChild( item );
		} );
		if ( summary.done ) {
			if ( start ) {
				start.remove();
				cancel.remove();
			}
		}
	}

	async function request( shouldCancel ) {
		const body = new URLSearchParams( {
			action: 'calendarp_tec_import_batch',
			nonce: config.nonce,
			token: config.token,
			cancel: shouldCancel ? '1' : '',
		} );
		const response = await fetch( config.url, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} );
		const result = await response.json();
		if ( ! response.ok || ! result || ! result.success ) {
			throw new Error(
				result && result.data && result.data.message
					? result.data.message
					: config.error,
			);
		}
		render( result.data );
		return result.data;
	}

	async function run() {
		if ( running ) {
			return;
		}
		running = true;
		start.disabled = true;
		cancelRequested = false;
		try {
			let summary;
			do {
				summary = await request( cancelRequested );
				if ( ! summary.done ) {
					await new Promise( function( resolve ) {
						window.setTimeout( resolve, 250 );
					} );
				}
			} while ( ! summary.done );
		} catch ( error ) {
			status.textContent = error.message || config.error;
		} finally {
			running = false;
			start.disabled = false;
			cancel.disabled = false;
		}
	}

	render( config.summary );
	if ( start ) {
		start.addEventListener( 'click', run );
		cancel.addEventListener( 'click', async function() {
			if ( running ) {
				cancelRequested = true;
				cancel.disabled = true;
				return;
			}
			try {
				await request( true );
			} catch ( error ) {
				status.textContent = error.message || config.error;
			}
		} );
	}
}() );
