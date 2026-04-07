/**
 * JavaScript code for the "List Tables" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/**
 * WordPress dependencies.
 */
import { _n } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './common/functions';
import { initializeReactComponent } from './common/react-loader';
import Screen from './list/screen';

/**
 * Process links with an "ajax-link" class with AJAX.
 *
 * @since 1.0.0
 */
document.querySelectorAll( '#tablepress-page .ajax-link' ).forEach( ( link ) => {
	link.addEventListener( 'click', ( event ) => {
		fetch( `${ ajaxurl }?${ event.target.href.split( '?' )[1] }` ) // Append original link's query string to AJAX endpoint.
		.then( ( response ) => response.text() )
		.then( ( result ) => {
			if ( '1' !== result ) {
				return;
			}

			if ( 'hide_message' === event.target.dataset.action ) {
				// Remove original message.
				event.target.closest( '.notice' ).remove();
			}
		} );
		event.preventDefault();
	} );
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
		// Set the ID of the bulk action dropdown in the global variable to the correct `name` attribute of the dropdown, as the default of `action` can not be used.
		window.bulkActionObserverIds.bulk_action = 'bulk-action-selector-top';

		const action = $( '#bulk-action-selector-top' ).value;
		const num_selected = $( '.tablepress-all-tables tbody input:checked' ).length;

		// Show AYS prompt when deleting tables.
		if ( 'delete' === action ) {
			if ( ! confirm( _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', num_selected, 'tablepress' ) ) ) {
				event.preventDefault();
				return;
			}
		}
	} );
}

initializeReactComponent(
	'tablepress-list-screen',
	<Screen />,
);
