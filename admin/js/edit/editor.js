/**
 * JavaScript code for the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/* globals jexcel, jQuery, jspreadsheet, tp, wp, wpLink */
/* eslint-disable jsdoc/check-param-names, jsdoc/valid-types */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from '../common/functions';
import contextMenu from './data/contextmenu';

// Ensure the global `tp` object exists.
window.tp = window.tp || {};

tp.made_changes = false;

tp.helpers = tp.helpers || {};

// Initial selection: cell A1.
tp.helpers.selection = tp.helpers.selection || {
	rows: [ 0 ],
	columns: [ 0 ],
};

tp.helpers.unsaved_changes = tp.helpers.unsaved_changes || {};

/**
 * [unsaved_changes.unload_dialog description]
 *
 * @param {Event} event [description]
 */
tp.helpers.unsaved_changes.unload_dialog = function ( event ) {
	event.preventDefault(); // Cancel the event as stated by the standard.
	event.returnValue = ''; // Chrome requires returnValue to be set.
};

/**
 * [unsaved_changes.set description]
 */
tp.helpers.unsaved_changes.set = function () {
	// Bail early if this function was already called.
	if ( tp.made_changes ) {
		return;
	}
	tp.made_changes = true;
	window.addEventListener( 'beforeunload', tp.helpers.unsaved_changes.unload_dialog );
};

/**
 * [unsaved_changes.unset description]
 */
tp.helpers.unsaved_changes.unset = function () {
	tp.made_changes = false;
	window.removeEventListener( 'beforeunload', tp.helpers.unsaved_changes.unload_dialog );
};

tp.helpers.visibility = tp.helpers.visibility || {};

/**
 * [visibility.load description]
 */
tp.helpers.visibility.load = function () {
	const num_rows = tp.table.visibility.rows.length;
	const num_columns = tp.table.visibility.columns.length;
	const meta = {};
	// Collect meta data for hidden rows.
	for ( let row_idx = 0; row_idx < num_rows; row_idx++ ) {
		if ( 1 === tp.table.visibility.rows[ row_idx ] ) {
			continue;
		}
		for ( let col_idx = 0; col_idx < num_columns; col_idx++ ) {
			const cell_name = jspreadsheet.getColumnNameFromId( [ col_idx, row_idx ] );
			meta[ cell_name ] = meta[ cell_name ] || {};
			meta[ cell_name ].row_hidden = true;
		}
	}
	// Collect meta data for hidden columns.
	for ( let col_idx = 0; col_idx < num_columns; col_idx++ ) {
		if ( 1 === tp.table.visibility.columns[ col_idx ] ) {
			continue;
		}
		for ( let row_idx = 0; row_idx < num_rows; row_idx++ ) {
			const cell_name = jspreadsheet.getColumnNameFromId( [ col_idx, row_idx ] );
			meta[ cell_name ] = meta[ cell_name ] || {};
			meta[ cell_name ].column_hidden = true;
		}
	}
	return meta;
};

/**
 * [visibility.update description]
 */
tp.helpers.visibility.update = function () {
	// Set all rows and columns to visible first.
	tp.table.visibility.rows = [];
	for ( let row_idx = 0; row_idx < tp.editor.options.data.length; row_idx++ ) {
		tp.table.visibility.rows[ row_idx ] = 1;
	}
	tp.table.visibility.columns = [];
	for ( let col_idx = 0; col_idx < tp.editor.options.columns.length; col_idx++ ) {
		tp.table.visibility.columns[ col_idx ] = 1;
	}
	// Get all hidden cells and mark their rows/columns as hidden.
	Object.keys( tp.editor.options.meta ).forEach( function ( cell_name ) {
		const cell = jspreadsheet.getIdFromColumnName( cell_name, true ); // Returns [ col_idx, row_idx ].
		if ( 1 === tp.table.visibility.rows[ cell[1] ] && tp.editor.options.meta[ cell_name ].row_hidden ) {
			tp.table.visibility.rows[ cell[1] ] = 0;
		}
		if ( 1 === tp.table.visibility.columns[ cell[0] ] && tp.editor.options.meta[ cell_name ].column_hidden ) {
			tp.table.visibility.columns[ cell[0] ] = 0;
		}
	} );
};

/**
 * Check whether the Hide or Unhide entries in the context menu should be disabled, by comparing
 * whether any of the selected rows/columns have a different visibility state than what the entry would set.
 *
 * @param {string}  type       What to hide or unhide ("rows" or "columns").
 * @param {boolean} visibility 0 for hidden, 1 for visible.
 * @return {boolean} True if the entry shall be shown, false if not.
 */
