/**
 * JavaScript code for the "Screen Options" tab integration on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals tp, ajaxurl */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { buildQueryString } from '@wordpress/url';

/**
 * Internal dependencies.
 */
import { $ } from '../common/functions';

/**
 * Updates table editor layout with new screen option values.
 *
 * @param {Event} event `input` event of the screen options fields.
 */
const update = ( event ) => {
	if ( ! event.target ) {
		return;
	}

	if ( 'table_editor_line_clamp' === event.target.id ) {
		tp.editor.el.style.setProperty( '--table-editor-line-clamp', parseInt( event.target.value, 10 ) );
		tp.editor.updateCornerPosition();
		return;
	}

	if ( 'table_editor_column_width' === event.target.id ) {
		tp.screenOptions.table_editor_column_width = parseInt( event.target.value, 10 );
		tp.screenOptions.table_editor_column_width = Math.max( tp.screenOptions.table_editor_column_width, 30 ); // Ensure a minimum column width of 30 pixesl.
		tp.screenOptions.table_editor_column_width = Math.min( tp.screenOptions.table_editor_column_width, 9999 ); // Ensure a maximum column width of 9999 pixesl.
		tp.editor.colgroup.forEach( ( col ) => col.setAttribute( 'width', tp.screenOptions.table_editor_column_width ) );
		tp.editor.updateCornerPosition();
		return;
	}
};

/**
 * Designates a screen option field to have been changed, so that the value is sent to the server when it is blurred.
 *
 * @param {Event} event `change` event of the screen options fields.
 */
const setWasChanged = ( event ) => {
	if ( event.target ) {
		event.target.was_changed = true;
	}
};

/**
 * Saves screen options to the server after they have been changed and the field is blurred.
 *
 * @param {Event} event `blur` event of the screen options fields.
 */
const save = ( event ) => {
	if ( ! event.target ) {
		return;
	}

	if ( ! event.target.was_changed ) {
		return;
	}

	event.target.was_changed = false;

	// Prepare the data for the AJAX request.
	const request_data = {
		action: 'tablepress_save_screen_options',
		_ajax_nonce: tp.nonces.screen_options,
		tablepress: {
			[ event.target.id ]: parseInt( event.target.value, 10 ),
		},
	};

	// Add spinner and change cursor.
	event.target.parentNode.insertAdjacentHTML( 'beforeend', `<span id="spinner-save-changes" class="spinner is-active" title="${ __( 'Changes are being saved …', 'tablepress' ) }" style="float:none;margin:0 0 0 6px;"></span>` );
	document.body.classList.add( 'wait' );

	// Save the table data to the server via an AJAX request.
	fetch( ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Accept: 'application/json',
		},
		body: buildQueryString( request_data ),
	} )
	.finally( () => {
		$( '#spinner-save-changes' ).remove();
		document.body.classList.remove( 'wait' );
	} );
};

// Register callbacks for the screen options.
const screenOptions = $( '#tablepress-screen-options' );
if ( screenOptions ) {
	screenOptions.addEventListener( 'input', update );
	screenOptions.addEventListener( 'change', setWasChanged );
	screenOptions.addEventListener( 'focusout', save ); // Use the `focusout` event instead of `blur` as that does not bubble.
}
