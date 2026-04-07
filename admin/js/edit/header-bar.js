/**
 * JavaScript code for the "Edit" section integration of the "Header Bar".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.3.0
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useEffect, useRef, useState } from 'react';
import {
	Button,
	DropdownMenu,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	KeyboardShortcuts,
	MenuGroup,
	MenuItem,
	Modal,
	Spinner,
	Tooltip,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	withNotices,
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { __, _n, sprintf } from '@wordpress/i18n';
import { arrowUp, blockTable, copy, download, info, moreVertical, pencil, settings, shortcode, siteLogo, trash } from '@wordpress/icons';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import processAjaxRequest from '../common/ajax-request';
import { Notifications } from '../common/notifications';
import { TablePressIcon, TablePressIconSimple } from '../../img/tablepress-icon';

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

/**
 * Returns the Confirm Delete Modal component's JSX markup.
 *
 * @param {Object}   props                         Component props.
 * @param {string}   props.title                   Title of the modal.
 * @param {string}   props.deleteUrl               URL to delete the table.
 * @param {Function} props.closeConfirmDeleteModal Callback to close the Confirm Delete modal.
 * @return {Object} Confirm Delete Modal component.
 */
const ConfirmDeleteModal = ( { title, deleteUrl, closeConfirmDeleteModal } ) => {
	const cancelButtonRef = useRef();
	const deleteButtonRef = useRef();

	const handleEnter = useCallback(
		( event ) => {
			// Avoid triggering the action when a button is focused, as this can cause a double submission.
			const isCancelOrDeleteButton =
				event.target === cancelButtonRef.current ||
				event.target === deleteButtonRef.current;

			if ( ! isCancelOrDeleteButton && 'Enter' === event.key ) {
				deleteButtonRef.current.click();
			}
		},
		[],
	);

	return (
		<Modal
			size="medium"
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			isDismissible={ false }
			onKeyDown={ handleEnter }
			onRequestClose={ closeConfirmDeleteModal }
		>
			<VStack spacing={ 8 }>
				<span>
					{ _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', 1, 'tablepress' ) }
					<br />
					{ __( 'Deleting a table is permanent and can not be undone!', 'tablepress' ) }
				</span>
				<HStack alignment="right">
					<Button
						ref={ cancelButtonRef }
						variant="tertiary"
						text={ __( 'Cancel', 'tablepress' ) }
						onClick={ closeConfirmDeleteModal }
					/>
					<Button
						ref={ deleteButtonRef }
						variant="primary"
						isDestructive={ true }
						href={ deleteUrl }
						text={ __( 'Delete', 'tablepress' ) }
						onClick={ () => {
							// Prevent onunload warning, by calling the unset method.
							tp.helpers.unsaved_changes.unset();
							closeConfirmDeleteModal();
						} }
					/>
				</HStack>
			</VStack>
		</Modal>
	);
};

const HeaderBar = ( { noticeOperations, noticeUI, screenData, updateScreenData, tableOptions, tableMeta, updateTableMeta, features } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );
	const { createSuccessNotice } = noticesStoreDispatch;
	const [ confirmDeleteModalIsOpen, setConfirmDeleteModalIsOpen ] = useState( false );

	// Copy shortcode to clipboard.
	const shortcodeText = `[${ tp.table.shortcode } id=${ tableMeta.id } /]`;
	const copyShortcodeButtonRef = useCopyToClipboard( shortcodeText, () => {
		createSuccessNotice(
			__( 'Copied Shortcode to clipboard.', 'tablepress' ),
			{
				type: 'snackbar',
				icon: <Icon icon={ TablePressIconSimple } />,
			}
		);
	} );

	const scrollToTop = () => window.scrollTo( { top: 0, behavior: 'smooth' } );

	const featureModules = Object.keys( tp?.modules ?? {} )
		.filter( ( module ) => features.includes( module ) )
		.map( ( module ) => ( {
			module,
			name: tp.modules[ module ].name,
		} ) )
		.sort( ( a, b ) => a.name.localeCompare( b.name ) );

	return (
		<VStack>
			<HStack style={ { maxWidth: '1000px' } }>
				<HStack alignment="left" spacing={ 3 } expanded={ false }>
					{ /* Left side: Quick Navigation, Table ID, Table name */ }
					<DropdownMenu
						icon={ TablePressIconSimple }
						label={ __( 'Quick navigation', 'tablepress' ) }
						open={ screenData.quickNavigationDropdownIsOpen }
						onToggle={ ( newQuickNavigationDropdownIsOpen ) => updateScreenData( { quickNavigationDropdownIsOpen: newQuickNavigationDropdownIsOpen } ) }
						toggleProps={ {
							onDoubleClick: scrollToTop, /* Scroll to Top on Double-click. (Double-click will also close the dropdown.) */
							shortcut: {
								ariaLabel: shortcutAriaLabel.primary( 'j' ),
								display: displayShortcut.primary( 'j' ),
							},
						} }
					>
						{ ( { onClose: closeMenu } ) => (
							<>
								<MenuGroup>
									<MenuItem
										icon={ arrowUp }
										onClick={ () => {
											scrollToTop();
											closeMenu();
										} }
									>
										{ __( 'Scroll to Top', 'tablepress' ) }
									</MenuItem>
								</MenuGroup>
								<MenuGroup label={ featureModules.length > 0 && __( 'Common', 'tablepress' ) }>
									{
										[
											{ section: 'table-information', label: __( 'Table Information', 'tablepress' ), icon: info },
											{ section: 'table-data', label: __( 'Table Content', 'tablepress' ), icon: blockTable },
											{ section: 'table-manipulation', label: __( 'Table Manipulation', 'tablepress' ), icon: pencil },
											{ section: 'table-options', label: __( 'Table Options', 'tablepress' ), icon: settings },
											{ section: 'datatables-features', label: __( 'Table Features for Site Visitors', 'tablepress' ), icon: siteLogo },
										].map( ( { section, label, icon } ) => (
											<MenuItem
												key={ section }
												icon={ icon }
												onClick={ () => {
													document.getElementById( `tablepress_edit-${section}` ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
													closeMenu();
												} }
											>
												{ label }
											</MenuItem>
										) )
									}
								</MenuGroup>
								{ featureModules.length > 0 && (
									<MenuGroup label={ __( 'Feature Modules', 'tablepress' ) }>
										{ featureModules.map( ( { module } ) => (
											<MenuItem
												key={ module }
												onClick={ () => {
													document.getElementById( `tablepress_edit-${module}` ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
													closeMenu();
												} }
											>
												{ tp.modules[ module ].name }
											</MenuItem>
										) ) }
									</MenuGroup>
								) }
							</>
						) }
					</DropdownMenu>
					<span
						className="tablepress-header-table-id"
						style={ { flexShrink: 0 } }>
						{ sprintf( __( 'ID: %s', 'tablepress' ), tableMeta.id ) }
					</span>
					<Tooltip text={ sprintf( __( 'Table Name: %s', 'tablepress' ), tableMeta.name ) }>
						<h2 className="tablepress-header-table-name">
							{ tableMeta.name }
						</h2>
					</Tooltip>
				</HStack>
				<HStack alignment="right" spacing={ 3 } expanded={ false } style={ { flexShrink: 0 } }>
					{ /* Right side: Spinner, Preview, Save Changes, Dropdown */ }
					{
						( screenData.isSaving || screenData.previewIsLoading ) && (
							<Spinner style={ { margin: 0 } } />
						)
					}
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
					<DropdownMenu
						icon={ moreVertical }
						label={ __( 'More actions', 'tablepress' ) }
					>
						{ ( { onClose: closeMenu } ) => (
							<>
								<MenuGroup>
									<MenuItem
										ref={ copyShortcodeButtonRef }
										icon={ shortcode }
										info={ shortcodeText }
										onClick={ () => closeMenu() }
									>
										{ __( 'Copy Shortcode', 'tablepress' ) }
									</MenuItem>
								</MenuGroup>
								{ ( tp.screenOptions.currentUserCanCopyTable || tp.screenOptions.currentUserCanExportTable ) && (
									<MenuGroup>
										{ tp.screenOptions.currentUserCanCopyTable && (
											<MenuItem
												icon={ copy }
												href={ screenData.copyUrl }
											>
												{ __( 'Copy Table', 'tablepress' ) }
											</MenuItem>
										) }
										{ tp.screenOptions.currentUserCanExportTable && (
											<MenuItem
												icon={ download }
												href={ screenData.exportUrl }
											>
												{ __( 'Export Table', 'tablepress' ) }
											</MenuItem>
										) }
									</MenuGroup>
								) }
								{ tp.screenOptions.currentUserCanDeleteTable && (
									<MenuGroup>
										<MenuItem
											icon={ trash }
											isDestructive={ true }
											href={ screenData.deleteUrl }
											onClick={ ( event ) => {
												setConfirmDeleteModalIsOpen( true );
												event.preventDefault();
												closeMenu();
											} }
										>
											{ __( 'Delete Table', 'tablepress' ) }
										</MenuItem>
									</MenuGroup>
								) }
							</>
						) }
					</DropdownMenu>
				</HStack>
			</HStack>
			{ noticeUI }
			{ confirmDeleteModalIsOpen && (
				<ConfirmDeleteModal
					title={ sprintf( __( 'Delete “%1$s” (ID %2$s)', 'tablepress' ), tableMeta.name, tableMeta.id ) }
					deleteUrl={ screenData.deleteUrl }
					closeConfirmDeleteModal={ () => setConfirmDeleteModalIsOpen( false ) }
				/>
			) }
		</VStack>
	);
};

// A copy of the section to be used for the bottom buttons, with keyboard shortcuts.
const HeaderBarWithKeyboardShortcutsWithNotices = withNotices( ( props ) => {
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

	const openNavigationDropdownCallback = ( event ) => {
		event.preventDefault();
		props.updateScreenData( { quickNavigationDropdownIsOpen: ! props.screenData.quickNavigationDropdownIsOpen } );
	};

	const shortcuts = {
		'mod+s': saveChangesCallback,
		'mod+j': openNavigationDropdownCallback,
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
			<HeaderBar { ...props } />
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ shortcuts }
			/>
			<Notifications />
		</>
	);
} );

initializeReactComponentInPortal(
	'header-bar',
	'edit',
	HeaderBarWithKeyboardShortcutsWithNotices,
);