tp.helpers.visibility.selection_contains = function ( type, visibility ) {
	// Show the entry as soon as one of the selected rows/columns does not have the intended visibility state.
	return tp.helpers.selection[ type ].some( ( roc_idx ) => ( tp.table.visibility[ type ][ roc_idx ] === visibility ) );
};

/**
 * For the context menu and button, determine whether moving the rows/columns of the current selection is allowed.
 *
 * @param {[type]} type      [description]
 * @param {[type]} direction [description]
 * @return {boolean} Whether the move is allowed or not.
 */
tp.helpers.move_allowed = function ( type, direction ) {
	// When moving up or left, or to top or first, test the first row/column of the selected range.
	let roc_to_test = tp.helpers.selection[ type ][0];
	let min_max_roc = 0; // First row/column.
	// When moving down or right, or bottom or last, test the last row/column of the selected range.
	if ( 'down' === direction || 'right' === direction || 'bottom' === direction || 'last' === direction ) {
		roc_to_test = tp.helpers.selection[ type ][ tp.helpers.selection[ type ].length - 1 ];
		min_max_roc = ( 'rows' === type ) ? tp.editor.options.data.length - 1 : tp.editor.options.columns.length - 1;
	}
	// Moving is disallowed if the first/last row/column is already at the target edge.
	if ( min_max_roc === roc_to_test ) {
		return false;
	}
	// Otherwise allow the move.
	return true;
};

/**
 * Determines whether merging the current selection is allowed.
 *
 * Note that this does currently not take into account hidden rows!
 *
 * This is e.g. used to give feedback in the context menu and "Combine/Merge" button.
 *
 * @param {string} errors        Whether errors should also be alert()ed.
 * @param {Object} error_message Call-by-reference object for the error message.
 * @return {boolean} Whether the merge is allowed or not.
 */
tp.helpers.cell_merge_allowed = function ( errors, error_message = {} ) {
	const alertOnError = ( 'alert' === errors );

	const first_selected_row_idx = tp.helpers.selection.rows[0];
	const last_selected_row_idx = tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ];

	const first_body_row_idx = tp.table.options.table_head;
	const last_body_row_idx = tp.editor.options.data.length - 1 - tp.table.options.table_foot;

	// If table header rows are used and the "Enable Visitor Features" option is active, cell merging is only allowed in the table header and footer rows.
	if ( tp.table.options.table_head > 0 && tp.table.options.use_datatables && ! ( first_selected_row_idx < first_body_row_idx && last_selected_row_idx < first_body_row_idx ) && ! ( first_selected_row_idx > last_body_row_idx && last_selected_row_idx > last_body_row_idx ) ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” checkbox in the “%2$s” section is checked.', 'tablepress' ), __( 'Enable Visitor Features', 'tablepress' ), __( 'Table Features for Site Visitors', 'tablepress' ) ) +
				' ' + __( 'When the Table Features for Site Visitors are used, merging is only allowed in the table header and footer rows.', 'tablepress' );
		if ( alertOnError ) {
			// This alert can not be replaced by the `Alert` component, as that does not pause the code execution.
			window.alert( error_message.text );
		}
		return false;
	}

	// If table header rows are used, and a header row and at least one adjacent body row are selected, disable merging cells.
	if ( first_selected_row_idx < first_body_row_idx && last_selected_row_idx >= first_body_row_idx ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” setting in the “%2$s” section is active.', 'tablepress' ), __( 'Table Header', 'tablepress' ), __( 'Table Options', 'tablepress' ) );
		if ( alertOnError ) {
			// This alert can not be replaced by the `Alert` component, as that does not pause the code execution.
			window.alert( error_message.text );
		}
		return false;
	}

	// If table footer rows are used, and a footer row and at least one adjacent body row are selected, disable merging cells.
	if ( first_selected_row_idx <= last_body_row_idx && last_selected_row_idx > last_body_row_idx ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” setting in the “%2$s” section is active.', 'tablepress' ), __( 'Table Footer', 'tablepress' ), __( 'Table Options', 'tablepress' ) );
		if ( alertOnError ) {
			// This alert can not be replaced by the `Alert` component, as that does not pause the code execution.
			window.alert( error_message.text );
		}
		return false;
	}

	// Otherwise allow the merge.
	return true;
};

