/**
 * JavaScript code for the CodeMirror handling on the "Options" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.9.0
 */

/* globals tp, wp */

/* jshint strict: global */
'use strict'; // Necessary as this file does not use "import".

// Ensure the global `tp` object exists.
window.tp = window.tp || {};

/**
 * Invoke CodeMirror on the "Custom CSS" textarea.
 *
 * @since 1.9.0
 */
tp.CM_custom_css = wp.codeEditor.initialize( 'option-custom-css', {} ).codemirror;
const $CM_wrapper = tp.CM_custom_css.getWrapperElement();

/**
 * Let CodeMirror textarea grow on first focus (with mouse click), if it is not disabled.
 *
 * @since 1.0.0
 */
const CM_wrapper_mousedown_handler = function () {
	if ( ! this.classList.contains( 'disabled' ) ) {
		this.classList.add( 'large' );
		tp.CM_custom_css.refresh();
		this.removeEventListener( 'mousedown', CM_wrapper_mousedown_handler ); // No need to keep checking for clicks after the textarea height was increased.
	}
};
$CM_wrapper.addEventListener( 'mousedown', CM_wrapper_mousedown_handler );

/**
 * Enable/disable CodeMirror according to state of "Load Custom CSS" checkbox.
 *
 * @since 1.0.0
 */
const $cb_use_custom_css = document.getElementById( 'option-use-custom-css' );
$cb_use_custom_css.addEventListener( 'change', function () {
	tp.CM_custom_css.setOption( 'readOnly', ! this.checked );
	$CM_wrapper.classList.toggle( 'disabled', ! this.checked );
} );
$cb_use_custom_css.dispatchEvent( new Event( 'change' ) );
