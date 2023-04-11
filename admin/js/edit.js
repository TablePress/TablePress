/**
 * JavaScript code for the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/* globals tp, wp, ajaxurl, JSON, jspreadsheet, jexcel, wpLink, jQuery */
/* eslint-disable jsdoc/check-param-names, jsdoc/valid-types */

/**
 * WordPress dependencies.
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { doAction as do_action, applyFilters as apply_filters } from '@wordpress/hooks';
import { buildQueryString } from '@wordpress/url';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';
import contextMenu from './_edit-contextmenu';
import naturalSort from './_edit-naturalsort';

// Ensure the global `tp` object exists.
window.tp = window.tp || {};

tp.made_changes = false;

tp.helpers = tp.helpers || {};
tp.callbacks = tp.callbacks || {};

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

tp.helpers.options = tp.helpers.options || {};

/**
 * Loads table options and sets DOM element states appropriately.
 */
tp.helpers.options.load = function () {
	Object.keys( tp.table.options ).forEach( function ( option_name ) {
		// Skip entries that are not actually option fields.
		if ( 'last_editor' === option_name ) {
			return;
		}

		// Allow skipping options, e.g. when custom loading is used.
		option_name = apply_filters( 'tablepress.optionsLoad', option_name );
		if ( '' === option_name ) {
			return;
		}

		let $field = $( `#option-${ option_name }` );
		if ( ! $field ) {
			// If no field with just that option_name is found, it could be a radio button, which have IDs based on option_name and value.
			$field = $( `#option-${ option_name }-${ tp.table.options[ option_name ] }` );
		}
		if ( ! $field ) {
			// If there's still no field, the field might be missing. For example, the "Custom Commands" only exists if a user is allowed to use `unfiltered_html`.
			return;
		}

		if ( $field instanceof HTMLInputElement && 'checkbox' === $field.type ) {
			// For checkboxes, the `checked` state is based on the value (true/false).
			$field.checked = tp.table.options[ option_name ];
		} else if ( $field instanceof HTMLInputElement && 'radio' === $field.type ) {
			// For checkboxes, the `checked` state is true, as only the field corresponding to the value is selected.
			$field.checked = true;
		} else {
			// For all other fields, the form field value is set according to the option value.
			$field.value = tp.table.options[ option_name ];
		}
	} );

	// Turn off "Enable Visitor Features" if the table has merged cells.
	if ( tp.table.options.use_datatables && tp.helpers.editor.has_merged_cells() ) {
		tp.table.options.use_datatables = false;
		$( '#option-use_datatables' ).checked = false;
	}

	tp.helpers.options.check_dependencies();
};

/**
 * Sets the table option property when the DOM element (form field) is changed.
 *
 * @param {Event} event [description]
 */
tp.helpers.options.change = function ( event ) {
	if ( ! event.target ) {
		return;
	}

	const option_name = event.target.name || '';

	// Skip input fields that don't have a valid `name` attribute, as these don't directly reflect table options.
	if ( '' === option_name ) {
		return;
	}

	const property = ( event.target instanceof HTMLInputElement && 'checkbox' === event.target.type ) ? 'checked' : 'value';
	tp.table.options[ option_name ] = event.target[ property ];

	// Save numeric options as numbers.
	if ( event.target instanceof HTMLInputElement && 'number' === event.target.type ) {
		tp.table.options[ option_name ] = parseInt( tp.table.options[ option_name ], 10 );
	}

	// Turn off "Enable Visitor Features" if the table has merged cells.
	if ( 'use_datatables' === option_name && tp.table.options.use_datatables && tp.helpers.editor.has_merged_cells() ) {
		tp.table.options.use_datatables = false;
		$( '#option-use_datatables' ).checked = false;
		window.alert( __( 'You can not enable the Table Features for Site Visitors, because your table contains combined/merged cells.', 'tablepress' ) );
	}

	do_action( 'tablepress.optionsChange', option_name, property, event );

	tp.helpers.options.check_dependencies();
	tp.helpers.unsaved_changes.set();
	tp.editor.updateTable(); // Redraw table.
};

/**
 * Checks dependencies of options and sets DOM state ("disabled") appropriately.
 */
tp.helpers.options.check_dependencies = function () {
	$( '#option-use_datatables' ).disabled = ! tp.table.options.table_head;
	$( '#notice-datatables-head-row' ).style.display = tp.table.options.table_head ? 'none' : 'block';

	$( '#option-print_name_position' ).disabled = ! tp.table.options.print_name;
	$( '#option-print_description_position' ).disabled = ! tp.table.options.print_description;

	const js_features_enabled = ( tp.table.options.use_datatables && tp.table.options.table_head );
	$( '#tablepress_edit-datatables-features' ).querySelectorAll( ':scope input:not(#option-use_datatables), :scope textarea' ).forEach( ( $field ) => ( $field.disabled = ! js_features_enabled ) );

	const pagination_enabled = ( js_features_enabled && tp.table.options.datatables_paginate );
	$( '#option-datatables_lengthchange' ).disabled = ! pagination_enabled;
	$( '#option-datatables_paginate_entries' ).disabled = ! pagination_enabled;

	do_action( 'tablepress.optionsCheckDependencies' );
};