tp.helpers.editor = tp.helpers.editor || {};

/**
 * [editor_reselect description]
 *
 * @param {[type]} el  [description]
 * @param {[type]} obj Jspreadsheet instance, passed e.g. by onblur. If not present, we use tp.editor.
 */
tp.helpers.editor.reselect = function ( el, obj ) {
	if ( 'undefined' === typeof obj ) {
		obj = tp.editor;
	}
	obj.updateSelectionFromCoords(
		tp.helpers.selection.columns[0],
		tp.helpers.selection.rows[0],
		tp.helpers.selection.columns[ tp.helpers.selection.columns.length - 1 ],
		tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ]
	);
};

/**
 * Checks if the table has merged cells in the visible body rows and columns.
 *
 * @return {boolean} True if the table has merged cells in the body, false otherwise.
 */
tp.helpers.editor.has_merged_body_cells = function () {
	const first_body_row_idx = tp.table.options.table_head;
	const first_footer_row_idx = tp.editor.options.data.length - tp.table.options.table_foot;
	const num_columns = tp.editor.options.columns.length;
	// Loop through all cells after the table header rows and before the table footer rows.
	for ( let row_idx = first_body_row_idx; row_idx < first_footer_row_idx; row_idx++ ) {
		for ( let col_idx = 0; col_idx < num_columns; col_idx++ ) {
			if ( ( '#rowspan#' === tp.editor.options.data[ row_idx ][ col_idx ] || '#colspan#' === tp.editor.options.data[ row_idx ][ col_idx ] ) && 1 === tp.table.visibility.rows[ row_idx ] && 1 === tp.table.visibility.columns[ col_idx ] ) {
				return true;
			}
		}
	}
	return false;
};

/**
 * Creates the sorting function that is used when sorting the table by a column.
 *
 * @param {number} direction Sorting direction. 0 for ascending, 1 for descending.
 * @return {Function} Sorting function.
 */
tp.helpers.editor.sorting = function ( direction ) {
	direction = direction ? -1 : 1;
	return function ( a, b ) {
		// The actual value is stored in the second array element, the first contains the row index.
		const sortResult = a[1].localeCompare( b[1], undefined, {
			numeric: true,
			sensitivity: 'base',
		} );
		return direction * sortResult;
	};
};

tp.callbacks = tp.callbacks || {};

tp.callbacks.editor = tp.callbacks.editor || {};

/**
 * [editor_onselection description]
 *
 * @param {[type]} instance [description]
 * @param {[type]} x1       [description]
 * @param {[type]} y1       [description]
 * @param {[type]} x2       [description]
 * @param {[type]} y2       [description]
 * @param {[type]} origin   [description]
 */
tp.callbacks.editor.onselection = function ( instance, x1, y1, x2, y2 /*, origin */ ) {
	tp.helpers.selection = {
		rows: [],
		columns: [],
	};
	for ( let row_idx = y1; row_idx <= y2; row_idx++ ) {
		tp.helpers.selection.rows.push( row_idx );
	}
	for ( let col_idx = x1; col_idx <= x2; col_idx++ ) {
		tp.helpers.selection.columns.push( col_idx );
	}
};

/**
 * [editor_onupdatetable description]
 *
 * @param {[type]} instance  [description]
 * @param {[type]} cell      [description]
 * @param {[type]} col_idx   [description]
 * @param {[type]} row_idx   [description]
 * @param {[type]} value     [description]
 * @param {[type]} label     [description]
 * @param {[type]} cell_name [description]
 */
tp.callbacks.editor.onupdatetable = function ( instance, cell, col_idx, row_idx, value, label, cell_name ) {
	const meta = instance.jspreadsheet.options.meta[ cell_name ];

	// Add class to cells (td) of hidden columns.
	cell.classList.toggle( 'column-hidden', Boolean( meta?.column_hidden ) );

	// Add classes to row (tr) for hidden rows and head/foot row. Only needs to be done once per row, thus when processing the first column.
	if ( 0 === col_idx ) {
		cell.parentNode.classList.toggle( 'row-hidden', Boolean( meta?.row_hidden ) );
		cell.parentNode.classList.remove( 'head-row', 'foot-row' );

		// After processing the last row, potentially add classes to the head and foot rows.
		if ( row_idx === instance.jspreadsheet.rows.length - 1 ) {
			const visible_rows = instance.jspreadsheet.content.querySelectorAll( ':scope tbody tr:not(.row-hidden)' );
			for ( let idx = 0; idx < tp.table.options.table_head; idx++ ) {
				visible_rows[ idx ]?.classList.add( 'head-row' );
			}
			// Designating footer rows only makes sense for tables that have enough rows to show them.
			if ( visible_rows.length >= tp.table.options.table_head + tp.table.options.table_foot ) {
				const last_row_idx = visible_rows.length - 1;
				for ( let idx = 0; idx < tp.table.options.table_foot; idx++ ) {
					visible_rows[ last_row_idx - idx ]?.classList.add( 'foot-row' );
				}
			}
		}
	}
};

