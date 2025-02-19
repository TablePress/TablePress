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
		if ( 80 === event.keyCode && tp.screen_options.currentUserCanPreviewTable ) {
			// Preview: Ctrl/Cmd + P.
			action = 'preview';
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

	if ( 'save-changes' === action || 'preview' === action ) {
		// Blur the focussed element to make sure that all change events were triggered.
		document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element

		/*
		 * Emulate a click on the button corresponding to the action.
		 * This way, things like notices will be shown, compared to directly calling the buttons' callbacks.
		 */
		document.querySelector( `#tablepress-tablepress_edit-buttons-2-section .button-${ action }` ).click();

		// Prevent the browser's native handling of the shortcut, i.e. showing the Save or Print dialogs.
		event.preventDefault();
	} else if ( 'insert_link' === action || 'insert_image' === action || 'advanced_editor' === action ) {
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
