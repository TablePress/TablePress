/**
 * Definition of the contextmenu for Jspreadsheet on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/* globals tp */
/* eslint-disable jsdoc/check-param-names, jsdoc/valid-types */

/**
 * WordPress dependencies.
 */
import { __, _x, _n, sprintf } from '@wordpress/i18n';

/**
 * Returns the entries for the table editor's context menu.
 *
 * @param {[type]} obj [description]
 * @param {[type]} x   [description]
 * @param {[type]} y   [description]
 * @param {[type]} e   [description]
 * @return {Array} Context menu items.
 */
export default function contextMenu( obj /*, x, y, e */ ) {
	const num_rows = tp.editor.options.data.length;
	const num_columns = tp.editor.options.columns.length;
	const num_selected_rows = tp.helpers.selection.rows.length;
	const num_selected_columns = tp.helpers.selection.columns.length;
	const is_mac = window?.navigator?.platform?.includes( 'Mac' );
	const meta_key = is_mac ?
		_x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) :
		_x( 'Ctrl+', 'keyboard shortcut modifier key on a non-Mac keyboard', 'tablepress' );
	const option_key = is_mac ?
		_x( '⌥', 'keyboard shortcut option key on a Mac keyboard', 'tablepress' ) :
		_x( 'Alt+', 'keyboard shortcut Alt key on a non-Mac keyboard', 'tablepress' );

	// Call-by-reference object for the cell_merge_allowed() call.
	const error_message = {
		text: ''
	};

	tp.helpers.visibility.update();

	const items = [
		// Undo/Redo.
		{
			title: __( 'Undo', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sZ', 'keyboard shortcut for Undo', 'tablepress' ), meta_key ),
			onclick: obj.undo,
			disabled: ( -1 === obj.historyIndex ),
		},
		{
			title: __( 'Redo', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sY', 'keyboard shortcut for Redo', 'tablepress' ), meta_key ),
			onclick: obj.redo,
			disabled: ( obj.historyIndex === obj.history.length - 1 ),
		},

		// Cut/Copy/Paste.
		{
			type: 'divisor',
		},
		{
			title: __( 'Cut', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sX', 'keyboard shortcut for Cut', 'tablepress' ), meta_key ),
			onclick() {
				/* eslint-disable @wordpress/no-global-active-element */
				if ( 'TEXTAREA' === document.activeElement.tagName && document.activeElement.selectionStart !== document.activeElement.selectionEnd ) {
					document.execCommand( 'copy' ); // If text is selected in the actively edited cell, only copy that.
					const cursorPosition = document.activeElement.selectionStart;
					document.activeElement.value = document.activeElement.value.slice( 0, document.activeElement.selectionStart ) + document.activeElement.value.slice( document.activeElement.selectionEnd ); // Cut the selected content.
					document.activeElement.selectionEnd = cursorPosition;
				} else {
					obj.copy( true ); // Otherwise, copy highlighted cells.
					obj.setValue( obj.highlighted, '' ); // Make cell content empty.
				}
				/* eslint-enable @wordpress/no-global-active-element */
			},
		},
		{
			title: __( 'Copy', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sC', 'keyboard shortcut for Copy', 'tablepress' ), meta_key ),
			onclick() {
				if ( 'TEXTAREA' === document.activeElement.tagName && document.activeElement.selectionStart !== document.activeElement.selectionEnd ) { // eslint-disable-line @wordpress/no-global-active-element
					document.execCommand( 'copy' ); // If text is selected in the actively edited cell, only copy that.
				} else {
					obj.copy( true ); // Otherwise, copy highlighted cells.
				}
			},
		},
		{
			title: __( 'Paste', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sV', 'keyboard shortcut for Paste', 'tablepress' ), meta_key ),
			onclick() {
				/* eslint-disable @wordpress/no-global-active-element */
				if ( 'TEXTAREA' === document.activeElement.tagName ) {
					window.navigator.clipboard.readText().then( ( text ) => {
						if ( text ) {
							const cursorPosition = document.activeElement.selectionStart + text.length;
							document.activeElement.value = document.activeElement.value.slice( 0, document.activeElement.selectionStart ) + text + document.activeElement.value.slice( document.activeElement.selectionEnd ); // Paste at the selection.
							document.activeElement.selectionEnd = cursorPosition;
						}
					} );
				} else if ( obj.selectedCell ) {
					window.navigator.clipboard.readText().then( ( text ) => {
						if ( text ) {
							obj.paste( obj.selectedCell[0], obj.selectedCell[1], text );
						}
					} );
				}
				/* eslint-enable @wordpress/no-global-active-element */
			},
			// Firefox does not offer the readText() method, so "Paste" needs to be disabled.
			disabled: ! window?.navigator?.clipboard?.readText,
			tooltip: ! window?.navigator?.clipboard?.readText ? __( 'Your browser does not allow pasting via the context menu. Use the keyboard shortcut instead.', 'tablepress' ) : '',
		},

		// Insert Link, Insert Image, Open Advanced Editor.
		{
			type: 'divisor',
		},
		{
			title: __( 'Insert Link', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sL', 'keyboard shortcut for Insert Link', 'tablepress' ), meta_key ),
			onclick: tp.callbacks.insert_link.open_dialog.bind( null, ( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null ), // eslint-disable-line @wordpress/no-global-active-element
		},
		{
			title: __( 'Insert Image', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sI', 'keyboard shortcut for Insert Image', 'tablepress' ), meta_key ),
			onclick: tp.callbacks.insert_image.open_dialog.bind( null, ( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null ), // eslint-disable-line @wordpress/no-global-active-element
		},
		{
			title: __( 'Advanced Editor', 'tablepress' ),
			shortcut: sprintf( _x( '%1$sE', 'keyboard shortcut for Advanced Editor', 'tablepress' ), meta_key ),
			onclick: tp.callbacks.advanced_editor.open_dialog.bind( null, ( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null ), // eslint-disable-line @wordpress/no-global-active-element
		},

		// Duplicate/Insert/Append/Delete.
		{
			type: 'divisor',
		},
		{
			title: __( 'Duplicate …', 'tablepress' ),
			submenu: [
				{
					title: _n( 'Duplicate row', 'Duplicate rows', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'duplicate', 'rows' ),
				},
				{
					title: _n( 'Duplicate column', 'Duplicate columns', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'duplicate', 'columns' ),
				},
			],
		},
		{
			title: __( 'Insert …', 'tablepress' ),
			submenu: [
				{
					title: _n( 'Insert row above', 'Insert rows above', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'insert', 'rows', 'before' ),
				},
				{
					title: _n( 'Insert row below', 'Insert rows below', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'insert', 'rows', 'after' ),
				},
				{
					title: _n( 'Insert column on the left', 'Insert columns on the left', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'insert', 'columns', 'before' ),
				},
				{
					title: _n( 'Insert column on the right', 'Insert columns on the right', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.insert_duplicate.bind( null, 'insert', 'columns', 'after' ),
				},
			],
		},
		{
			title: __( 'Append …', 'tablepress' ),
			submenu: [
				{
					title: __( 'Append row', 'tablepress' ),
					onclick: tp.callbacks.append.bind( null, 'rows', 1 ),
				},
				{
					title: __( 'Append column', 'tablepress' ),
					onclick: tp.callbacks.append.bind( null, 'columns', 1 ),
				},
			],
		},
		{
			title: __( 'Delete …', 'tablepress' ),
			submenu: [
				{
					title: _n( 'Delete row', 'Delete rows', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.remove.bind( null, 'rows' ),
					disabled: num_rows === num_selected_rows,
					tooltip: num_rows === num_selected_rows ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'You can not delete all table rows!', 'tablepress' ) : '',
				},
				{
					title: _n( 'Delete column', 'Delete columns', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.remove.bind( null, 'columns' ),
					disabled: num_columns === num_selected_columns,
					tooltip: num_columns === num_selected_columns ? __( 'This option is disabled.', 'tablepress' ) + ' ' + __( 'You can not delete all table columns!', 'tablepress' ) : '',
				},
			],
		},

		// Move rows/columns, Sort by column.
		{
			type: 'divisor',
		},
		{
			title: __( 'Move …', 'tablepress' ),
			submenu: [
				{
					title: _n( 'Move row up', 'Move rows up', num_selected_rows, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s⇧↑', 'keyboard shortcut for Move up', 'tablepress' ), meta_key ),
					onclick: tp.callbacks.move.bind( null, 'up', 'rows' ),
					disabled: ! tp.helpers.move_allowed( 'rows', 'up' ),
				},
				{
					title: _n( 'Move row down', 'Move rows down', num_selected_rows, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s⇧↓', 'keyboard shortcut for Move down', 'tablepress' ), meta_key ),
					onclick: tp.callbacks.move.bind( null, 'down', 'rows' ),
					disabled: ! tp.helpers.move_allowed( 'rows', 'down' ),
				},
				{
					title: _n( 'Move column left', 'Move columns left', num_selected_columns, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s⇧←', 'keyboard shortcut for Move left', 'tablepress' ), meta_key ),
					onclick: tp.callbacks.move.bind( null, 'left', 'columns' ),
					disabled: ! tp.helpers.move_allowed( 'columns', 'left' ),
				},
				{
					title: _n( 'Move column right', 'Move columns right', num_selected_columns, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s⇧→', 'keyboard shortcut for Move right', 'tablepress' ), meta_key ),
					onclick: tp.callbacks.move.bind( null, 'right', 'columns' ),
					disabled: ! tp.helpers.move_allowed( 'columns', 'right' ),
				},
				{
					type: 'divisor',
				},
				{
					title: _n( 'Move row to the top', 'Move rows to the top', num_selected_rows, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s%2$s⇧↑', 'keyboard shortcut for Move to the top', 'tablepress' ), meta_key, option_key ),
					onclick: tp.callbacks.move.bind( null, 'top', 'rows' ),
					disabled: ! tp.helpers.move_allowed( 'rows', 'top' ),
				},
				{
					title: _n( 'Move row to the bottom', 'Move rows to the bottom', num_selected_rows, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s%2$s⇧↓', 'keyboard shortcut for Move to the bottom', 'tablepress' ), meta_key, option_key ),
					onclick: tp.callbacks.move.bind( null, 'bottom', 'rows' ),
					disabled: ! tp.helpers.move_allowed( 'rows', 'bottom' ),
				},
				{
					title: _n( 'Move column to first', 'Move columns to first', num_selected_columns, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s%2$s⇧←', 'keyboard shortcut for Move to first', 'tablepress' ), meta_key, option_key ),
					onclick: tp.callbacks.move.bind( null, 'first', 'columns' ),
					disabled: ! tp.helpers.move_allowed( 'columns', 'first' ),
				},
				{
					title: _n( 'Move column to last', 'Move columns to last', num_selected_columns, 'tablepress' ),
					shortcut: sprintf( _x( '%1$s%2$s⇧→', 'keyboard shortcut for Move to last', 'tablepress' ), meta_key, option_key ),
					onclick: tp.callbacks.move.bind( null, 'last', 'columns' ),
					disabled: ! tp.helpers.move_allowed( 'columns', 'last' ),
				},
			],
		},
		{
			title: __( 'Sort by column …', 'tablepress' ),
			submenu: [
				{
					title: __( 'Sort by column ascending', 'tablepress' ),
					onclick: tp.callbacks.sort.bind( null, 'asc' ),
					disabled: 1 !== num_selected_columns,
					tooltip: 1 !== num_selected_columns ? __( 'This option is disabled because more than one column was selected.', 'tablepress' ) : '',
				},
				{
					title: __( 'Sort by column descending', 'tablepress' ),
					onclick: tp.callbacks.sort.bind( null, 'desc' ),
					disabled: 1 !== num_selected_columns,
					tooltip: 1 !== num_selected_columns ? __( 'This option is disabled because more than one column was selected.', 'tablepress' ) : '',
				},
			],
		},

		// Hide/Show rows/columns.
		{
			type: 'divisor',
		},
		{
			title: __( 'Hide/Show …', 'tablepress' ),
			submenu: [
				{
					title: _n( 'Hide row', 'Hide rows', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.hide_unhide.bind( null, 'hide', 'rows' ),
					disabled: ! tp.helpers.visibility.selection_contains( 'rows', 1 ),
					tooltip: ! tp.helpers.visibility.selection_contains( 'rows', 1 ) ? __( 'This option is disabled because no visible rows were selected.', 'tablepress' ) : '',
				},
				{
					title: _n( 'Hide column', 'Hide columns', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.hide_unhide.bind( null, 'hide', 'columns' ),
					disabled: ! tp.helpers.visibility.selection_contains( 'columns', 1 ),
					tooltip: ! tp.helpers.visibility.selection_contains( 'columns', 1 ) ? __( 'This option is disabled because no visible columns were selected.', 'tablepress' ) : '',
				},
				{
					title: _n( 'Show row', 'Show rows', num_selected_rows, 'tablepress' ),
					onclick: tp.callbacks.hide_unhide.bind( null, 'unhide', 'rows' ),
					disabled: ! tp.helpers.visibility.selection_contains( 'rows', 0 ),
					tooltip: ! tp.helpers.visibility.selection_contains( 'rows', 0 ) ? __( 'This option is disabled because no hidden rows were selected.', 'tablepress' ) : '',
				},
				{
					title: _n( 'Show column', 'Show columns', num_selected_columns, 'tablepress' ),
					onclick: tp.callbacks.hide_unhide.bind( null, 'unhide', 'columns' ),
					disabled: ! tp.helpers.visibility.selection_contains( 'columns', 0 ),
					tooltip: ! tp.helpers.visibility.selection_contains( 'columns', 0 ) ? __( 'This option is disabled because no hidden columns were selected.', 'tablepress' ) : '',
				},
			],
		},

		// Merging/Unmerging cells.
		{
			type: 'divisor',
		},
		{
			title: __( 'Combine/Merge cells', 'tablepress' ),
			onclick: tp.callbacks.merge_cells,
			disabled: ( 1 === num_selected_rows && 1 === num_selected_columns ) || ! tp.helpers.cell_merge_allowed( 'no-alert' ),
			tooltip: ( 1 === num_selected_rows && 1 === num_selected_columns ) || ! tp.helpers.cell_merge_allowed( 'no-alert', error_message ) ? __( 'This option is disabled.', 'tablepress' ) + ' ' + error_message.text : '',
		}
	];

	return items;
}