/**
 * [editor_oninsertroc description]
 *
 * Abbreviations:
 * roc: row or column
 * cor: column or row
 *
 * @param {[type]} type         [description]
 * @param {[type]} action       [description]
 * @param {[type]} el           [description]
 * @param {[type]} roc_idx      [description]
 * @param {[type]} num_rocs     [description]
 * @param {[type]} roc_records  [description]
 * @param {[type]} insertBefore [description]
 */
tp.callbacks.editor.oninsertroc = function ( type, action, el, roc_idx, num_rocs, roc_records, insertBefore ) {
	const handling_rows = ( 'rows' === type );
	const property = handling_rows ? 'column_hidden' : 'row_hidden';
	const duplicating = ( 'duplicate' === action );

	const from_roc_idx = roc_idx + ( insertBefore ? num_rocs : 0 );
	const num_cors = handling_rows ? tp.editor.options.columns.length : tp.editor.options.data.length;

	// Get data of row/column that is copied.
	const from_meta = {};
	for ( let cor_idx = 0; cor_idx < num_cors; cor_idx++ ) {
		const cell_idx = handling_rows ? [ cor_idx, from_roc_idx ] : [ from_roc_idx, cor_idx ];
		const meta = tp.editor.options.meta[ jspreadsheet.getColumnNameFromId( cell_idx ) ];
		if ( ! meta ) {
			continue;
		}
		// When duplicating, copy full cell meta, otherwise only the necessary property (row visibility for columns, column visibility for rows).
		if ( duplicating ) {
			from_meta[ cor_idx ] = meta;
		} else if ( meta[ property ] ) {
			from_meta[ cor_idx ] = from_meta[ cor_idx ] || {};
			from_meta[ cor_idx ][ property ] = true;
		}
	}

	const from_meta_keys = Object.keys( from_meta );
	// Bail early if there's nothing to copy.
	if ( ! from_meta_keys.length ) {
		return;
	}

	// Construct meta data for target rows/columns.
	const to_meta = {};
	if ( ! insertBefore ) {
		roc_idx++; // When appending (i.e. insert after), we start after the current row or column.
	}
	for ( let new_roc = 0; new_roc < num_rocs; new_roc++ ) {
		const to_roc_idx = roc_idx + new_roc;
		from_meta_keys.forEach( function ( cor_idx ) {
			const cell_idx = handling_rows ? [ cor_idx, to_roc_idx ] : [ to_roc_idx, cor_idx ];
			to_meta[ jspreadsheet.getColumnNameFromId( cell_idx ) ] = from_meta[ cor_idx ];
		} );
	}

	tp.editor.setMeta( to_meta );
	tp.editor.updateTable(); // Redraw table.
};

/**
 * [editor_onmove description]
 *
 * @param {[type]} el          [description]
 * @param {[type]} old_roc_idx [description]
 * @param {[type]} new_roc_idx [description]
 */
tp.callbacks.editor.onmove = function ( /* el, old_roc_idx, new_roc_idx */ ) {
	tp.helpers.editor.reselect();
	tp.helpers.unsaved_changes.set();
};

/**
 * [editor_onsort description]
 *
 * @param {[type]} el     [description]
 * @param {[type]} column [description]
 * @param {[type]} order  [description]
 */
tp.callbacks.editor.onsort = function ( /* el, column, order */ ) {
	tp.editor.updateTable(); // Redraw table.
	tp.helpers.unsaved_changes.set();
};

/**
 * Copy the generated link or image HTML code from the helper textarea to the first selected table cell.
 */
tp.helpers.editor.insert_from_helper_textarea = function () {
	tp.editor.setValueFromCoords( tp.helpers.selection.columns[0], tp.helpers.selection.rows[0], this.value );
};

tp.callbacks.insert_link = {};

