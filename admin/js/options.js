/**
 * JavaScript code for the "Options" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* global confirm, CodeMirror, tablepress_strings */

jQuery( document ).ready( function( $ ) {

	'use strict';

	/**
	 * Invoke CodeMirror on the "Custom CSS" textarea
	 *
	 * @since 1.0.0
	 */
	var CM_custom_css = CodeMirror.fromTextArea( document.getElementById( 'option-custom-css' ), {
		mode: 'css',
		indentUnit: 2,
		tabSize: 2,
		indentWithTabs: true
	} );

	/**
	 * "Custom CSS" textarea grows on focus, if it is not disabled, but only once
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).find( '.CodeMirror' ).on( 'mousedown.codemirror', function() {
		var $this = $(this);
		if ( ! $this.hasClass( 'disabled' ) ) {
			$this.addClass( 'large' );
			CM_custom_css.refresh();
			$this.off( 'mousedown.codemirror' );
		}
	} );

	/**
	 * Enable/disable "Custom CSS" textarea and "Load from file" checkbox according to state of "Use Custom CSS" checkbox
	 *
	 * @since 1.0.0
	 */
	$( '#option-use-custom-css' ).on( 'change', function() {
		var use_custom_css = $(this).prop( 'checked' );
		CM_custom_css.setOption( 'readOnly', ! use_custom_css );
		$( '#tablepress-page' ).find( '.CodeMirror' ).toggleClass( 'disabled', ! use_custom_css );
	} ).change();

	/**
	 * On form submit: Enable disabled fields, so that they are transmitted in the POST request
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).on( 'submit', 'form', function() {
		$(this).find( 'input, select, textarea' ).prop( 'disabled', false );
	} );

	/**
	 * Require double confirmation when wanting to uninstall TablePress
	 *
	 * @since 1.0.0
	 */
	$( '#uninstall-tablepress' ).on( 'click', function() {
		if ( confirm( tablepress_strings.uninstall_warning_1 ) ) {
			return confirm( tablepress_strings.uninstall_warning_2 );
		} else {
			return false;
		}
	} );

} );
