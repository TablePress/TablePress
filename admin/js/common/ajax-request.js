/**
 * JavaScript code for AJAX requests with Notices functionality on admin screens.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';
import { buildQueryString } from '@wordpress/url';

/**
 * Internal dependencies.
 */
import { showNotice } from './functions';

/**
 * Default callback for handling specifics of a successful request.
 *
 * @param {Object} data
 */
const onSuccessfulRequestDefault = ( data ) => {
	const actionMessages = {
		success_save: __( 'The changes were saved successfully.', 'tablepress' ),
	};

	const notice = {
		status: ( data.message.includes( 'error' ) ) ? 'error' : 'success',
		content: actionMessages[ data.message ],
		type: ( data.message.includes( 'error' ) ) ? 'default' : 'snackbar',
	};

	return { notice };
};

/**
 * Processes an AJAX request with Notices functionality.
 *
 * @param {Object}   props                      Function parameters.
 * @param {Object}   props.requestData          Request data.
 * @param {Function} props.setBusyState         Callback for setting the busy state.
 * @param {Function} props.noticesStoreDispatch Dispatch function for the notices store.
 * @param {Function} props.onSuccessfulRequest  Callback for handling a successful save.
 */
const processAjaxRequest = ( { requestData, setBusyState, noticesStoreDispatch, onSuccessfulRequest = onSuccessfulRequestDefault } ) => {
	// Put the screen into "is busy" mode.
	setBusyState( true );
	document.body.classList.add( 'wait' );

	// Save the table data to the server via an AJAX request.
	fetch( ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Accept: 'application/json',
		},
		body: buildQueryString( requestData ),
	} )
	// Check for HTTP connection problems.
	.then( ( response ) => {
		if ( ! response.ok ) {
			throw new Error( sprintf( __( 'There was a problem with the server, HTTP response code %1$d (%2$s).', 'tablepress' ), response.status, response.statusText ) );
		}
		return response.json();
	} )
	// Check for problems with the transmitted data.
	.then( ( data ) => {
		if ( 'undefined' === typeof data || null === data || '-1' === data || 'undefined' === typeof data.success ) {
			throw new Error( __( 'The JSON data returned from the server is unclear or incomplete.', 'tablepress' ) );
		}

		if ( true !== data.success ) {
			const debugHtml = data.error_details ? `<p>${ __( 'These errors were encountered:', 'tablepress' ) }</p><pre>${ data.error_details }</pre>` : '';
			throw new Error( `<p>${ __( 'There was a problem with the request.', 'tablepress' ) }</p>${ debugHtml }` );
		}

		handleRequestSuccess( data, noticesStoreDispatch );
	} )
	// Handle errors.
	.catch( ( error ) => {
		handleRequestError( error.message, noticesStoreDispatch );
	} )
	// Clean up.
	.finally( () => {
		// Reset the screen from "is busy" mode.
		setBusyState( false );
		document.body.classList.remove( 'wait' );
	} );

	/**
	 * Handles a successful AJAX request.
	 *
	 * @param {Object} data Request response data.
	 */
	const handleRequestSuccess = ( data ) => {
		const { notice } = onSuccessfulRequest( data );
		if ( notice ) {
			showNotice( notice, noticesStoreDispatch );
		}
	};

	/**
	 * Handles an error during the AJAX request.
	 *
	 * @param {string} message Error message.
	 */
	const handleRequestError = ( message ) => {
		const title = __( 'Attention: Unfortunately, an error occurred.', 'tablepress' );
		message += '<br>' + sprintf( __( 'Please see the <a href="%s" target="_blank">TablePress FAQ page</a> for suggestions.', 'tablepress' ), 'https://tablepress.org/faq/common-errors/' );

		const notice = {
			status: 'error',
			content: message,
			details: title, // There is no dedicated `title` property for notices, so we repurpose the `details` property.
			type: 'default',
		};
		showNotice( notice, noticesStoreDispatch );
	};
};

export default processAjaxRequest;
