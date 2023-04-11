/**
 * JavaScript code for the "Options" screen, without the CodeMirror handling.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals confirm */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';

/**
 * Enable/disable the regular textarea according to state of "Load Custom CSS" checkbox.
 *
 * @since 1.0.0
 */
const $cb_use_custom_css = $( '#option-use-custom-css' );
if ( $cb_use_custom_css ) { // The checkbox field only exists for admins!
	$cb_use_custom_css.addEventListener( 'change', function () {
		$( '#option-custom-css' ).disabled = ! this.checked;
	} );
	$cb_use_custom_css.dispatchEvent( new Event( 'change' ) );
}

/**
 * On form submit: Enable disabled fields, so that they are sent in the HTTP POST request.
 *
 * @since 1.0.0
 */
document.querySelector( '#tablepress-page form' ).addEventListener( 'submit', function () {
	this.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => ( field.disabled = false ) );
} );

/**
 * Registers keyboard events and triggers corresponding actions by emulating button clicks.
 *
 * @since 2.1.1
 *
 * @param {Event} event Keyboard event.
 */
const keyboard_shortcuts = function ( event ) {
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
		document.querySelector( `.submit input.button` ).click();

		// Prevent the browser's native handling of the shortcut, i.e. showing the Save or Print dialogs.
		event.preventDefault();
	}
};
// Register keyboard shortcut handler.
window.addEventListener( 'keydown', keyboard_shortcuts, true );

/**
 * Require double confirmation when wanting to uninstall TablePress.
 *
 * @since 1.0.0
 */
$( '#uninstall-tablepress' ).addEventListener( 'click', ( event ) => {
	if (
		! confirm( __( 'Do you really want to uninstall TablePress and delete ALL data?', 'tablepress' ) ) ||
		! confirm( __( 'Are you really sure?', 'tablepress' ) ) )
	{
		event.preventDefault();
	}
} );
