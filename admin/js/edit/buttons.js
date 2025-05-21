/**
 * JavaScript code for the "Edit" section integration of the "Save Changes" and "Preview" buttons.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	KeyboardShortcuts,
	Spinner,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	withNotices,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import processAjaxRequest from '../common/ajax-request';
import { Notifications } from '../common/notifications';

/**
 * Saves the table changes to the server.
 *
 * @param {Object}   props                      Function parameters.
 * @param {Object}   props.screenData           Screen data.
 * @param {Function} props.updateScreenData     Callback to update the screen data.
 * @param {Object}   props.tableOptions         Table options.
 * @param {Object}   props.tableMeta            Table meta data.
 * @param {Function} props.updateTableMeta      Callback to update the table meta.
 * @param {Function} props.noticeOperations     Callbacks for working with notices.
 * @param {Object}   props.noticesStoreDispatch Dispatch function for the notices store. (Optional, only for notices created by keyboard shortcuts.)
 */
const saveTableChanges = ( { screenData, updateScreenData, tableOptions, tableMeta, updateTableMeta, noticeOperations, noticesStoreDispatch } ) => {
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
			tp.nonces[ `${action}_table` ] = data[ `new_${action}_nonce` ];
		} );

		// Update the "Export", "Copy", "Delete", and "Preview" URLs in the screen data.
		const updatedUrls = {};
		updatedUrls.exportUrl = screenData.exportUrl.replace( /table_id=[a-zA-Z0-9_-]+/g, `table_id=${ data.table_id }` );
		[ 'copy', 'delete', 'preview' ].forEach( ( action ) => {
			updatedUrls[ `${ action }Url` ] = screenData[ `${ action }Url` ]
				.replace( /item=[a-zA-Z0-9_-]+/g, `item=${ data.table_id }` ) // Updates both the "item" and the "return_item" parameters.
				.replace( /&_wpnonce=[a-zA-Z0-9]+/g, `&_wpnonce=${ tp.nonces[ `${ action }_table` ] }` );
		} );
		updateScreenData( updatedUrls );

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
			type: ( data.message.includes( 'error' ) ) ? 'notice' : 'snackbar',
		};

		return { notice };
	};

	const setBusyState = ( isBusy ) => updateScreenData( { isSaving: isBusy } );

	processAjaxRequest( { requestData, onSuccessfulRequest, setBusyState, noticeOperations, noticesStoreDispatch } );
};

/**
 * Shows a preview of the table.
 *
 * @param {Object}   props                  Function parameters.
 * @param {Function} props.updateScreenData Callback to update the screen data.
 * @param {Object}   props.tableOptions     Table options.
 * @param {Object}   props.tableMeta        Table meta data.
 * @param {Function} props.noticeOperations Callbacks for working with notices.
 */
