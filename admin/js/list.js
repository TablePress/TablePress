/**
 * JavaScript code for the "List Tables" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals confirm, tb_show, ajaxurl */

/**
 * WordPress dependencies.
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';

document.querySelector( '.tablepress-all-tables' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
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

			switch ( event.target.dataset.action ) {
				case 'hide_message':
					// Show confirmation message with new text for donation nag links.
					if ( 'donation_nag' === event.target.dataset.item && '' !== event.target.dataset.target ) {
						let message =  __( 'Thank you very much! Your donation is highly appreciated. You just contributed to the further development of TablePress!', 'tablepress' );
						if ( 'maybe-later' === event.target.dataset.target ) {
							message = sprintf( __( 'No problem! I still hope you enjoy the benefits that TablePress adds to your site. If you should change your mind, you&#8217;ll always find the &#8220;Donate&#8221; button on the <a href="%s">TablePress website</a>.', 'tablepress' ), 'https://tablepress.org/' );
						}
						event.target.closest( 'div' ).insertAdjacentHTML( 'afterend', `<div class="donation-message-after-click-message notice notice-success"><p><strong>${ message }</strong></p></div>` );
						const $notice = document.querySelector( '.donation-message-after-click-message' );
						void $notice.offsetWidth; // Trick browser layout engine. Necessary to make CSS transition work.
						$notice.style.opacity = 0;
						$notice.addEventListener( 'transitionend', () => $notice.remove() );
					}

					// Remove original message.
					event.target.closest( 'div' ).remove();
					break;
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
