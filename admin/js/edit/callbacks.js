/**
 * JavaScript code for the callbacks on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/* globals tp */

/**
 * WordPress dependencies.
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import processAjaxRequest from '../common/ajax-request';

/**
 * Saves the table changes to the server.
 *
 * @param {Object}   props                      Function parameters.
 * @param {Object}   props.noticesStoreDispatch Dispatch function for the notices store.
 * @param {Object}   props.tableMeta            Table meta data.
 * @param {Object}   props.tableOptions         Table options.
 * @param {Function} props.updateScreenSettings Callback to update the screen settings.
 * @param {Function} props.updateTableMeta      Callback to update the table meta.
 */
export const saveTableChanges = ( { noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings, updateTableMeta } ) => {
	// Validate input fields.
	if ( ! applyFilters( 'tablepress.optionsValidateFields', true, tableOptions ) ) {
		return;
	}

	// Collect information about hidden rows and columns.
	tp.helpers.visibility.update();

	// Prepare the data for the AJAX request.
	const requestData = {
		action: 'tablepress_save_table',
		_ajax_nonce: tp.nonces.edit_table,
		tablepress: {
			id: tp.table.id,
			new_id: tableMeta.id,
			name: tableMeta.name,
			description: tableMeta.description,
			data: JSON.stringify( tp.editor.options.data ),
			options: JSON.stringify( tableOptions ),
			visibility: JSON.stringify( tp.table.visibility ),
			number: {
				rows: tp.editor.options.data.length,
				columns: tp.editor.options.columns.length,
			},
		},
	};

	/**
	 * Callback for handling specifics of a successful save on this screen.
	 *
	 * @param {Object} data
	 */
	const onSuccessfulRequest = ( data ) => {
		// Saving was successful, so the original ID has changed to the (maybe) new ID -> we need to adjust all occurrences.
		if ( tp.table.id !== data.table_id && window?.history?.pushState ) {
			// Update URL, but only if the table ID changed, to not get dummy entries in the browser history.
			window.history.pushState( '', '', window.location.href.replace( /table_id=[a-zA-Z0-9_-]+/gi, `table_id=${ data.table_id }` ) );
		}

		tp.table.id = data.table_id;

		updateTableMeta( {
			id: data.table_id,
			lastModified: data.last_modified,
			lastEditor: data.last_editor,
		} );

		// Update the nonces.
		[ 'copy', 'delete', 'edit', 'preview' ].forEach( action => {
			tp.nonces[ `${ action }_table` ] = data[ `new_${ action }_nonce` ];
		} );

		// Update the "Export", "Copy", "Delete", and "Preview" URLs in the screen data.
		updateScreenSettings( ( prevScreenSettings ) => {
			const updatedUrls = {
				exportUrl: prevScreenSettings.exportUrl.replace( /table_id=[a-zA-Z0-9_-]+/g, `table_id=${ data.table_id }` ),
			};
			[ 'copy', 'delete', 'preview' ].forEach( ( action ) => {
				const url = `${ action }Url`;
				updatedUrls[ url ] = prevScreenSettings[ url ]
					.replace( /item=[a-zA-Z0-9_-]+/g, `item=${ data.table_id }` ) // Updates both the "item" and the "return_item" parameters.
					.replace( /&_wpnonce=[a-zA-Z0-9]+/g, `&_wpnonce=${ tp.nonces[ `${ action }_table` ] }` );
			} );

			return updatedUrls;
		} );

		tp.helpers.unsaved_changes.unset();

		const actionMessages = {};
		actionMessages.success_save = __( 'The table was saved successfully.', 'tablepress' );
		actionMessages.success_save_success_id_change = actionMessages.success_save + ' ' + __( 'The table ID was changed.', 'tablepress' );
		actionMessages.success_save_error_id_change = actionMessages.success_save + ' ' + __( 'The table ID could not be changed, probably because the new ID is already in use!', 'tablepress' );

		if ( 'success_save_error_id_change' === data.message && data.error_details ) {
			const errorIntroduction = __( 'These errors were encountered:', 'tablepress' );
			actionMessages.success_save_error_id_change = `<p>${ actionMessages.success_save_error_id_change }</p><p>${ errorIntroduction }</p><pre>${ data.error_details }</pre><p>`;
		}

		const notice = {
			status: ( data.message.includes( 'error' ) ) ? 'error' : 'success',
			content: actionMessages[ data.message ],
			type: ( data.message.includes( 'error' ) ) ? 'default' : 'snackbar',
		};

		return { notice };
	};

	const setBusyState = ( isBusy ) => updateScreenSettings( { isSaving: isBusy } );

	processAjaxRequest( { requestData, setBusyState, noticesStoreDispatch, onSuccessfulRequest } );
};

/**
 * Shows a preview of the table.
 *
 * @param {Object}   props                      Function parameters.
 * @param {Object}   props.noticesStoreDispatch Dispatch function for the notices store.
 * @param {Object}   props.tableMeta            Table meta data.
 * @param {Object}   props.tableOptions         Table options.
 * @param {Function} props.updateScreenSettings Callback to update the screen settings.
 */
export const showPreview = ( { noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings } ) => {
	// For tables without unsaved changes, directly show an externally rendered table from a URL in an iframe in a Modal.
	if ( ! tp.made_changes ) {
		updateScreenSettings( {
			previewIsOpen: true,
			previewSrcDoc: '',
		} );
		return;
	}

	/* For tables with unsaved changes, get the table preview HTML code for the iframe via AJAX. */

	// Update information about hidden rows and columns.
	tp.helpers.visibility.update();

	// Prepare the data for the AJAX request.
	const requestData = {
		action: 'tablepress_preview_table',
		_ajax_nonce: tp.nonces.preview_table,
		tablepress: {
			id: tp.table.id,
			new_id: tableMeta.id,
			name: tableMeta.name,
			description: tableMeta.description,
			data: JSON.stringify( tp.editor.options.data ),
			options: JSON.stringify( tableOptions ),
			visibility: JSON.stringify( tp.table.visibility ),
			number: {
				rows: tp.editor.options.data.length,
				columns: tp.editor.options.columns.length,
			},
		},
	};

	/**
	 * Callback for handling specifics of a successful save on this screen.
	 *
	 * @param {Object} data
	 */
	const onSuccessfulRequest = ( data ) => {
		updateScreenSettings( {
			previewIsOpen: true,
			previewSrcDoc: `<!DOCTYPE html><html><head>${ data.head_html }</head><body>${ data.body_html }</body></html>`,
		} );

		return { notice: null };
	};

	const setBusyState = ( isBusy ) => updateScreenSettings( { previewIsLoading: isBusy } );

	processAjaxRequest( { requestData, setBusyState, noticesStoreDispatch, onSuccessfulRequest } );
};