const showPreview = ( { updateScreenData, tableOptions, tableMeta, noticeOperations } ) => {
	// For tables without unsaved changes, directly show an externally rendered table from a URL in an iframe in a Modal.
	if ( ! tp.made_changes ) {
		updateScreenData( {
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
		updateScreenData( {
			previewIsOpen: true,
			previewSrcDoc: `<!DOCTYPE html><html><head>${ data.head_html }</head><body>${ data.body_html }</body></html>`,
		} );

		return { notice: null };
	};

	const setBusyState = ( isBusy ) => updateScreenData( { previewIsLoading: isBusy } );

	processAjaxRequest( { requestData, onSuccessfulRequest, setBusyState, noticeOperations } );
};

const Section = ( { noticeOperations, noticeUI, screenData, updateScreenData, tableOptions, tableMeta, updateTableMeta } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	return (
		<VStack
			style={ {
				margin: '1.5rem 0',
			} }
		>
			<HStack alignment="left">
			{
				tp.screenOptions.currentUserCanPreviewTable && (
					<Button
						variant="secondary"
						href={ screenData.previewUrl }
						text={ __( 'Preview', 'tablepress' ) }
						shortcut={ screenData.previewIsLoading ? undefined :
							{
								ariaLabel: shortcutAriaLabel.primary( 'p' ),
								display: displayShortcut.primary( 'p' ),
							}
						}
						isBusy={ screenData.previewIsLoading }
						disabled={ screenData.previewIsLoading }
						accessibleWhenDisabled={ true }
						onClick={ ( event ) => {
							// Open the Preview Modal if the button is clicked, except if an unmodified preview in a new tab is requested.
							if ( tp.made_changes || ! ( event.ctrlKey || event.metaKey || event.shiftKey ) ) {
								event.preventDefault();
								showPreview( { updateScreenData, tableOptions, tableMeta, noticeOperations } );
							}
						} }
					/>
				)
			}
			<Button
				variant="primary"
				text={ __( 'Save Changes', 'tablepress' ) }
				shortcut={ screenData.isSaving ? undefined :
					{
						ariaLabel: shortcutAriaLabel.primary( 's' ),
						display: displayShortcut.primary( 's' ),
					}
				}
				isBusy={ screenData.isSaving }
				disabled={ screenData.isSaving }
				accessibleWhenDisabled={ true }
				onClick={ () => saveTableChanges( { screenData, updateScreenData, tableOptions, tableMeta, updateTableMeta, noticeOperations, noticesStoreDispatch } ) }
			/>
			{
				( screenData.isSaving || screenData.previewIsLoading ) && (
					<Spinner style={ { margin: 0} }	/>
				)
			}
		</HStack>
		{ noticeUI }
	</VStack>
	);
};

// A copy of the section to be used for the top buttons, without keyboard shortcuts.
const SectionWithNotices = withNotices( Section );

// A copy of the section to be used for the bottom buttons, with keyboard shortcuts.
const SectionWithKeyboardShortcutsWithNotices = withNotices( ( props ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const saveChangesCallback = ( event ) => {
		event.preventDefault();
		// Blur the focussed element to make sure that all `change` and `blur` events were triggered.
		document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element
		// Don't save changes directly, but trigger a save on the next render.
		props.updateScreenData( { triggerSaveChanges: true } );
	};

	/*
	 * Save changes when the `triggerSaveChanges` screen data option is set.
	 * This ensures that the save function receives the latest data, after all `change` and `blur` events were triggered.
	 */
	useEffect( () => {
		if ( props.screenData.triggerSaveChanges ) {
			props.updateScreenData( { triggerSaveChanges: false } );
			saveTableChanges( { ...props, noticesStoreDispatch } );
		}
	}, [ props, noticesStoreDispatch ] );

	const shortcuts = {
		'mod+s': saveChangesCallback,
	};

	if ( tp.screenOptions.currentUserCanPreviewTable ) {
		const showPreviewCallback = ( event ) => {
			event.preventDefault();
			// Blur the focussed element to make sure that all `change` and `blur` events were triggered.
			document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element
			// Don't load the preview directly, but trigger it on the next render.
			props.updateScreenData( { triggerPreview: true } );
		};

		/*
		* Trigger the preview when the `triggerPreview` screen data option is set.
		* This ensures that the save function receives the latest data, after all `change` and `blur` events were triggered.
		*/
		useEffect( () => {
			if ( props.screenData.triggerPreview ) {
				props.updateScreenData( { triggerPreview: false } );
				showPreview( props );
			}
		}, [ props ] );

		shortcuts[ 'mod+p' ] = showPreviewCallback;
	}

	return (
		<>
			<Section { ...props } />
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ shortcuts }
			/>
			<Notifications />
		</>
	);
} );

initializeReactComponentInPortal(
	'tablepress_edit-buttons-top',
	'edit',
	SectionWithNotices,
);

initializeReactComponentInPortal(
	'tablepress_edit-buttons-bottom',
	'edit',
	SectionWithKeyboardShortcutsWithNotices,
);
