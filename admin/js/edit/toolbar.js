/**
 * JavaScript code for the "Edit" section integration of the "Toolbar".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.4.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useState } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Modal,
	Toolbar,
	ToolbarButton,
	ToolbarGroup,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { addAction, removeAction } from '@wordpress/hooks';
import { __, _n, sprintf } from '@wordpress/i18n';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import {
	arrowDown,
	arrowLeft,
	arrowRight,
	arrowUp,
	column,
	copy,
	copySmall,
	image,
	link,
	plus,
	redo,
	seen,
	trash,
	undo,
	unseen,
} from '@wordpress/icons';
import { Menu } from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { Alert } from '../common/alert';
import { HelpBox } from '../common/help';
import {
	cellsMerge,
	cut,
	formatting,
	insertColumn,
	insertColumnBefore,
	insertColumnAfter,
	insertRow,
	insertRowBefore,
	insertRowAfter,
	moveFirst,
	moveLast,
	moveLeftRight,
	moveTop,
	moveBottom,
	moveUpDown,
	paste,
	row,
	sort,
	sortAsc,
	sortDesc,
 } from '../../img/toolbar-icons';

/**
 * Returns the Editor Toolbar component's JSX markup.
 *
 * @return {Object} Editor Toolbar component.
 */
const Section = () => {
	const [ columnsAppendNumber, setColumnsAppendNumber ] = useState( 1 );
	const [ rowsAppendNumber, setRowsAppendNumber ] = useState( 1 );
	const [ alertMoveInvalidIsShown, setAlertMoveInvalidIsShown ] = useState( false );
	const [ alertDeleteRowsInvalidIsShown, setAlertDeleteRowsInvalidIsShown ] = useState( false );
	const [ alertDeleteColumnsInvalidIsShown, setAlertDeleteColumnsInvalidIsShown ] = useState( false );
	const [ alertMergeProhibitedIsShown, setAlertMergeProhibitedIsShown ] = useState( false );
	const [ modalAddRowsIsShown, setModalAddRowsIsShown ] = useState( false );
	const [ modalAddColumnsIsShown, setModalAddColumnsIsShown ] = useState( false );
	const [ editorSelection, setEditorSelection ] = useState( () => ( { ...tp.helpers.selection } ) );
	const [ tableSize, setTableSize ] = useState( () => ( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } ) );
	const [ toolbarUndoRedoState, setToolbarUndoRedoState ] = useState( { undoDisabled: true, redoDisabled: true } );
	const [ cellsMergeErrorMessage, setCellsMergeErrorMessage ] = useState( '' );

	const numSelectedRows = editorSelection.rows.length;
	const numSelectedColumns = editorSelection.columns.length;

	/*
	// @todo Conversion of dynamic state update via addAction/doAction?

	// When the component is first rendered, register the action hook that is triggered when the editor selection is changed.
	useEffect( () => {
		addAction( 'tablepress.selectionChanged', 'tp/edit-screen/handle-selection-changed', ( meta ) => {
			setEditorSelection( { ...meta } );
		} );

		return () => {
			removeAction( 'tablepress.selectionChanged', 'tp/edit-screen/handle-selection-changed' );
		};
	}, [] );
	// */

	useEffect( () => {
		addAction( 'tablepress.toolbarUndoRedoStateUpdate', 'tp/edit-screen/handle-toolbar-undo-redo-state-update', ( newToolbarUndoRedoState ) => {
			setToolbarUndoRedoState( newToolbarUndoRedoState );
		} );

		return () => {
			removeAction( 'tablepress.toolbarUndoRedoStateUpdate', 'tp/edit-screen/handle-toolbar-undo-redo-state-update' );
		};
	}, [] );

	/**
	 * Merges/combines the selected cells, if allowed.
	 */
	const mergeCells = () => {
		// Call-by-reference object for the cell_merge_allowed() call.
		const errorMessage = {
			text: '',
		};

		if ( ! tp.helpers.cell_merge_allowed( errorMessage ) ) {
			setCellsMergeErrorMessage( errorMessage.text );
			setAlertMergeProhibitedIsShown( true );
			return;
		}

		tp.callbacks.merge_cells();
	};

	/**
	 * Checks whether the selected rows or columns contain at least one element of the specific visibility.
	 *
	 * @param {string} type       The type of the selected items to check ('rows' or 'columns').
	 * @param {number} visibility Visibility state to check for (0 for hidden, 1 for visible).
	 * @return {boolean} True if the entry shall be shown, false if not.
	 */
	const selectionVisibilityContains = ( type, visibility ) => {
		return editorSelection[ type ].some( ( rocIdx ) => ( tp.table.visibility[ type ][ rocIdx ] === visibility ) );
	};

	/**
	 * Determines whether moving the rows/columns of the current selection is allowed.
	 *
	 * @param {string} type      The type of the selected items to move ('rows' or 'columns').
	 * @param {string} direction The direction to move ('up', 'down', 'left', 'right', 'top', 'bottom', 'first', 'last').
	 * @return {boolean} Whether the move is allowed or not.
	 */
	const moveAllowed = ( type, direction ) => {
		// When moving up or left, or to top or to first, test the first row/column of the selected range.
		let rocToTest = editorSelection[ type ][0];
		let minMaxRoC = 0; // First row/column.
		// When moving down or right, or to bottom or to last, test the last row/column of the selected range.
		if ( [ 'down', 'right', 'bottom', 'last' ].includes( direction ) ) {
			rocToTest = editorSelection[ type ][ editorSelection[ type ].length - 1 ];
			minMaxRoC = tableSize[ type ] - 1; // Last row/column.
		}

		// Moving is allowed if the first/last row/column is not already at the target edge.
		return minMaxRoC !== rocToTest;
	};

	/**
	 * Moves the selected rows or columns.
	 *
	 * @param {string} direction The direction to move ('up', 'down', 'left', 'right', 'top', 'bottom', 'first', 'last').
	 * @param {string} type      The type of the selected items to move ('rows' or 'columns').
	 */
	const move = ( direction, type ) => {
		if ( ! moveAllowed( type, direction ) ) {
			setAlertMoveInvalidIsShown( true );
			return;
		}

		tp.callbacks.move( direction, type );
	};

	/**
	 * Removes the selected rows or columns.
	 *
	 * @param {string} type The type of the selected items to remove ('rows' or 'columns').
	 */
	const remove = ( type ) => {
		// Prevent deleting all rows or columns.
		if ( tableSize[ type ] === editorSelection[ type ].length ) {
			if ( 'rows' === type ) {
				setAlertDeleteRowsInvalidIsShown( true );
			} else {
				setAlertDeleteColumnsInvalidIsShown( true );
			}
			return;
		}

		tp.callbacks.remove( type );
	};

	return (
		<HStack
			spacing={ 1 }
			alignment="left"
		>
			<HStack
				alignment="left"
				className="tablepress-toolbar-scroll-wrapper"
			>
				<Toolbar
					label={ __( 'Table Manipulation', 'tablepress' ) }
					style={ {
						borderColor: '#c3c4c7',
					} }
				>
					<ToolbarGroup
						style={ {
							borderRightColor: '#c3c4c7',
						} }
					>
						<ToolbarButton
							icon={ undo }
							label={ __( 'Undo', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'z' ),
								display: displayShortcut.primary( 'z' ),
							} }
							onClick={ () => tp.editor.undo() }
							disabled={ toolbarUndoRedoState.undoDisabled }
						/>
						<ToolbarButton
							icon={ redo }
							label={ __( 'Redo', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'y' ),
								display: displayShortcut.primary( 'y' ),
							} }
							onClick={ () => tp.editor.redo() }
							disabled={ toolbarUndoRedoState.redoDisabled }
						/>
					</ToolbarGroup>
					<ToolbarGroup
						style={ {
							borderRightColor: '#c3c4c7',
						} }
					>
						<ToolbarButton
							icon={ cut }
							label={ __( 'Cut', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'x' ),
								display: displayShortcut.primary( 'x' ),
							} }
							onClick={ () => {
								/* eslint-disable @wordpress/no-global-active-element */
								if ( 'TEXTAREA' === document.activeElement.tagName && document.activeElement.selectionStart !== document.activeElement.selectionEnd ) {
									// @todo Does not work, likely due to blur event!
									document.execCommand( 'copy' ); // If text is selected in the actively edited cell, only copy that.
									const cursorPosition = document.activeElement.selectionStart;
									document.activeElement.value = document.activeElement.value.slice( 0, document.activeElement.selectionStart ) + document.activeElement.value.slice( document.activeElement.selectionEnd ); // Cut the selected content.
									document.activeElement.selectionEnd = cursorPosition;
								} else {
									tp.editor.copy( true ); // Otherwise, copy highlighted cells.
									tp.editor.setValue( tp.editor.highlighted, '' ); // Make cell content empty.
								}
								/* eslint-enable @wordpress/no-global-active-element */
							} }
						/>
						<ToolbarButton
							icon={ copy }
							label={ __( 'Copy', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'c' ),
								display: displayShortcut.primary( 'c' ),
							} }
							onClick={ () => {
								if ( 'TEXTAREA' === document.activeElement.tagName && document.activeElement.selectionStart !== document.activeElement.selectionEnd ) { // eslint-disable-line @wordpress/no-global-active-element
									// @todo Does not work, likely due to blur event!
									document.execCommand( 'copy' ); // If text is selected in the actively edited cell, only copy that.
								} else {
									tp.editor.copy( true ); // Otherwise, copy highlighted cells.
								}
							} }
						/>
						<ToolbarButton
							icon={ paste }
							label={ __( 'Paste', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'v' ),
								display: displayShortcut.primary( 'v' ),
							} }
							onClick={ () => {
								/* eslint-disable @wordpress/no-global-active-element */
								if ( 'TEXTAREA' === document.activeElement.tagName ) {
									// @todo Does not work, likely due to blur event!
									window.navigator.clipboard.readText().then( ( text ) => {
										if ( text ) {
											const cursorPosition = document.activeElement.selectionStart + text.length;
											document.activeElement.value = document.activeElement.value.slice( 0, document.activeElement.selectionStart ) + text + document.activeElement.value.slice( document.activeElement.selectionEnd ); // Paste at the selection.
											document.activeElement.selectionEnd = cursorPosition;
										}
									} );
								} else if ( tp.editor.selectedCell ) {
									window.navigator.clipboard.readText().then( ( text ) => {
										if ( text ) {
											tp.editor.paste( tp.editor.selectedCell[0], tp.editor.selectedCell[1], text );
										}
									} );
								}
								/* eslint-enable @wordpress/no-global-active-element */
							} }
							// Older browser don't offer the readText() method, so "Paste" needs to be disabled.
							disabled={ ! window?.navigator?.clipboard?.readText }
							extraProps={ {
								title: ! window?.navigator?.clipboard?.readText ? __( 'Your browser does not allow pasting via the context menu. Use the keyboard shortcut instead.', 'tablepress' ) : undefined,
							} }
						/>
					</ToolbarGroup>
					<ToolbarGroup
						style={ {
							borderRightColor: '#c3c4c7',
						} }
					>
						<ToolbarButton
							icon={ link }
							label={ __( 'Insert Link', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'l' ),
								display: displayShortcut.primary( 'l' ),
							} }
							onClick={ () => tp.callbacks.insert_link.open_dialog() } // @todo Add parameter `( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null`?
						/>
						<ToolbarButton
							icon={ image }
							label={ __( 'Insert Image', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'i' ),
								display: displayShortcut.primary( 'i' ),
							} }
							onClick={ () => tp.callbacks.insert_image.open_dialog() } // @todo Add parameter `( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null`?
						/>
						<ToolbarButton
							icon={ formatting }
							label={ __( 'Advanced Editor', 'tablepress' ) }
							shortcut={ {
								ariaLabel: shortcutAriaLabel.primary( 'e' ),
								display: displayShortcut.primary( 'e' ),
							} }
							onClick={ () => tp.callbacks.advanced_editor.open_dialog() } // @todo Add parameter `( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null`?
						/>
						<ToolbarButton
							icon={ cellsMerge }
							label={ __( 'Combine/Merge cells', 'tablepress' ) }
							onClick={ () => {
								mergeCells();
							} }
							/*
							// @todo After conversion to dynamic state update via addAction/doAction?
							disabled={ ( 1 === numSelectedRows && 1 === numSelectedColumns ) || ! tp.helpers.cell_merge_allowed() }
							extraProps={ {
								title: ( 1 === numSelectedRows && 1 === numSelectedColumns ) || ! tp.helpers.cell_merge_allowed( errorMessage ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + errorMessage.text : undefined,
							} }
							*/
						/>
					</ToolbarGroup>
					<ToolbarGroup
						style={ {
							borderRightColor: '#c3c4c7',
						} }
					>
						<Menu.Root modal={ false }>
							<Menu.Trigger
								render={
									<ToolbarButton
										icon={ row }
										label={ __( 'Actions for rows', 'tablepress' ) }
										onClick={ () => {
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
											tp.helpers.visibility.update(); // Update information about hidden rows and columns.
										} }
									>
										<span className="tablepress-toolbar-button-text">
											{ __( 'Rows…', 'tablepress' ) }
										</span>
									</ToolbarButton>
								}
							/>
							<Menu.Popup>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ copySmall } /> }
									onClick={ ( event ) => {
										tp.callbacks.insert_duplicate( 'duplicate', 'rows' );
										if ( event.shiftKey ) {
											event.preventBaseUIHandler();
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
										}
									} }
								>
									<Menu.ItemLabel>
										{ _n( 'Duplicate row', 'Duplicate rows', numSelectedRows, 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ insertRow } /> }
									>
										<Menu.ItemLabel>
											{ _n( 'Insert row…', 'Insert rows…', numSelectedRows, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ insertRowBefore } /> }
											onClick={ ( event ) => {
												tp.callbacks.insert_duplicate( 'insert', 'rows', 'before' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
										>
											<Menu.ItemLabel>
												{ _n( 'Insert row above', 'Insert rows above', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ insertRowAfter } /> }
											onClick={ ( event ) => {
												tp.callbacks.insert_duplicate( 'insert', 'rows', 'after' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
										>
											<Menu.ItemLabel>
												{ _n( 'Insert row below', 'Insert rows below', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ plus } /> }
									onClick={ () => setModalAddRowsIsShown( true ) }
								>
									<Menu.ItemLabel>
										{ __( 'Add rows…', 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ trash } /> }
									onClick={ ( event ) => {
										remove( 'rows' );
										if ( event.shiftKey ) {
											event.preventBaseUIHandler();
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
										}
									} }
									disabled={ tableSize.rows === numSelectedRows }
									title={ tableSize.rows === numSelectedRows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'You can not delete all table rows!', 'tablepress' ) : undefined }
								>
									<Menu.ItemLabel>
										{ _n( 'Delete row', 'Delete rows', numSelectedRows, 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.Separator />
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ moveUpDown } /> }
										disabled={ 1 === tableSize.rows }
										title={ 1 === tableSize.rows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'The table has only one row.', 'tablepress' ) : undefined }
									>
										<Menu.ItemLabel>
											{ _n( 'Move row…', 'Move rows…', numSelectedRows, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ moveTop } /> }
											onClick={ ( event ) => {
												move( 'top', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }

											shortcut={ {
												label: shortcutAriaLabel.secondary( '↑' ),
												displayShortcut: displayShortcut.secondary( '↑' ),
											} }
											disabled={ ! moveAllowed( 'rows', 'top' ) }
											title={ ! moveAllowed( 'rows', 'top' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already at the top of the table.', 'The selected rows are already at the top of the table.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move row to the top', 'Move rows to the top', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ arrowUp } /> }
											onClick={ ( event ) => {
												move( 'up', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.primaryShift( '↑' ),
												displayShortcut: displayShortcut.primaryShift( '↑' ),
											} }
											disabled={ ! moveAllowed( 'rows', 'up' ) }
											title={ ! moveAllowed( 'rows', 'up' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already at the top of the table.', 'The selected rows are already at the top of the table.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move row up', 'Move rows up', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Separator />
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ arrowDown } /> }
											onClick={ ( event ) => {
												move( 'down', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.primaryShift( '↓' ),
												displayShortcut: displayShortcut.primaryShift( '↓' ),
											} }
											disabled={ ! moveAllowed( 'rows', 'down' ) }
											title={ ! moveAllowed( 'rows', 'down' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already at the bottom of the table.', 'The selected rows are already at the bottom of the table.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move row down', 'Move rows down', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ moveBottom } /> }
											onClick={ ( event ) => {
												move( 'bottom', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.secondary( '↓' ),
												displayShortcut: displayShortcut.secondary( '↓' ),
											} }
											disabled={ ! moveAllowed( 'rows', 'bottom' ) }
											title={ ! moveAllowed( 'rows', 'bottom' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already at the bottom of the table.', 'The selected rows are already at the bottom of the table.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move row to the bottom', 'Move rows to the bottom', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ sort } /> }
										disabled={ 1 !== numSelectedColumns || 1 === tableSize.rows }
										title={ 1 !== numSelectedColumns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'More than one column was selected.', 'tablepress' ) : ( 1 === tableSize.rows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'The table has only one row.', 'tablepress' ) : undefined ) } // eslint-disable-line no-nested-ternary
									>
										<Menu.ItemLabel>
											{ __( 'Sort rows…', 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ sortAsc } /> }
											onClick={ ( event ) => {
												tp.callbacks.sort( 'asc' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
												}
											} }
											disabled={ 1 !== numSelectedColumns || 1 === tableSize.rows }
											title={ 1 !== numSelectedColumns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'More than one column was selected.', 'tablepress' ) : ( 1 === tableSize.rows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'The table has only one row.', 'tablepress' ) : undefined ) } // eslint-disable-line no-nested-ternary
										>
											<Menu.ItemLabel>
												{ __( 'Sort by column ascending', 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ sortDesc } /> }
											onClick={ ( event ) => {
												tp.callbacks.sort( 'desc' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
												}
											} }
											disabled={ 1 !== numSelectedColumns || 1 === tableSize.rows }
											title={ 1 !== numSelectedColumns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'More than one column was selected.', 'tablepress' ) : ( 1 === tableSize.rows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'The table has only one row.', 'tablepress' ) : undefined ) } // eslint-disable-line no-nested-ternary
										>
											<Menu.ItemLabel>
												{ __( 'Sort by column descending', 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ unseen } /> }
									>
										<Menu.ItemLabel>
											{ _n( 'Hide/show row…', 'Hide/show rows…', numSelectedRows, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ unseen } /> }
											onClick={ ( event ) => {
												tp.callbacks.hide_unhide( 'hide', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
													tp.helpers.visibility.update(); // Update information about hidden rows and columns.
												}
											} }
											disabled={ ! selectionVisibilityContains( 'rows', 1 ) }
											title={ ! selectionVisibilityContains( 'rows', 1 ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already hidden.', 'The selected rows are already hidden.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Hide row', 'Hide rows', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ seen } /> }
											onClick={ ( event ) => {
												tp.callbacks.hide_unhide( 'unhide', 'rows' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
													tp.helpers.visibility.update(); // Update information about hidden rows and columns.
												}
											} }
											disabled={ ! selectionVisibilityContains( 'rows', 0 ) }
											title={ ! selectionVisibilityContains( 'rows', 0 ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected row is already visible.', 'The selected rows are already visible.', numSelectedRows, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Show row', 'Show rows', numSelectedRows, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
							</Menu.Popup>
						</Menu.Root>
						<Menu.Root modal={ false }>
							<Menu.Trigger
								render={
									<ToolbarButton
										icon={ column }
										label={ __( 'Actions for columns', 'tablepress' ) }
										onClick={ () => {
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
											tp.helpers.visibility.update(); // Update information about hidden rows and columns.
										} }
									>
										<span className="tablepress-toolbar-button-text">
											{ __( 'Columns…', 'tablepress' ) }
										</span>
									</ToolbarButton>
								}
							/>
							<Menu.Popup>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ copySmall } /> }
									onClick={ ( event ) => {
										tp.callbacks.insert_duplicate( 'duplicate', 'columns' );
										if ( event.shiftKey ) {
											event.preventBaseUIHandler();
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
										}
									} }
								>
									<Menu.ItemLabel>
										{ _n( 'Duplicate column', 'Duplicate columns', numSelectedColumns, 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ insertColumn } /> }
									>
										<Menu.ItemLabel>
											{ _n( 'Insert column…', 'Insert columns…', numSelectedColumns, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ insertColumnBefore } /> }
											onClick={ ( event ) => {
												tp.callbacks.insert_duplicate( 'insert', 'columns', 'before' )
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
										>
											<Menu.ItemLabel>
												{ _n( 'Insert column on the left', 'Insert columns on the left', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ insertColumnAfter } /> }
											onClick={ ( event ) => {
												tp.callbacks.insert_duplicate( 'insert', 'columns', 'after' )
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
										>
											<Menu.ItemLabel>
												{ _n( 'Insert column on the right', 'Insert columns on the right', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ plus } /> }
									onClick={ () => setModalAddColumnsIsShown( true ) }
								>
									<Menu.ItemLabel>
										{ __( 'Add columns…', 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ trash } /> }
									onClick={ ( event ) => {
										remove( 'columns' );
										if ( event.shiftKey ) {
											event.preventBaseUIHandler();
											// @todo Remove after conversion to dynamic state update via addAction/doAction.
											setEditorSelection( { ...tp.helpers.selection } );
											setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
										}
									} }
									disabled={ tableSize.columns === numSelectedColumns }
									title={ tableSize.columns === numSelectedColumns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'You can not delete all table columns!', 'tablepress' ) : undefined }
								>
									<Menu.ItemLabel>
										{ _n( 'Delete column', 'Delete columns', numSelectedColumns, 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.Separator />
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ moveLeftRight } /> }
										disabled={ 1 === tableSize.columns }
										title={ 1 === tableSize.columns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'The table has only one column.', 'tablepress' ) : undefined }
									>
										<Menu.ItemLabel>
											{ _n( 'Move column…', 'Move columns…', numSelectedColumns, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ moveFirst } /> }
											onClick={ ( event ) => {
												move( 'first', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }

											shortcut={ {
												label: shortcutAriaLabel.secondary( '←' ),
												displayShortcut: displayShortcut.secondary( '←' ),
											} }
											disabled={ ! moveAllowed( 'columns', 'first' ) }
											title={ ! moveAllowed( 'columns', 'first' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already at the left edge of the table.', 'The selected columns are already at the left edge of the table.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move column to first', 'Move columns to first', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ arrowLeft } /> }
											onClick={ ( event ) => {
												move( 'left', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.primaryShift( '←' ),
												displayShortcut: displayShortcut.primaryShift( '←' ),
											} }
											disabled={ ! moveAllowed( 'columns', 'left' ) }
											title={ ! moveAllowed( 'columns', 'left' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already at the left edge of the table.', 'The selected columns are already at the left edge of the table.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move column left', 'Move columns left', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Separator />
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ arrowRight } /> }
											onClick={ ( event ) => {
												move( 'right', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.primaryShift( '→' ),
												displayShortcut: displayShortcut.primaryShift( '→' ),
											} }
											disabled={ ! moveAllowed( 'columns', 'right' ) }
											title={ ! moveAllowed( 'columns', 'right' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already at the right edge of the table.', 'The selected columns are already at the right edge of the table.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move column right', 'Move columns right', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ moveLast } /> }
											onClick={ ( event ) => {
												move( 'last', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
												}
											} }
											shortcut={ {
												label: shortcutAriaLabel.secondary( '→' ),
												displayShortcut: displayShortcut.secondary( '→' ),
											} }
											disabled={ ! moveAllowed( 'columns', 'last' ) }
											title={ ! moveAllowed( 'columns', 'last' ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already at the right edge of the table.', 'The selected columns are already at the right edge of the table.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Move column to last', 'Move columns to last', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
								<Menu.SubmenuRoot>
									<Menu.SubmenuTrigger
										prefix={ <Menu.PrefixIcon icon={ unseen } /> }
									>
										<Menu.ItemLabel>
											{ _n( 'Hide/show column…', 'Hide/show columns…', numSelectedColumns, 'tablepress' ) }
										</Menu.ItemLabel>
									</Menu.SubmenuTrigger>
									<Menu.Popup
										positioner={ <Menu.Positioner align="center" /> }
									>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ unseen } /> }
											onClick={ ( event ) => {
												tp.callbacks.hide_unhide( 'hide', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
													tp.helpers.visibility.update(); // Update information about hidden rows and columns.
												}
											} }
											disabled={ ! selectionVisibilityContains( 'columns', 1 ) }
											title={ ! selectionVisibilityContains( 'columns', 1 ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already hidden.', 'The selected columns are already hidden.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Hide column', 'Hide columns', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ seen } /> }
											onClick={ ( event ) => {
												tp.callbacks.hide_unhide( 'unhide', 'columns' );
												if ( event.shiftKey ) {
													event.preventBaseUIHandler();
													// @todo Remove after conversion to dynamic state update via addAction/doAction.
													setEditorSelection( { ...tp.helpers.selection } );
													setTableSize( { rows: tp.editor.options.data.length, columns: tp.editor.options.columns.length } );
													tp.helpers.visibility.update(); // Update information about hidden rows and columns.
												}
											} }
											disabled={ ! selectionVisibilityContains( 'columns', 0 ) }
											title={ ! selectionVisibilityContains( 'columns', 0 ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + _n( 'The selected column is already visible.', 'The selected columns are already visible.', numSelectedColumns, 'tablepress' ) : undefined }
										>
											<Menu.ItemLabel>
												{ _n( 'Show column', 'Show columns', numSelectedColumns, 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</Menu.Popup>
								</Menu.SubmenuRoot>
							</Menu.Popup>
						</Menu.Root>
					</ToolbarGroup>
				</Toolbar>
				<HelpBox
					title={ __( 'Table Editor Toolbar', 'tablepress' ) }
					buttonProps={ {
						size: 'compact',
						label: __( 'Help on the Table Editor Toolbar', 'tablepress' ),
						variant: 'icon',
					} }
					modalProps={ {
						size: 'large',
					} }
				>
					<p>
						{ __( 'The toolbar provides a set of tools for editing the data in your tables.', 'tablepress' ) }
						{ ' ' }
						{ __( 'You can use the Undo and Redo buttons to revert or reapply changes to your table.', 'tablepress' ) }
						{ ' ' }
						{ __( 'The Cut, Copy, and Paste buttons allow you to manipulate the data in your table.', 'tablepress' ) }
						{ ' ' }
						{ sprintf( __( 'Buttons for inserting links or images, for opening the “%1$s” with more styling options, and for combining or merging cells are available as well.', 'tablepress' ), __( 'Advanced Editor', 'tablepress' ) ) }
					</p>
					<RawHTML>
						{ '<p>' }
						{ sprintf( __( 'In addition, the toolbar provides quick access to common actions for editing the structure of your table, via the “%1$s” and “%2$s” buttons.', 'tablepress' ), __( 'Rows…', 'tablepress' ), __( 'Columns…', 'tablepress' ) ) }
						{ ' ' + __( 'Use these buttons to add, insert, duplicate, delete, sort, show/hide, or move rows and columns.', 'tablepress' ) }
						{ ' ' + __( 'If you’d like to repeat the same action multiple times, hold down the <kbd>Shift</kbd> key while clicking the action to keep the current menu open.', 'tablepress' ) }
						{ '</p>' }
					</RawHTML>
					<p>
						{ __( 'Most actions can also be performed by typing a keyboard shortcut, which is shown in the button’s tooltip or the menus.', 'tablepress' ) }
					</p>
					<h2>{ __( 'Help on combining cells', 'tablepress' ) }</h2>
					<RawHTML>
						{ '<p>' }
						{ __( 'Table cells can span across more than one column or row.', 'tablepress' ) }
						{ ' ' + __( 'Combining consecutive cells within the same row is called “colspanning”.', 'tablepress' ) }
						{ ' ' + __( 'Combining consecutive cells within the same column is called “rowspanning”.', 'tablepress' ) }
						{ '</p>' }
					</RawHTML>
					<RawHTML>
						{ '<p>' }
						{ sprintf( __( 'To combine adjacent cells, select the desired cells and click the “%s” button or use the context menu.', 'tablepress' ), __( 'Combine/Merge cells', 'tablepress' ) ) }
						{ ' ' + __( 'The corresponding keywords, <code>#colspan#</code> and <code>#rowspan#</code>, will then be added for you.', 'tablepress' ) }
						{ '</p>' }
					</RawHTML>
					<p>
						<strong>
							{ __( 'Be aware that the Table Features for Site Visitors, like sorting, filtering, and pagination, will not work in tables which have combined cells in their body rows.', 'tablepress' ) }
						</strong>
						{ ' ' }
						{ __( 'It is however possible to use these features in tables that have combined cells in the table header or footer rows, to allow for creating complex header and footer layouts.', 'tablepress' ) }
					</p>
					<h2>{ __( 'More information', 'tablepress' ) }</h2>
					<p>
						{ /* eslint-disable-next-line react/jsx-no-target-blank */ }
						<a
							href="https://tablepress.org/documentation/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen-help-box"
							target="_blank"
						>
							{ __( 'Documentation', 'tablepress' ) }
						</a>
						{ " · " }
						{ /* eslint-disable-next-line react/jsx-no-target-blank */ }
						<a
							href="https://tablepress.org/faq/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen-help-box"
							target="_blank"
						>
							{ __( 'Frequently Asked Questions', 'tablepress' ) }
						</a>
						{ " · " }
						{ /* eslint-disable-next-line react/jsx-no-target-blank */ }
						<a
							href="https://tablepress.org/tutorials/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen-help-box"
							target="_blank"
						>
							{ __( 'Tutorials', 'tablepress' ) }
						</a>
					</p>
				</HelpBox>
			</HStack>
			{ alertMergeProhibitedIsShown && (
				<Alert
					icon={ cellsMerge }
					title={ __( 'Combine/Merge cells', 'tablepress' ) }
					text={ cellsMergeErrorMessage }
					onConfirm={ () => setAlertMergeProhibitedIsShown( false ) }
					modalProps={ {
						size: 'small',
					} }
				/>
			) }
			{ alertMoveInvalidIsShown && (
				<Alert
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not do this move, because you reached the border of the table.', 'tablepress' ) }
					onConfirm={ () => setAlertMoveInvalidIsShown( false ) }
					modalProps={ {
						size: 'small',
					} }
				/>
			) }
			{ alertDeleteRowsInvalidIsShown && (
				<Alert
					icon={ trash }
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not delete all table rows!', 'tablepress' ) }
					onConfirm={ () => setAlertDeleteRowsInvalidIsShown( false ) }
				/>
			) }
			{ alertDeleteColumnsInvalidIsShown && (
				<Alert
					icon={ trash }
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not delete all table columns!', 'tablepress' ) }
					onConfirm={ () => setAlertDeleteColumnsInvalidIsShown( false ) }
				/>
			) }
			{ modalAddRowsIsShown && (
				<Modal
					icon={ <Icon icon={ plus } size="36" style={ { display: 'flex', 'marginRight': '1rem' } } /> }
					title={ __( 'Add rows…', 'tablepress' ) }
					onRequestClose={ () => setModalAddRowsIsShown( false ) }
					focusOnMount="firstInputElement"
					style={ { width: '280px', minWidth: '280px' } }
				>
					<VStack>
						<HStack spacing={ 4 } alignment="bottom">
							<NumberControl
								label={ __( 'Number of rows', 'tablepress' ) }
								size="compact"
								title={ __( 'This field must contain a positive number.', 'tablepress' ) }
								isDragEnabled={ false }
								value={ rowsAppendNumber }
								onChange={ ( newRowsAppendNumber ) => {
									newRowsAppendNumber = '' !== newRowsAppendNumber ? parseInt( newRowsAppendNumber, 10 ) : 1;
									setRowsAppendNumber( newRowsAppendNumber );
								} }
								min={ 1 }
								required={ true }
								onKeyDown={ ( event ) => {
									if ( 'Enter' === event.key ) {
										event.preventDefault();
										tp.callbacks.append( 'rows', rowsAppendNumber );
										if ( ! event.shiftKey ) {
											setModalAddRowsIsShown( false );
										}
									}
								} }
							/>
							<Button
								variant="primary"
								size="compact"
								text={ __( 'Add', 'tablepress' ) }
								onClick={ ( event ) => {
									tp.callbacks.append( 'rows', rowsAppendNumber );
									if ( ! event.shiftKey ) {
										setModalAddRowsIsShown( false );
									}
								} }
							/>
						</HStack>
					</VStack>
				</Modal>
			) }
			{ modalAddColumnsIsShown && (
				<Modal
					icon={ <Icon icon={ plus } size="36" style={ { display: 'flex', 'marginRight': '1rem' } } /> }
					title={ __( 'Add columns…', 'tablepress' ) }
					onRequestClose={ () => setModalAddColumnsIsShown( false ) }
					focusOnMount="firstInputElement"
					style={ { width: '280px', minWidth: '280px' } }
				>
					<VStack>
						<HStack spacing={ 4 } alignment="bottom">
							<NumberControl
								label={ __( 'Number of columns', 'tablepress' ) }
								size="compact"
								title={ __( 'This field must contain a positive number.', 'tablepress' ) }
								isDragEnabled={ false }
								value={ columnsAppendNumber }
								onChange={ ( newColumnsAppendNumber ) => {
									newColumnsAppendNumber = '' !== newColumnsAppendNumber ? parseInt( newColumnsAppendNumber, 10 ) : 1;
									setColumnsAppendNumber( newColumnsAppendNumber );
								} }
								min={ 1 }
								required={ true }
								onKeyDown={ ( event ) => {
									if ( 'Enter' === event.key ) {
										event.preventDefault();
										tp.callbacks.append( 'columns', columnsAppendNumber );
										if ( ! event.shiftKey ) {
											setModalAddColumnsIsShown( false );
										}
									}
								} }
							/>
							<Button
								variant="primary"
								size="compact"
								text={ __( 'Add', 'tablepress' ) }
								onClick={ ( event ) => {
									tp.callbacks.append( 'columns', columnsAppendNumber );
									if ( ! event.shiftKey ) {
										setModalAddColumnsIsShown( false );
									}
								} }
							/>
						</HStack>
					</VStack>
				</Modal>
			) }
		</HStack>
	);
};

initializeReactComponentInPortal(
	'table-editor-toolbar',
	'edit',
	Section,
);