/**
 * Open the wpLink dialog for inserting links.
 *
 * @param {HTMLElement|null} $active_textarea Active textarea of the table editor or null.
 */
tp.callbacks.insert_link.open_dialog = function ( $active_textarea = null ) {
	const $helper_textarea = $( '#textarea-insert-helper' );
	$helper_textarea.value = tp.editor.options.data[ tp.helpers.selection.rows[0] ][ tp.helpers.selection.columns[0] ];
	if ( $active_textarea ) {
		$helper_textarea.selectionStart = $active_textarea.selectionStart;
		$helper_textarea.selectionEnd = $active_textarea.selectionEnd;
	} else {
		$helper_textarea.selectionStart = $helper_textarea.value.length;
		$helper_textarea.selectionEnd = $helper_textarea.value.length;
	}
	const cell_name = jexcel.getColumnNameFromId( [ tp.helpers.selection.columns[0], tp.helpers.selection.rows[0] ] );
	$( '#link-modal-title' ).textContent = sprintf( __( 'Insert Link into cell %1$s', 'tablepress' ), cell_name );
	wpLink.open( 'textarea-insert-helper' );
	jexcel.current = null; // This is necessary to prevent problems with the focus when the "Insert Link" dialog is called from the context menu.
};

tp.callbacks.insert_image = {};

/**
 * Open the WP Media library for inserting images.
 *
 * @param {HTMLElement|null} $active_textarea Active textarea of the table editor or null.
 */
tp.callbacks.insert_image.open_dialog = function ( $active_textarea = null ) {
	const $helper_textarea = $( '#textarea-insert-helper' );
	$helper_textarea.value = tp.editor.options.data[ tp.helpers.selection.rows[0] ][ tp.helpers.selection.columns[0] ];
	if ( $active_textarea ) {
		$helper_textarea.selectionStart = $active_textarea.selectionStart;
		$helper_textarea.selectionEnd = $active_textarea.selectionEnd;
	} else {
		$helper_textarea.selectionStart = $helper_textarea.value.length;
		$helper_textarea.selectionEnd = $helper_textarea.value.length;
	}
	wp.media.editor.open( 'textarea-insert-helper', {
		frame: 'post',
		state: 'insert',
		title: wp.media.view.l10n.addMedia,
		multiple: true,
	} );
	const cell_name = jexcel.getColumnNameFromId( [ tp.helpers.selection.columns[0], tp.helpers.selection.rows[0] ] );
	document.querySelector( '#media-frame-title h1' ).textContent = sprintf( __( 'Add media to cell %1$s', 'tablepress' ), cell_name );
	jexcel.current = null; // This is necessary to prevent problems with the focus when the "Insert Link" dialog is called from the context menu.
};

tp.callbacks.advanced_editor = {};

tp.callbacks.advanced_editor.$textarea = $( '#advanced-editor-content' );

/**
 * Open the wpdialog for the Advanced Editor.
 *
 * @param {HTMLElement|null} $active_textarea Active textarea of the table editor or null.
 */
tp.callbacks.advanced_editor.open_dialog = function ( $active_textarea = null ) {
	tp.callbacks.advanced_editor.$textarea.value = tp.editor.options.data[ tp.helpers.selection.rows[0] ][ tp.helpers.selection.columns[0] ];

	const cell_name = jexcel.getColumnNameFromId( [ tp.helpers.selection.columns[0], tp.helpers.selection.rows[0] ] );
	const title = sprintf( __( 'Advanced Editor for cell %1$s', 'tablepress' ), cell_name );
	$( '#advanced-editor-label' ).textContent = title; // Screen reader label for the "Advanced Editor" textarea.
	$( '#link-modal-title' ).textContent = sprintf( __( 'Insert Link into cell %1$s', 'tablepress' ), cell_name );

	jQuery( '#advanced-editor' ).wpdialog( {
		width: 600,
		modal: true,
		title,
		resizable: false, // Height of textarea does not increase when resizing editor height.
		closeOnEscape: true,
		buttons: [
			{
				text: __( 'Cancel', 'tablepress' ),
				class: 'button button-cancel',
				click() {
					jQuery( this ).wpdialog( 'close' );
				},
			},
			{
				text: __( 'OK', 'tablepress' ),
				class: 'button button-primary button-ok',
				click: tp.callbacks.advanced_editor.confirm_save,
			},
		],
	} );

	jexcel.current = null; // This is necessary to prevent problems with the focus and cells being emptied when the Advanced Editor is called from the context menu.
	if ( $active_textarea ) {
		tp.callbacks.advanced_editor.$textarea.selectionStart = $active_textarea.selectionStart;
		tp.callbacks.advanced_editor.$textarea.selectionEnd = $active_textarea.selectionEnd;
	} else {
		tp.callbacks.advanced_editor.$textarea.selectionStart = tp.callbacks.advanced_editor.$textarea.value.length;
		tp.callbacks.advanced_editor.$textarea.selectionEnd = tp.callbacks.advanced_editor.$textarea.value.length;
	}
	tp.callbacks.advanced_editor.$textarea.focus();
};

