/**
 * JavaScript code for the keyboard shortcuts integration on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals tp */

/**
 * Internal dependencies.
 */
import { $ } from '../common/functions';

/**
 * Registers keyboard events and triggers corresponding actions by emulating button clicks.
 *
 * @param {Event} event Keyboard event.
 */
const handleKeyboardShortcuts = ( event ) => {
	let action = '';
	let move_direction = '';
	let move_type = '';

	if ( event.ctrlKey || event.metaKey ) {
		if ( 76 === event.keyCode ) {
			// Insert Link: Ctrl/Cmd + L.
			action = 'insert_link';
		} else if ( 73 === event.keyCode ) {
			// Insert Image: Ctrl/Cmd + I.
			action = 'insert_image';
		} else if ( 69 === event.keyCode ) {
			// Advanced Editor: Ctrl/Cmd + E.
			action = 'advanced_editor';
		} else if ( event.shiftKey ) {
			if ( 38 === event.keyCode ) {
				action = 'move';
				move_type = 'rows';
				if ( event.altKey ) {
					move_direction = 'top'; // Move to top: Ctrl/Cmd + Alt/Option + Shift + ↑.
				} else {
					move_direction = 'up'; // Move up: Ctrl/Cmd + Shift + ↑.
				}
			} else if ( 40 === event.keyCode ) {
				action = 'move';
				move_type = 'rows';
				if ( event.altKey ) {
					move_direction = 'bottom'; // Move to bottom: Ctrl/Cmd + Alt/Option + Shift + ↓.
				} else {
					move_direction = 'down'; // Move down: Ctrl/Cmd + Shift + ↓.
				}
			} else if ( 37 === event.keyCode ) {
				action = 'move';
				move_type = 'columns';
				if ( event.altKey ) {
					move_direction = 'first'; // Move to left end: Ctrl/Cmd + Alt/Option + Shift + ←.
				} else {
					move_direction = 'left'; // Move left: Ctrl/Cmd + Shift + ←.
				}
			} else if ( 39 === event.keyCode ) {
				action = 'move';
				move_type = 'columns';
				if ( event.altKey ) {
					move_direction = 'last'; // Move to right end: Ctrl/Cmd + Alt/Option + Shift + →.
				} else {
					move_direction = 'right'; // Move right: Ctrl/Cmd + Shift + →.
				}
			}
		}
	}

	// Return early if no action was triggered.
	if ( '' === action ) {
		return;
	}

	if ( 'insert_link' === action || 'insert_image' === action || 'advanced_editor' === action ) {
		// Only open the dialogs if an element in the table editor is focussed, to e.g. prevent multiple dialogs to be opened.
		if ( $( '#table-editor' ).contains( document.activeElement ) ) { // eslint-disable-line @wordpress/no-global-active-element
			const $active_textarea = ( 'TEXTAREA' === document.activeElement.tagName ) ? document.activeElement : null; // eslint-disable-line @wordpress/no-global-active-element
			// Blur the active textarea to make sure that all change events were triggered.
			$active_textarea?.blur(); // eslint-disable-line @wordpress/no-global-active-element
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

// Register keyboard shortcut handler.
window.addEventListener( 'keydown', handleKeyboardShortcuts, true );
