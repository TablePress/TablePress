/**
 * Common functions that are used in TablePress JS.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Registers a "Save Changes" keyboard shortcut for a button.
 *
 * @since 2.2.0
 *
 * @param {HTMLElement} $button DOM element for the button.
 */
export const register_save_changes_keyboard_shortcut = ( $button ) => {
	// Add keyboard shortcut as title attribute to the "Save Changes" button, with correct modifier key for Mac/non-Mac.
	const modifier_key = ( window?.navigator?.platform?.includes( 'Mac' ) ) ?
		_x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) :
		_x( 'Ctrl+', 'keyboard shortcut modifier key on a non-Mac keyboard', 'tablepress' );
	const shortcut = sprintf( $button.dataset.shortcut, modifier_key ); // eslint-disable-line @wordpress/valid-sprintf
	$button.title = sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), shortcut );

	/**
	 * Registers keyboard events and triggers corresponding actions by emulating button clicks.
	 *
	 * @since 2.2.0
	 *
	 * @param {Event} event Keyboard event.
	 */
	const keyboard_shortcuts = ( event ) => {
		let action = '';

		if ( event.ctrlKey || event.metaKey ) {
			if ( 83 === event.keyCode ) {
				// Save Changes: Ctrl/Cmd + S.
				action = 'save-changes';
			}
		}

		if ( 'save-changes' === action ) {
			// Blur the focussed element to make sure that all change events were triggered.
			document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element

			// Emulate a click on the button corresponding to the action.
			$button.click();

			// Prevent the browser's native handling of the shortcut, i.e. showing the Save or Print dialogs.
			event.preventDefault();
		}
	};
	// Register keyboard shortcut handler.
	window.addEventListener( 'keydown', keyboard_shortcuts, true );
};