/**
 * Confirm and save changes of the Advanced Editor.
 */
tp.callbacks.advanced_editor.confirm_save = function () {
	const current_value = tp.editor.options.data[ tp.helpers.selection.rows[0] ][ tp.helpers.selection.columns[0] ];
	// Only set the cell content if changes were made to not wrongly call tp.helpers.unsaved_changes.set().
	if ( tp.callbacks.advanced_editor.$textarea.value !== current_value ) {
		tp.editor.setValueFromCoords( tp.helpers.selection.columns[0], tp.helpers.selection.rows[0], tp.callbacks.advanced_editor.$textarea.value );
	}
	jQuery( this ).wpdialog( 'close' );
};

/**
 * Inserts or duplicates rows or columns before each currently selected row/column.
 *
 * @param {string} action   The action to perform on the selected rows/columns ("insert" or "duplicate").
 * @param {string} type     What to insert or duplicate ("rows" or "columns").
 * @param {string} position Where to insert or duplicate ("before" or "after"). Default "before".
 */
tp.callbacks.insert_duplicate = function ( action, type, position = 'before' ) {
	const handling_rows = ( 'rows' === type );
	const insert_function = handling_rows ? tp.editor.insertRow : tp.editor.insertColumn;
	const getData_function = handling_rows ? tp.editor.getRowData : tp.editor.getColumnData;
	const duplicating = ( 'duplicate' === action );
	// Dynamically set the event handler, so that we have the action available in it.
	tp.editor.options[ handling_rows ? 'oninsertrow' : 'oninsertcolumn' ] = tp.callbacks.editor.oninsertroc.bind( null, type, action );
	tp.helpers.selection[ type ].forEach( function ( roc_idx, array_idx ) {
		const shifted_roc_idx = roc_idx + array_idx; // Not having to deal with shifted indices is possible by looping through the reversed array, but that's likely slower.
		const data = duplicating ? getData_function( shifted_roc_idx ) : 1;
		const position_bool = 'before' === position; // true means "before".
		insert_function( data, shifted_roc_idx, position_bool );
	} );
	tp.helpers.unsaved_changes.set();

	// Select both inserted/duplicated rows/columns if more than one were selected.
	const num_selected_rocs = tp.helpers.selection[ type ].length;
	if ( num_selected_rocs > 1 ) {
		tp.editor.updateSelectionFromCoords(
			tp.helpers.selection.columns[0],
			tp.helpers.selection.rows[0],
			handling_rows ? tp.helpers.selection.columns[ tp.helpers.selection.columns.length - 1 ] : tp.helpers.selection.columns[ tp.helpers.selection.columns.length - 1 ] + num_selected_rocs,
			handling_rows ? tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ] + num_selected_rocs : tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ]
		);
	}
};

/**
 * Removes currently selected rows or columns.
 *
 * @param {string} type What to remove ("rows" or "columns").
 */
tp.callbacks.remove = function ( type ) {
	const handling_rows = 'rows' === type;
	const num_cors = handling_rows ? tp.editor.options.columns.length : tp.editor.options.data.length;
	const last_roc_idx = handling_rows ? tp.editor.options.data.length - 1 : tp.editor.options.columns.length - 1;

	// Visibility meta information has to be deleted manually, as otherwise the Jspreadsheet meta information can get out of sync.
	if ( tp.editor.options.meta ) {
		tp.helpers.selection[ type ].forEach( function ( roc_idx ) {
			for ( let cor_idx = 0; cor_idx < num_cors; cor_idx++ ) {
				const cell_idx = handling_rows ? [ cor_idx, roc_idx ] : [ roc_idx, cor_idx ];
				delete tp.editor.options.meta[ jspreadsheet.getColumnNameFromId( cell_idx ) ];
			}
		} );
	}

	const delete_function = handling_rows ? tp.editor.deleteRow : tp.editor.deleteColumn;
	delete_function( tp.helpers.selection[ type ][0], tp.helpers.selection[ type ].length );
	tp.helpers.unsaved_changes.set();

	// Reselect last visible row/column, if last rows/columns were deleted.
	if ( last_roc_idx === tp.helpers.selection[ type ][ tp.helpers.selection[ type ].length - 1 ] ) {
		const col_idx = handling_rows ? tp.helpers.selection.columns[0] : tp.helpers.selection.columns[0] - 1;
		const row_idx = handling_rows ? tp.helpers.selection.rows[0] - 1 : tp.helpers.selection.rows[0];
		tp.editor.updateSelectionFromCoords( col_idx, row_idx, col_idx, row_idx );
	}
};

