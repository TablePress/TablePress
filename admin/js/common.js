/**
 * JavaScript code for all TablePress admin screens.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals confirm, postboxes, pagenow, jQuery */

/**
 * WordPress dependencies.
 */
import { _n } from '@wordpress/i18n';

/**
 * Enable toggle/order functionality for post meta boxes.
 * For TablePress, pagenow has the form "tablepress_{$action}".
 *
 * @since 1.0.0
 */
postboxes.add_postbox_toggles( pagenow );

/**
 * Turn off the collapsing feature of postboxes when directly clicking on the postbox header bar.
 * This causes confusion to many users that accidentally click this.
 * After this, the postboxes can only be collapsed/expanded via the arrow icon in their header bar.
 *
 * @since 2.1.0
 */
jQuery( '.postbox .hndle' ).off( 'click.postboxes', postboxes.handle_click );

document.getElementById( 'tablepress-page' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
		return;
	}

	/**
	 * Show an AYS warning when a "Delete" link is clicked.
	 *
	 * @since 1.0.0
	 */
	if ( event.target.matches( '.delete-link' ) ) {
		if ( ! confirm( _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', 1, 'tablepress' ) ) ) {
			event.preventDefault();
			return;
		}

		// Prevent onunload warning, by calling unset method from edit.js (if defined).
		window?.tp?.helpers?.unsaved_changes?.unset?.();

		return;
	}
} );