/**
 * Validate certain form fields, before saving or generating a preview.
 */
tp.helpers.options.validate_fields = function () {
	// The pagination entries value must be a positive number.
	if ( tp.table.options.datatables_paginate && ( isNaN( tp.table.options.datatables_paginate_entries ) || tp.table.options.datatables_paginate_entries < 1 || tp.table.options.datatables_paginate_entries > 9999 ) ) {
		window.alert( sprintf( __( 'The entered value in the “%1$s” field is invalid.', 'tablepress' ), __( 'Pagination Entries', 'tablepress' ) ) );
		const $field = $( '#option-datatables_paginate_entries' );
		$field.focus();
		$field.select();
		return false;
	}

	// The "Extra CSS classes" must not contain invalid characters.
	if ( ( /[^A-Za-z0-9- _:]/ ).test( tp.table.options.extra_css_classes ) ) {
		window.alert( sprintf( __( 'The entered value in the “%1$s” field is invalid.', 'tablepress' ), __( 'Extra CSS Classes', 'tablepress' ) ) );
		const $field = $( '#option-extra_css_classes' );
		$field.focus();
		$field.select();
		return false;
	}

	return apply_filters( 'tablepress.optionsValidateFields', true );
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
 * For the context menu and button, determine whether merging the current selection is allowed.
 *
 * @param {string} errors        Whether errors should also be alert()ed.
 * @param {Object} error_message Call-by-reference object for the error message.
 * @return {boolean} Whether the merge is allowed or not.
 */
tp.helpers.cell_merge_allowed = function ( errors, error_message = {} ) {
	const alert_on_error = ( 'alert' === errors );

	// If the "Table Head Row" and Enable Visitor Features" options are enabled, disable merging cells.
	if ( tp.table.options.table_head && tp.table.options.use_datatables ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” checkbox in the “%2$s” section is checked.', 'tablepress' ), __( 'Enable Visitor Features', 'tablepress' ), __( 'Table Features for Site Visitors', 'tablepress' ) ) +
				' ' + __( 'The Table Features for Site Visitors are not compatible with merged cells.', 'tablepress' );
		if ( alert_on_error ) {
			window.alert( error_message.text );
		}
		return false;
	}

	const first_selected_row = tp.helpers.selection.rows[0];
	const last_selected_row = tp.helpers.selection.rows[ tp.helpers.selection.rows.length - 1 ];

	// If the head row option is enabled, and the first and (at least) second row are selected, disable merging cells.
	if ( tp.table.options.table_head && 0 === first_selected_row && last_selected_row > 0 ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” checkbox in the “%2$s” section is checked.', 'tablepress' ), __( 'Table Head Row', 'tablepress' ), __( 'Table Options', 'tablepress' ) );
		if ( alert_on_error ) {
			window.alert( error_message.text );
		}
		return false;
	}

	// If the foot row option is enabled, and the last and (at least) next to last row are selected, disable merging cells.
	const last_row_idx = tp.editor.options.data.length - 1;
	if ( tp.table.options.table_foot && last_row_idx === last_selected_row && first_selected_row < last_row_idx ) {
		error_message.text = sprintf( __( 'You can not combine these cells, because the “%1$s” checkbox in the “%2$s” section is checked.', 'tablepress' ), __( 'Table Foot Row', 'tablepress' ), __( 'Table Options', 'tablepress' ) );
		if ( alert_on_error ) {
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
 * [editor_has_merged_cells description]
 */
tp.helpers.editor.has_merged_cells = function () {
	const num_rows = tp.editor.options.data.length;
	const num_columns = tp.editor.options.columns.length;
	for ( let row_idx = 1; row_idx < num_rows; row_idx++ ) {
		for ( let col_idx = 1; col_idx < num_columns; col_idx++ ) {
			if ( '#rowspan#' === tp.editor.options.data[ row_idx ][ col_idx ] || '#colspan#' === tp.editor.options.data[ row_idx ][ col_idx ] ) {
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
tp.helpers.editor.sorting = function( direction ) {
	direction = direction ? -1 : 1;
	return function( a, b ) {
		// The actual value is stored in the second array element, the first contains the row index.
		return direction * naturalSort( a[1], b[1] );
	};
};

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
			// Designating a head and a foot row only makes sense for tables with more than one row. Single-row tables will only have a table body.
			if ( 1 < visible_rows.length ) {
				if ( tp.table.options.table_head ) {
					visible_rows[0].classList.add( 'head-row' );
				}
				if ( tp.table.options.table_foot ) {
					visible_rows[ visible_rows.length - 1 ].classList.add( 'foot-row' );
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
tp.callbacks.editor.onmove = function (/* el, old_roc_idx, new_roc_idx */) {
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
tp.callbacks.editor.onsort = function (/* el, column, order */) {
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

tp.callbacks.help_box = {};

/**
 * Open the wpdialog for a help box.
 *
 * @param {Event} event [description]
 */
tp.callbacks.help_box.open_dialog = function ( event ) {
	const $helpbox = $( event.target.dataset.helpBox );
	jQuery( $helpbox ).wpdialog( {
		height: $helpbox.dataset.height,
		width: $helpbox.dataset.width,
		minWidth: 260,
		modal: true,
		closeOnEscape: true,
		buttons: [
			{
				text: __( 'OK', 'tablepress' ),
				class: 'button button-ok',
				click() {
					jQuery( this ).wpdialog( 'close' );
				},
			},
		],
		open( /* event, ui */ ) {
			jQuery( this ).next().find( '.button-ok' ).trigger( 'focus' );
		},
	} );
};

tp.callbacks.table_preview = {};

/**
 * Handle showing the table preview.
 *
 * @param {Event} event [description]
 */
tp.callbacks.table_preview.process = function ( event ) {
	// Never follow the link of the Preview button, everything is handled with JS.
	event.preventDefault();

	let table_name = $( '#table-name' ).value;
	if ( '' === table_name.trim() ) {
		table_name = __( '(no name)', 'tablepress' );
	}

	// Initialize the Table Preview wpdialog.
	tp.callbacks.table_preview.$dialog = jQuery( '#table-preview' ).wpdialog( {
		autoOpen: false,
		width: window.innerWidth - 80,
		height: window.innerHeight - 80,
		modal: true,
		title: sprintf( __( 'Preview of table “%1$s” (ID %2$s)', 'tablepress' ), table_name, tp.table.id ),
		closeOnEscape: true,
		buttons: [
			{
				text: __( 'OK', 'tablepress' ),
				class: 'button button-ok',
				click() {
					jQuery( this ).wpdialog( 'close' );
				},
			},
		],
	} );

	// For tables without unsaved changes, show an externally rendered table from a URL in an iframe in a wpdialog.
	if ( ! tp.made_changes ) {
		const $iframe = $( '#table-preview-iframe' );
		$iframe.src = event.target.href;
		$iframe.removeAttribute( 'srcdoc' );
		tp.callbacks.table_preview.$dialog.wpdialog( 'open' );
		return;
	}

	// For tables with unsaved changes, get the table preview HTML code for the iframe via AJAX.

	// Collect information about hidden rows and columns.
	tp.helpers.visibility.update();

	// Prepare the data for the AJAX request.
	const request_data = {
		action: 'tablepress_preview_table',
		_ajax_nonce: tp.nonces.preview_table,
		tablepress: {
			id: tp.table.id,
			new_id: tp.table.new_id,
			name: $( '#table-name' ).value,
			description: $( '#table-description' ).value,
			data: JSON.stringify( tp.editor.options.data ),
			options: JSON.stringify( tp.table.options ),
			visibility: JSON.stringify( tp.table.visibility ),
			number: {
				rows: tp.editor.options.data.length,
				columns: tp.editor.options.columns.length,
			},
		},
	};

	// Add spinner, disable "Preview" buttons, and change cursor.
	event.target.parentNode.insertAdjacentHTML( 'beforeend', `<span id="spinner-table-preview" class="spinner-table-preview spinner is-active" title="${ __( 'The Table Preview is being loaded …', 'tablepress' ) }"/>` );
	$( '.button-show-preview' ).forEach( ( button ) => button.classList.add( 'disabled' ) );
	document.body.classList.add( 'wait' );

	// Load the table preview data from the server via an AJAX request.
	fetch( ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Accept: 'application/json',
		},
		body: buildQueryString( request_data ),
	} )
	// Check for HTTP connection problems.
	.then( ( response ) => {
		if ( ! response.ok ) {
			throw new Error( `There was a problem with the server, HTTP response code ${ response.status } (${ response.statusText }).` );
		}
		return response.json();
	} )
	// Check for problems with the transmitted data.
	.then( ( data ) => {
		if ( 'undefined' === typeof data || null === data || '-1' === data || 'undefined' === typeof data.success ) {
			throw new Error( 'The JSON data returned from the server is unclear or incomplete.' );
		}

		if ( true !== data.success ) {
			throw new Error( 'The preview could not be loaded.' );
		}

		tp.callbacks.table_preview.success( data );
	} )
	// Handle errors.
	.catch( ( error ) => tp.callbacks.table_preview.error( error.message ) )
	.finally( () => {
		$( '#spinner-table-preview' ).remove();
		$( '.button-show-preview' ).forEach( ( button ) => button.classList.remove( 'disabled' ) );
		document.body.classList.remove( 'wait' );
	} );
};

/**
 * [success description]
 *
 * @param {[type]} data [description]
 */
tp.callbacks.table_preview.success = function ( data ) {
	const $iframe = $( '#table-preview-iframe' );
	$iframe.src = '';
	$iframe.srcdoc = `<!DOCTYPE html><html><head>${ data.head_html }</head><body>${ data.body_html }</body></html>`;

	tp.callbacks.table_preview.$dialog.wpdialog( 'open' );
};

/**
 * [error description]
 *
 * @param {[type]} message [description]
 */
tp.callbacks.table_preview.error = function ( message ) {
	message = __( 'Attention: Unfortunately, an error occurred.', 'tablepress' ) + ' ' + message;
	const div_id = `show-preview-${ Date.now() }`;

	$( '#spinner-table-preview' ).parentNode.insertAdjacentHTML( 'afterend', `<div id="${ div_id }" class="ajax-alert notice notice-error"><p>${ message }</p></div>` );

	const $notice = $( `#${ div_id }` );
	void $notice.offsetWidth; // Trick browser layout engine. Necessary to make CSS transition work.
	$notice.style.opacity = 0;
	$notice.addEventListener( 'transitionend', () => $notice.remove() );
};

tp.callbacks.save_changes = {};

/**
 * Save Changes to the server.
 *
 * @param {Event} event [description]
 */
tp.callbacks.save_changes.process = function ( event ) {
	// Validate input fields.
	if ( ! tp.helpers.options.validate_fields() ) {
		return;
	}

	// Collect information about hidden rows and columns.
	tp.helpers.visibility.update();

	// Prepare the data for the AJAX request.
	const request_data = {
		action: 'tablepress_save_table',
		_ajax_nonce: tp.nonces.edit_table,
		tablepress: {
			id: tp.table.id,
			new_id: tp.table.new_id,
			name: $( '#table-name' ).value,
			description: $( '#table-description' ).value,
			data: JSON.stringify( tp.editor.options.data ),
			options: JSON.stringify( tp.table.options ),
			visibility: JSON.stringify( tp.table.visibility ),
			number: {
				rows: tp.editor.options.data.length,
				columns: tp.editor.options.columns.length,
			},
		},
	};

	// Add spinner, disable "Save Changes" buttons, and change cursor.
	event.target.parentNode.insertAdjacentHTML( 'beforeend', `<span id="spinner-save-changes" class="spinner-save-changes spinner is-active" title="${ __( 'Changes are being saved …', 'tablepress' ) }"/>` );
	$( '.button-save-changes' ).forEach( ( button ) => ( button.disabled = true ) );
	document.body.classList.add( 'wait' );

	// Save the table data to the server via an AJAX request.
	fetch( ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Accept: 'application/json',
		},
		body: buildQueryString( request_data ),
	} )
	// Check for HTTP connection problems.
	.then( ( response ) => {
		if ( ! response.ok ) {
			throw new Error( `There was a problem with the server, HTTP response code ${ response.status } (${ response.statusText }).` );
		}
		return response.json();
	} )
	// Check for problems with the transmitted data.
	.then( ( data ) => {
		if ( 'undefined' === typeof data || null === data || '-1' === data || 'undefined' === typeof data.success ) {
			throw new Error( 'The JSON data returned from the server is unclear or incomplete.' );
		}

		if ( true !== data.success ) {
			const error_introduction = __( 'These errors were encountered:', 'tablepress' );
			const debug_html = data.error_details ? `</p><p>${ error_introduction }</p><pre>${ data.error_details }</pre><p>` : '';
			throw new Error( `The table could not be saved to the database properly.${ debug_html }` );
		}

		tp.callbacks.save_changes.success( data );
	} )
	// Handle errors.
	.catch( ( error ) => tp.callbacks.save_changes.error( error.message ) )
	.finally( () => {
		$( '#spinner-save-changes' ).remove();
		$( '.button-save-changes' ).forEach( ( button ) => ( button.disabled = false ) );
		document.body.classList.remove( 'wait' );
	} );
};

/**
 * [success description]
 *
 * @param {[type]} data [description]
 */
tp.callbacks.save_changes.success = function ( data ) {
	// Saving was successful, so the original ID has changed to the (maybe) new ID -> we need to adjust all occurrences.
	if ( tp.table.id !== data.table_id && window?.history?.pushState ) {
		// Update URL, but only if the table ID changed, to not get dummy entries in the browser history.
		window.history.pushState( '', '', window.location.href.replace( /table_id=[0-9a-zA-Z-_]+/gi, `table_id=${ data.table_id }` ) );
	}

	// Update table ID in input field.
	tp.table.id = data.table_id;
	tp.table.new_id = data.table_id;
	$( '#table-id' ).value = data.table_id;
	const $shortcode_field = $( '#table-information-shortcode' );
	if ( $shortcode_field ) {
		$shortcode_field.value = `[${ tp.table.shortcode } id=${ data.table_id } /]`;
	}

	// Update the nonces.
	tp.nonces.edit_table = data.new_edit_nonce;
	tp.nonces.preview_table = data.new_preview_nonce;

	// Update URLs in Preview links.
	$( '.button-show-preview' ).forEach( ( button ) => {
		button.href = button.href
			.replace( /item=[a-zA-Z0-9_-]+/g, `item=${ data.table_id }` )
			.replace( /&_wpnonce=[a-z0-9]+/ig, `&_wpnonce=${ data.new_preview_nonce }` );
	} );

	// Update last-modified date and user nickname.
	$( '#last-modified' ).textContent = data.last_modified;
	$( '#last-editor' ).textContent = data.last_editor;

	tp.helpers.unsaved_changes.unset();

	const action_messages = {};
	action_messages.success_save = __( 'The table was saved successfully.', 'tablepress' );
	action_messages.success_save_success_id_change = action_messages.success_save + ' ' + __( 'The table ID was changed.', 'tablepress' );
	action_messages.success_save_error_id_change = action_messages.success_save + ' ' + __( 'The table ID could not be changed, probably because the new ID is already in use!', 'tablepress' );

	if ( 'success_save_error_id_change' === data.message && data.error_details ) {
		const error_introduction = __( 'These errors were encountered:', 'tablepress' );
		action_messages.success_save_error_id_change += `</p><p>${ error_introduction }</p><pre>${ data.error_details }</pre><p>`;
	}

	const type = ( data.message.includes( 'error' ) ) ? 'error' : 'success';
	tp.callbacks.save_changes.after_saving_notice( type, action_messages[ data.message ] );
};

/**
 * [error description]
 *
 * @param {[type]} message [description]
 */
tp.callbacks.save_changes.error = function ( message ) {
	message = __( 'Attention: Unfortunately, an error occurred.', 'tablepress' ) + ' ' + message;
	tp.callbacks.save_changes.after_saving_notice( 'error', message );
};

/**
 * [after_saving_notice description]
 *
 * @param {[type]} type    [description]
 * @param {[type]} message [description]
 */
tp.callbacks.save_changes.after_saving_notice = function ( type, message ) {
	const div_id = `save-changes-${ Date.now() }`;

	$( '#spinner-save-changes' ).parentNode.insertAdjacentHTML( 'afterend', `<div id="${ div_id }" class="ajax-alert notice notice-${ type }"><p>${ message }</p></div>` );

	const $notice = $( `#${ div_id }` );
	void $notice.offsetWidth; // Trick browser layout engine. Necessary to make CSS transition work.
	$notice.style.opacity = 0;
	$notice.addEventListener( 'transitionend', () => $notice.remove() );
};

tp.callbacks.screen_options = {};

/**
 * Updates table editor layout with new screen option values.
 *
 * @param {Event} event `input` event of the screen options fields.
 */
tp.callbacks.screen_options.update = function ( event ) {
	if ( ! event.target ) {
		return;
	}

	if ( 'table_editor_line_clamp' === event.target.id ) {
		tp.editor.el.style.setProperty( '--table-editor-line-clamp', parseInt( event.target.value, 10 ) );
		tp.editor.updateCornerPosition();
		return;
	}

	if ( 'table_editor_column_width' === event.target.id ) {
		tp.screen_options.table_editor_column_width = parseInt( event.target.value, 10 );
		tp.screen_options.table_editor_column_width = Math.max( tp.screen_options.table_editor_column_width, 30 ); // Ensure a minimum column width of 30 pixesl.
		tp.screen_options.table_editor_column_width = Math.min( tp.screen_options.table_editor_column_width, 9999 ); // Ensure a maximum column width of 9999 pixesl.
		tp.editor.colgroup.forEach( ( col ) => col.setAttribute( 'width', tp.screen_options.table_editor_column_width ) );
		tp.editor.updateCornerPosition();
		return;
	}
};

/**
 * Designates a screen option field to have been changed, so that the value is sent to the server when it is blurred.
 *
 * @param {Event} event `change` event of the screen options fields.
 */
tp.callbacks.screen_options.set_was_changed = function ( event ) {
	if ( ! event.target ) {
		return;
	}

	event.target.was_changed = true;
};

/**
 * Saves screen options to the server after they have been changed and the field is blurred.
 *
 * @param {Event} event `blur` event of the screen options fields.
 */
tp.callbacks.screen_options.save = function ( event ) {
	if ( ! event.target ) {
		return;
	}

	if ( ! event.target.was_changed ) {
		return;
	}

	event.target.was_changed = false;

	// Prepare the data for the AJAX request.
	const request_data = {
		action: 'tablepress_save_screen_options',
		_ajax_nonce: tp.nonces.screen_options,
		tablepress: {
			[ event.target.id ]: parseInt( event.target.value, 10 ),
		},
	};

	// Add spinner and change cursor.
	event.target.parentNode.insertAdjacentHTML( 'beforeend', `<span id="spinner-save-changes" class="spinner-save-changes spinner is-active" title="${ __( 'Changes are being saved …', 'tablepress' ) }"/>` );
	document.body.classList.add( 'wait' );

	// Save the table data to the server via an AJAX request.
	fetch( ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Accept: 'application/json',
		},
		body: buildQueryString( request_data ),
	} )
	.finally( () => {
		$( '#spinner-save-changes' ).remove();
		document.body.classList.remove( 'wait' );
	} );
};

tp.callbacks.table_id = tp.callbacks.table_id || {};

/**
 * [sanitize_table_id description]
 */
tp.callbacks.table_id.sanitize = function () {
	this.value = this.value.replace( /[^0-9a-zA-Z-_]/g, '' );
};

/**
 * [change_table_id description]
 */
tp.callbacks.table_id.change = function () {
	// The table IDs "" and "0" are not allowed, or in other words, the table ID has to fulfill /[A-Za-z1-9-_]|[A-Za-z0-9-_]{2,}/.
	if ( '' === this.value || '0' === this.value ) {
		window.alert( __( 'This table ID is invalid. Please enter a different table ID.', 'tablepress' ) );
		this.value = tp.table.new_id;
		this.focus();
		this.select();
		return;
	}

	if ( ! window.confirm( __( 'Do you really want to change the Table ID? All blocks and Shortcodes for this table in your posts and pages will have to be adjusted!', 'tablepress' ) ) ) {
		this.value = tp.table.new_id;
		return;
	}

	// Set the new table ID.
	tp.table.new_id = this.value;
	const $shortcode_field = $( '#table-information-shortcode' );
	if ( $shortcode_field ) {
		$shortcode_field.value = `[${ tp.table.shortcode } id=${ tp.table.new_id } /]`;
		$shortcode_field.focus();
		$shortcode_field.select();
	}
	tp.helpers.unsaved_changes.set();
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

/**
 * Registers keyboard events and triggers corresponding actions by emulating button clicks.
 *
 * @param {Event} event Keyboard event.
 */
tp.callbacks.keyboard_shortcuts = function ( event ) {
	let action = '';
	let move_direction = '';
	let move_type = '';

	if ( event.ctrlKey || event.metaKey ) {
		if ( 80 === event.keyCode ) {
			// Preview: Ctrl/Cmd + P.
			action = 'show-preview';
		} else if ( 83 === event.keyCode ) {
			// Save Changes: Ctrl/Cmd + S.
			action = 'save-changes';
		} else if ( 76 === event.keyCode ) {
			// Insert Link: Ctrl/Cmd + L.
			action = 'insert_link';
		} else if ( 73 === event.keyCode ) {
			// Insert Image: Ctrl/Cmd + I.
			action = 'insert_image';
		} else if ( 69 === event.keyCode ) {
			// Advanced Editor: Ctrl/Cmd + E.
			action = 'advanced_editor';
		} else if ( event.shiftKey && event.altKey && 38 === event.keyCode ) {
			// Move up: Ctrl/Cmd + Alt/Option + Shift + ↑.
			action = 'move';
			move_direction = 'top';
			move_type = 'rows';
		} else if ( event.shiftKey && event.altKey && 40 === event.keyCode ) {
			// Move down: Ctrl/Cmd + Alt/Option + Shift + ↓.
			action = 'move';
			move_direction = 'bottom';
			move_type = 'rows';
		} else if ( event.shiftKey && event.altKey && 37 === event.keyCode ) {
			// Move left: Ctrl/Cmd + Alt/Option + Shift + ←.
			action = 'move';
			move_direction = 'first';
			move_type = 'columns';
		} else if ( event.shiftKey && event.altKey && 39 === event.keyCode ) {
			// Move r: Ctrl/Cmd + Alt/Option + Shift + →.
			action = 'move';
			move_direction = 'last';
			move_type = 'columns';
		} else if ( event.shiftKey && 38 === event.keyCode ) {
			// Move up: Ctrl/Cmd + Shift + ↑.
			action = 'move';
			move_direction = 'up';
			move_type = 'rows';
		} else if ( event.shiftKey && 40 === event.keyCode ) {
			// Move down: Ctrl/Cmd + Shift + ↓.
			action = 'move';
			move_direction = 'down';
			move_type = 'rows';
		} else if ( event.shiftKey && 37 === event.keyCode ) {
			// Move left: Ctrl/Cmd + Shift + ←.
			action = 'move';
			move_direction = 'left';
			move_type = 'columns';
		} else if ( event.shiftKey && 39 === event.keyCode ) {
			// Move r: Ctrl/Cmd + Shift + →.
			action = 'move';
			move_direction = 'right';
			move_type = 'columns';
		}
	}

	if ( 'save-changes' === action || 'show-preview' === action ) {
		// Blur the focussed element to make sure that all change events were triggered.
		document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element

		/*
		 * Emulate a click on the button corresponding to the action.
		 * This way, things like notices will be shown, compared to directly calling the buttons' callbacks.
		 */
		document.querySelector( `#tablepress_edit-buttons-2-submit .button-${ action }` ).click();

		// Prevent the browser's native handling of the shortcut, i.e. showing the Save or Print dialogs.
		event.preventDefault();
	} else if ( 'insert_link' === action || 'insert_image' === action || 'advanced_editor' === action ) {
		// Only open the dialogs if an element in the table editor is focussed, to e.g. prevent multiple dialogs to be opened.
		if ( $( '#table-editor' ).contains( document.activeElement ) ) { // eslint-disable-line @wordpress/no-global-active-element
			const $active_textarea = ( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null; // eslint-disable-line @wordpress/no-global-active-element
			// Open the "Insert Link", "Insert Image", or Advanced Editor" dialog.
			tp.callbacks[ action ].open_dialog( $active_textarea );
		}

		// Prevent the browser's native handling of the shortcut.
		event.preventDefault();
	} else if ( 'move' === action ) {
		// Only move rows or columns if an element in the table editor is focussed, but not if the cell is being edited (to not prevent the browser's original shortcuts).
		if ( $( '#table-editor' ).contains( document.activeElement ) && 'TEXTAREA' !== document.activeElement.tagName ) { // eslint-disable-line @wordpress/no-global-active-element
			// Move the selected rows or columns.
			if ( tp.helpers.move_allowed( move_type, move_direction ) ) {
				tp.callbacks.move( move_direction, move_type );
			}
		}

		// Stop the event propagation so that Jspreadsheet doesn't understand the arrow key as movement of the cursor, and prevent the browser's native handling of the shortcut.
		event.stopImmediatePropagation();
	}
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
	defaultColWidth: tp.screen_options.table_editor_column_width,
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

tp.helpers.options.load();

/*
 * Register click callback for the "Preview" and "Save Changes" buttons.
 */
$( '#tablepress-page' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
		return;
	}

	if ( event.target.matches( '.button-show-preview' ) ) {
		tp.callbacks.table_preview.process( event );
		return;
	}

	if ( event.target.matches( '.button-save-changes' ) ) {
		tp.callbacks.save_changes.process( event );
		return;
	}

	if ( event.target.matches( '.button-show-help-box' ) ) {
		tp.callbacks.help_box.open_dialog( event );
		return;
	}
} );

/*
 * Register click callbacks for the table manipulation buttons.
 */
$( '#tablepress-manipulation-controls' ).addEventListener( 'click', ( event ) => {
	if ( ! event.target ) {
		return;
	}

	/*
	 * Events that don't require a selection.
	 */

	if ( event.target.matches( '.button-append' ) ) {
		const type = event.target.dataset.type;
		const $input_field = $( `#${ type }-append-number` );
		const num_rocs = parseInt( $input_field.value, 10 );
		if ( isNaN( num_rocs ) || num_rocs < 1 || num_rocs > 99999 ) {
			const message = ( 'rows' === event.target.dataset.type ) ? __( 'The value for the number of rows is invalid!', 'tablepress' ) : __( 'The value for the number of columns is invalid!', 'tablepress' );
			window.alert( message );
			$input_field.focus();
			$input_field.select();
			return;
		}

		tp.callbacks.append( type, num_rocs );
		return;
	}

	/*
	 * Events that do require a selection.
	 */

	if ( 'button-insert-link' === event.target.id ) {
		tp.callbacks.insert_link.open_dialog();
		return;
	}

	if ( 'button-insert-image' === event.target.id ) {
		tp.callbacks.insert_image.open_dialog();
		return;
	}

	if ( 'button-advanced-editor' === event.target.id ) {
		tp.callbacks.advanced_editor.open_dialog();
		return;
	}

	if ( event.target.matches( '.button-insert-duplicate' ) ) {
		tp.callbacks.insert_duplicate( event.target.dataset.action, event.target.dataset.type );
		return;
	}

	if ( event.target.matches( '.button-move' ) ) {
		if ( ! tp.helpers.move_allowed( event.target.dataset.type, event.target.dataset.direction ) ) {
			window.alert( __( 'You can not do this move, because you reached the border of the table.', 'tablepress' ) );
			return;
		}
		tp.callbacks.move( event.target.dataset.direction, event.target.dataset.type );
		return;
	}

	if ( event.target.matches( '.button-remove' ) ) {
		const handling_rows = ( 'rows' === event.target.dataset.type );
		const num_rocs = handling_rows ? tp.editor.options.data.length : tp.editor.options.columns.length;

		if ( num_rocs === tp.helpers.selection[ event.target.dataset.type ].length ) {
			const message = handling_rows ? __( 'You can not delete all table rows!', 'tablepress' ) : __( 'You can not delete all table columns!', 'tablepress' );
			window.alert( message );
			return;
		}

		tp.callbacks.remove( event.target.dataset.type );
		return;
	}

	if ( event.target.matches( '.button-merge-unmerge' ) ) {
		if ( tp.helpers.cell_merge_allowed( 'alert' ) ) {
			tp.callbacks.merge_cells();
		}
		return;
	}

	if ( event.target.matches( '.button-hide-unhide' ) ) {
		tp.callbacks.hide_unhide( event.target.dataset.action, event.target.dataset.type );
		return;
	}
} );

// Register callbacks for the table ID text field.
const $table_id_field = $( '#table-id' );
$table_id_field.addEventListener( 'input', tp.callbacks.table_id.sanitize );
$table_id_field.addEventListener( 'change', tp.callbacks.table_id.change );

// Select Shortcode input field content when it's focussed.
const $table_information_shortcode = $( '#table-information-shortcode' );
if ( $table_information_shortcode ) {
	$table_information_shortcode.addEventListener( 'focus', function() {
		this.select();
	} );
}

// Register callback for inserting a link into a cell after it has been constructed in the wpLink dialog.
jQuery( '#textarea-insert-helper' ).on( 'change', tp.helpers.editor.insert_from_helper_textarea ); // This must use jQuery, as wpLink triggers jQuery events, which can not be observed by native JS listeners.

// Register change callbacks for the table name, description, and options.
[ '#table-name', '#table-description' ].forEach( ( field_id ) => $( field_id ).addEventListener( 'change', tp.helpers.unsaved_changes.set ) );
const options_meta_boxes = apply_filters( 'tablepress.optionsMetaBoxes', [ '#tablepress_edit-table-options', '#tablepress_edit-datatables-features' ] );
options_meta_boxes.forEach( ( meta_box_id ) => $( meta_box_id ).addEventListener( 'change', tp.helpers.options.change ) );

// Move all "Help" buttons inside the postbox header.
document.querySelectorAll( '#tablepress-body .button-module-help' ).forEach( ( $button ) => ( $button.closest( '.postbox' ).querySelector( '.handle-actions' ).prepend( $button ) ) );

// Register callbacks for the screen options.
const $tablepress_screen_options = $( '#tablepress-screen-options' );
$tablepress_screen_options.addEventListener( 'input', tp.callbacks.screen_options.update );
$tablepress_screen_options.addEventListener( 'change', tp.callbacks.screen_options.set_was_changed );
$tablepress_screen_options.addEventListener( 'focusout', tp.callbacks.screen_options.save ); // Use the `focusout` event instead of `blur` as that does not bubble.

// Register keyboard shortcut handler.
window.addEventListener( 'keydown', tp.callbacks.keyboard_shortcuts, true );

// Add keyboard shortcuts as title attributes to "Preview" and "Save Changes" buttons, with correct modifier key for Mac/non-Mac.
const modifier_key = ( window?.navigator?.platform?.includes( 'Mac' ) ) ?
	_x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) :
	_x( 'Ctrl+', 'keyboard shortcut modifier key on a non-Mac keyboard', 'tablepress' );
document.querySelectorAll( '.button[data-shortcut]' ).forEach( ( $button ) => {
	const shortcut = sprintf( $button.dataset.shortcut, modifier_key ); // eslint-disable-line @wordpress/valid-sprintf
	$button.title = sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), shortcut );
} );

// This code requires jQuery, and it must run when the DOM is ready. Therefore, move it outside of the main function.
jQuery( function () {
	// Fix issue with wpLink input fields not being usable, when called through the "Advanced Editor". They are immediately losing focus without this.
	jQuery( '#wp-link' ).on( 'focus', 'input', function ( event ) {
		event.stopPropagation();
	} );

	// Fix issue with Media Library input fields in the sidebar not being usable, when called through the "Advanced Editor". They are immediately losing focus without this.
	jQuery( 'body' ).on( 'focus', '.media-modal .media-frame-content input, .media-modal .media-frame-content textarea', function ( event ) {
		event.stopPropagation();
	} );
} );