/**
 * Appends rows or columns at the bottom or right end of the table.
 *
 * @param {string} type     What to append ("rows" or "columns").
 * @param {number} num_rocs Number of rows or columns to append.
 */
tp.callbacks.append = function ( type, num_rocs ) {
	const handling_rows = ( 'rows' === type );
	const insert_function = handling_rows ? tp.editor.insertRow : tp.editor.insertColumn;
	// Dynamically set the event handler, so that we have the action available in it.
	tp.editor.options[ handling_rows ? 'oninsertrow' : 'oninsertcolumn' ] = tp.callbacks.editor.oninsertroc.bind( null, type, 'append' );
	insert_function( num_rocs );
	tp.helpers.unsaved_changes.set();
};

/**
 * Moves currently selected rows or columns.
 *
 * @param {string} direction Where to move the selected rows or columns (for rows: "up"/"down"/"top"/"bottom", for columns: "left"/right"/"first"/"last").
 * @param {string} type      What to move ("rows" or "columns").
 */
tp.callbacks.move = function ( direction, type ) {
	const handling_rows = ( 'rows' === type );

	// Default case: up/left
	let rocs = tp.helpers.selection[ type ]; // When moving up or left, start with the first row/column of the selected range.
	let position_difference = -1; // New row/column number is one smaller than current row/column number.
	// Alternate case: down/right
	if ( 'down' === direction || 'right' === direction ) {
		rocs = rocs.slice().reverse(); // When moving down or right, reverse the order, to start with the last row/column of the selected range. slice() is needed here to create an array copy.
		position_difference = 1; // New row/column number is one higher than current row/column number.
	} else if ( 'top' === direction || 'first' === direction ) {
		position_difference = -rocs[0];
	} else if ( 'bottom' === direction || 'last' === direction ) {
		rocs = rocs.slice().reverse(); // When moving down or right, reverse the order, to start with the last row/column of the selected range. slice() is needed here to create an array copy.
		const min_max_roc = ( 'rows' === type ) ? tp.editor.options.data.length - 1 : tp.editor.options.columns.length - 1;
		position_difference = min_max_roc - rocs[0];
	}

	// Bail early if there is nothing to do (e.g. when the selected range is already at the target edge).
	if ( 0 === position_difference ) {
		return;
	}

	// Move the selected rows/columns individually.
	const move_function = handling_rows ? tp.editor.moveRow : tp.editor.moveColumn;
	rocs.forEach( ( roc_idx ) => move_function( roc_idx, roc_idx + position_difference ) );
	tp.helpers.unsaved_changes.set();

	// Reselect moved selection.
	tp.editor.updateSelectionFromCoords(
		handling_rows ? tp.helpers.selection.columns[0] : tp.helpers.selection.columns[0] + position_difference,
		handling_rows ? tp.helpers.selection.rows[0] + position_difference : tp.helpers.selection.rows[0],
		handling_rows ? tp.helpers.selection.columns[ tp.helpers.selection.columns.length - 1 ] : tp.helpers.selection.columns[ tp.helpers.selection.columns.length - 1 ] + position_difference,
		handling_rows ? tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ] + position_difference : tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ]
	);
};

/**
 * Sorts the table data by the first currently selected column.
 *
 * @param {string} direction Sort order/direction ("asc" for ascending, "desc" for descending).
 */
tp.callbacks.sort = function ( direction ) {
	tp.editor.orderBy( tp.helpers.selection.columns[0], ( 'desc' === direction ) );
};

/**
 * Hides or unhides selected rows or columns.
 *
 * @param {string} action The action to perform on the rows/columns ("hide" or "unhide").
 * @param {string} type   What to hide or unhide ("rows" or "columns").
 */
