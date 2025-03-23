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
import {
	Icon,
} from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { buildQueryString } from '@wordpress/url';
import { TablePressIconSimple } from '../../img/tablepress-icon';

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
		type: ( data.message.includes( 'error' ) ) ? 'notice' : 'snackbar',
	};

	return { notice };
};

/**
 * Processes an AJAX request with Notices functionality.
 *
 * @param {Object}   props                      Function parameters.
 * @param {Object}   props.requestData          Request data.
 * @param {Function} props.onSuccessfulRequest  Callback for handling a successful save.
 * @param {Function} props.setBusyState         Callback for setting the busy state.
 * @param {Function} props.noticeOperations     Callbacks for working with notices.
 * @param {Function} props.noticesStoreDispatch Dispatch function for notices store. (Optional, only for "success" Snackbar notices.)
 */
const processAjaxRequest = ( { requestData, onSuccessfulRequest = onSuccessfulRequestDefault, setBusyState, noticeOperations, noticesStoreDispatch } ) => {
	/**
	 * Shows a notice to the user.
	 *
	 * @param {Object} notice         Notice data.
	 * @param {string} notice.status  Status of the notice (error, success, warning, info).
	 * @param {string} notice.content Content of the notice.
	 */
	const showNotice = ( { status, content } ) => {
		const id = `notice-${ Date.now() }`;
		content = <>
			{ /* Notices don't have a DOM ID, so add a custom span which has one, for the fade-out and removal. */ }
			<span id={ id }></span>
			<RawHTML>{ content }</RawHTML>
		</>;
		noticeOperations.createNotice( { id, status, content, isDismissible: ( 'error' === status ) } );

		if ( 'error' !== status ) {
			// Fade out non-error notices after 5 seconds and then remove them.
			setTimeout( () => {
				const notice = document.getElementById( id ).closest( '.components-notice' );
				notice.addEventListener( 'transitionend', () => noticeOperations.removeNotice( id ) );
				notice.style.opacity = 0;
			}, 5000 );
		} else {
			// Scroll error notices into view.
			setTimeout( () => {
				const notice = document.getElementById( id ).closest( '.components-notice' );
				if ( notice.getBoundingClientRect().bottom > ( window.innerHeight || document.documentElement.clientHeight ) ) {
					notice.scrollIntoView( { behavior: 'smooth', block: 'end', inline: 'nearest' } );
				}
			}, 1 );
		}
	};

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

		handleRequestSuccess( data );
	} )
	// Handle errors.
	.catch( ( error ) => {
		handleRequestError( error.message );
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
			if ( 'snackbar' === notice.type && 'undefined' !== typeof noticesStoreDispatch ) {
				// Dispatch a Snackbar notice.
				noticesStoreDispatch.createSuccessNotice(
					notice.content,
					{
						type: 'snackbar',
						icon: <Icon icon={ TablePressIconSimple } />,
					}
				);
			} else {
				// Show a normal notice.
				showNotice( notice );
			}
		}
	};

	/**
	 * Handles an error during the AJAX request.
	 *
	 * @param {string} message Error message.
	 */
	const handleRequestError = ( message ) => {
		message = __( 'Attention: Unfortunately, an error occurred.', 'tablepress' ) + ' ' + message + '<br>' + sprintf( __( 'Please see the <a href="%s" target="_blank">TablePress FAQ page</a> for suggestions.', 'tablepress' ), 'https://tablepress.org/faq/common-errors/' );
		const notice = {
			status: 'error',
			content: message,
		};
		showNotice( notice );
	};
};

export default processAjaxRequest;
