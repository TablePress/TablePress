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
import { $ } from './common/functions';
import { registerSaveChangesKeyboardShortcut } from './common/keyboard-shortcut';

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
$( '#tablepress-page-form' ).addEventListener( 'submit', function () {
	this.querySelectorAll( ':scope input, :scope select, :scope textarea' ).forEach( ( field ) => ( field.disabled = false ) );
} );

registerSaveChangesKeyboardShortcut( $( '#tablepress-options-save-changes' ) );

/**
 * Require double confirmation when wanting to uninstall TablePress.
 *
 * @since 1.0.0
 */
const $button_uninstall = $( '#uninstall-tablepress' );
if ( $button_uninstall ) { // The button only exists for admins!
	$button_uninstall.addEventListener( 'click', ( event ) => {
		if (
			! confirm( __( 'Do you really want to uninstall TablePress and delete ALL data?', 'tablepress' ) ) ||
			! confirm( __( 'Are you really sure?', 'tablepress' ) )
		) {
			event.preventDefault();
		}
	} );
}
