/**
 * JavaScript code for the "List Tables" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals confirm, prompt, tb_show, ajaxurl */

/**
 * WordPress dependencies.
 */
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';

document.querySelector( '.tablepress-all-tables' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
		return;
	}

	/**
	 * Show a popup box with the table's Shortcode.
	 *
	 * @since 1.0.0
	 */
	if ( event.target.matches( '.shortcode a' ) ) {
		prompt( __( 'To embed this table into a post or page, use this Shortcode:', 'tablepress' ), event.target.title );
		event.preventDefault();
		return;
	}

	/**
	 * Load a Thickbox with a table preview.
	 *
	 * @since 1.0.0
	 */
	if ( event.target.matches( '.table-preview a' ) ) {
		const width = window.innerWidth - 120;
		const height = window.innerHeight - 120;
		tb_show( event.target.title, `${ event.target.href }#TB_iframe=true&height=${ height }&width=${ width }`, false );
		event.preventDefault();
		return;
	}
} );

/**
 * Process links with an "ajax-link" class with AJAX.
 *
 * @since 1.0.0
 */
$( '#tablepress-page' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
		return;
	}

	if ( event.target.matches( '.ajax-link' ) ) {
		fetch( `${ ajaxurl }?${ event.target.href.split('?')['1'] }` ) // Append original link's query string to AJAX endpoint.
		.then( ( response ) => response.text() )
		.then( ( result ) => {
			if ( '1' !== result ) {
				return;
			}

			if ( 'hide_message' === event.target.dataset.action ) {
				// Remove original message.
				event.target.closest( 'div' ).remove();
			}
		} );

		event.preventDefault();
		return;
	}
} );

/**
 * Submit Bulk Actions only if an action was selected and at least one table was selected.
 *
 * Only the top button and the top bulk selector dropdown have to be evaluated, as WP mirrors them.
 *
 * @since 1.0.0
 */
const bulk_action_dropdown = $( '#doaction' );
// The bulk action dropdown is only in the DOM if at least one table is shown in the list, thus an existence check is needed.
if ( bulk_action_dropdown ) {
	bulk_action_dropdown.addEventListener( 'click', ( event ) => {
		const action = $( '#bulk-action-selector-top' ).value;
		const num_selected = $( '.tablepress-all-tables tbody input:checked' ).length;

		// Do nothing if no action or no tables were selected.
		if ( '-1' === action || 0 === num_selected ) {
			event.preventDefault();
			return;
		}

		// Show AYS prompt when deleting tables.
		if ( 'delete' === action ) {
			if ( ! confirm( _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', num_selected, 'tablepress' ) ) ) {
				event.preventDefault();
				return;
			}
		}
	} );
}
