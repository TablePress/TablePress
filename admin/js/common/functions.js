/**
 * Common functions that are used in TablePress JS.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { ariaKeyShortcut, displayShortcut, keyboardShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import {
	Icon,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { TablePressIconSimple } from '../../img/icons';

/**
 * Alias for document.getElementById and document.querySelectorAll, depending on the first character of the passed selector string. Resembles jQuery.
 *
 * @param {string} selector Selector string. If it starts with #, a single ID is selected, all matching selectors otherwise.
 * @return {Element|NodeList} A single DOM Element or a DOM NodeList matching the selector.
 */
export const $ = ( selector ) => ( '#' === selector[0] ? document.getElementById( selector.slice( 1 ) ) : document.querySelectorAll( selector ) );

/**
 * Helper function to retrieve keyboard shortcuts, because the `keyboardShortcut` object is not available in WordPress versions < 7.2.
 *
 * @param {string} property The property to retrieve from the keyboardShortcut object.
 * @param {string} key      The key to retrieve the shortcut for.
 * @returns {Object} The keyboard shortcut object containing ariaLabel, display, and label.
 */
export const keyboardShortcutHelper = ( property, key ) => {
	// @todo This can be simplified to just `keyboardShortcut.primary( 'j' )` once WP 7.2 is required.
	return (
		keyboardShortcut ?
			keyboardShortcut[ property ]( key ) :
			{
				ariaKeyShortcut: ariaKeyShortcut ? ariaKeyShortcut[ property ]( key ) : undefined,
				displayShortcut: displayShortcut[ property ]( key ),
				label: shortcutAriaLabel[ property ]( key ),
			}
	);
};

/**
 * Shows a notice to the user.
 *
 * @param {Object}   notice               Notice data.
 * @param {string}   notice.status        Status of the notice (error, success, warning, info).
 * @param {string}   notice.content       Content of the notice.
 * @param {string}   notice.details       Details of the notice.
 * @param {string}   notice.type          Type of the notice.
 * @param {Function} noticesStoreDispatch Dispatch function for the notices store.
 */
export const showNotice = ( { status, content, details, type } = notice, noticesStoreDispatch ) => {
	noticesStoreDispatch.createNotice(
		status,
		content,
		{
			type,
			icon: ( 'snackbar' === type ) ? <Icon icon={ TablePressIconSimple } /> : undefined,
			details,
			context: 'tablepress',
			isDismissible: ( 'default' === type ),
		}
	)
	.then( ( { notice } ) => {
		const { id } = notice;

		// Don't do anything for snackbar notices, as they will automatically fade out after a few seconds.
		if ( 'snackbar' === type ) {
			return;
		}

		if ( 'error' !== status ) {
			// Fade out non-error notices after 5 seconds and then remove them.
			setTimeout( () => {
				const noticeElements = document.querySelectorAll( `.tablepress-notice.notice-${ id }` );
				if ( noticeElements.length > 0 ) {
					noticeElements[0].addEventListener( 'transitionend', () => noticesStoreDispatch.removeNotice( id, 'tablepress' ) ); // Add the event listener to the first element only, as all elements will transition at the same time.
					noticeElements.forEach( ( noticeElement ) => ( noticeElement.style.opacity = 0 ) );
				}
			}, 5000 );
		} else {
			// Scroll error notices into view.
			setTimeout( () => {
				const noticeElement = document.querySelector( `.tablepress-notice.notice-${ id }` );
				if ( noticeElement && ( noticeElement.getBoundingClientRect().top < 0 || noticeElement.getBoundingClientRect().bottom > ( window.innerHeight || document.documentElement.clientHeight ) ) ) {
					noticeElement.scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'nearest' } );
				}
			}, 1 );
		}
	} );
};