tp.callbacks.hide_unhide = function ( action, type ) {
	const handling_rows = ( 'rows' === type );
	const property = handling_rows ? 'row_hidden' : 'column_hidden';
	const num_cors = handling_rows ? tp.editor.options.columns.length : tp.editor.options.data.length;
	const cell_hidden = ( 'hide' === action );
	const meta = {};
	tp.helpers.selection[ type ].forEach( function ( roc_idx ) {
		for ( let cor_idx = 0; cor_idx < num_cors; cor_idx++ ) {
			const cell_idx = handling_rows ? [ cor_idx, roc_idx ] : [ roc_idx, cor_idx ];
			const cell_name = jspreadsheet.getColumnNameFromId( cell_idx );
			meta[ cell_name ] = {};
			meta[ cell_name ][ property ] = cell_hidden;
		}
	} );
	tp.editor.setMeta( meta );
	tp.helpers.unsaved_changes.set();
	tp.editor.updateTable(); // Redraw table.
};

/**
 * Combines/merges the currently selected cells.
 */
tp.callbacks.merge_cells = function () {
	const current_col_idx = tp.helpers.selection.columns[0];
	const current_row_idx = tp.helpers.selection.rows[0];
	const colspan = tp.helpers.selection.columns.length;
	const rowspan = tp.helpers.selection.rows.length;
	for ( let row_idx = 1; row_idx < rowspan; row_idx++ ) {
		tp.editor.setValueFromCoords( current_col_idx, current_row_idx + row_idx, '#rowspan#' );
	}
	for ( let col_idx = 1; col_idx < colspan; col_idx++ ) {
		tp.editor.setValueFromCoords( current_col_idx + col_idx, current_row_idx, '#colspan#' );
	}
	for ( let row_idx = 1; row_idx < rowspan; row_idx++ ) {
		for ( let col_idx = 1; col_idx < colspan; col_idx++ ) {
			tp.editor.setValueFromCoords( current_col_idx + col_idx, current_row_idx + row_idx, '#span#' );
		}
	}
	tp.helpers.unsaved_changes.set();
};

/*
 * Initialize Jspreadsheet.
 */
tp.editor = jspreadsheet( $( '#table-editor' ), {
	data: tp.table.data,
	meta: tp.helpers.visibility.load(),
	wordWrap: true,
	rowDrag: true,
	rowResize: true,
	columnSorting: true,
	columnDrag: true,
	columnResize: true,
	defaultColWidth: tp.screenOptions.table_editor_column_width,
	defaultColAlign: 'left',
	parseFormulas: false,
	allowExport: false,
	allowComments: false,
	allowManualInsertRow: false, // To prevent addition of new row when Enter is pressed in last row.
	allowManualInsertColumn: false, // To prevent addition of new column when Tab is pressed in last column.
	about: false,
	secureFormulas: false,
	detachForUpdates: true,
	onselection: tp.callbacks.editor.onselection,
	updateTable: tp.callbacks.editor.onupdatetable,
	contextMenu,
	sorting: tp.helpers.editor.sorting,
	// Keep the selection when certain events occur and the table loses focus.
	onmoverow: tp.callbacks.editor.onmove,
	onmovecolumn: tp.callbacks.editor.onmove,
	onblur: tp.helpers.editor.reselect,
	onload: tp.helpers.editor.reselect, // When the table is loaded, select the top-left cell A1.
	onchange: tp.helpers.unsaved_changes.set,
	onsort: tp.callbacks.editor.onsort,
} );

// Register callback for inserting a link into a cell after it has been constructed in the wpLink dialog.
jQuery( '#textarea-insert-helper' ).on( 'change', tp.helpers.editor.insert_from_helper_textarea ); // This must use jQuery, as wpLink triggers jQuery events, which can not be observed by native JS listeners.

// This code requires jQuery, and it must run when the DOM is ready.
jQuery( () => {
	// Fix issue with wpLink input fields not being usable, when called through the "Advanced Editor". They are immediately losing focus without this.
	jQuery( '#wp-link' ).on( 'focus', 'input', ( event ) => ( event.stopPropagation() ) );

	// Fix issue with Media Library input fields in the sidebar not being usable, when called through the "Advanced Editor". They are immediately losing focus without this.
	jQuery( 'body' ).on( 'focus', '.media-modal .media-frame-content input, .media-modal .media-frame-content textarea', ( event ) => ( event.stopPropagation() ) );
} );
