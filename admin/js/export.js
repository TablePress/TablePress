/**
 * JavaScript code for the "Export" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/**
 * WordPress dependencies.
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';

const $tables_export_dropdown = $( '#tables-export' );
const $cb_tables_export_zip_file = $( '#tables-export-zip-file' );

/**
 * Change keyboard keys for multi-table export when the user is using a Mac.
 *
 * @since 2.0.0
 */
if ( window?.navigator?.platform?.includes( 'Mac' ) ) {
	const description = $( '#tables-export-shortcut-description' );
	if ( description ) {
		description.textContent = sprintf( __( 'You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.', 'tablepress' ), _x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ), _x( 'Shift', 'keyboard key', 'tablepress' ) );
	}
}

/**
 * Check, whether inputs are valid.
 *
 * @since 1.0.0
 */
document.querySelector( '#tablepress-page form' ).addEventListener( 'submit', function ( event ) {
	const selected_tables = [ ...$tables_export_dropdown.selectedOptions ].map( ( option ) => option.value );

	// Don't submit the form if no table was selected.
	if ( 0 === selected_tables.length ) {
		event.preventDefault();
		return;
	}

	/*
	 * Add selected tables as a comma-separated list to a hidden field.
	 * The import will then prefer that value over the transmitted array values of the dropdown.
	 */
	$( '#tables-export-list' ).value = selected_tables.join();

	// On form submit: Enable disabled fields, so that they are sent in the HTTP POST request.
	$cb_tables_export_zip_file.disabled = false;
} );

/**
 * Show export delimiter dropdown box only if export format is CSV.
 *
 * @since 1.0.0
 */
const $tables_export_format_dropdown = $( '#tables-export-format' );
$tables_export_format_dropdown.addEventListener( 'change', function () {
	const non_csv_selected = ( 'csv' !== this.value );
	$( '#tables-export-csv-delimiter' ).disabled = non_csv_selected;
	$( '#tables-export-csv-delimiter-description' ).style.display = non_csv_selected ? 'inline' : 'none';
} );
$tables_export_format_dropdown.dispatchEvent( new Event( 'change' ) );

/**
 * Automatically check and disable the "ZIP file" checkbox whenever more than one table is selected.
 *
 * @since 1.0.0
 */
let zip_file_manually_checked = false;
$cb_tables_export_zip_file.addEventListener( 'change', function () {
	zip_file_manually_checked = this.checked;
} );

const $cb_tables_export_select_all = $( '#tables-export-select-all' );
$tables_export_dropdown.addEventListener( 'change', function () {
	const zip_file_required = ( $tables_export_dropdown.selectedOptions.length > 1 );
	$cb_tables_export_zip_file.disabled = zip_file_required;
	$cb_tables_export_zip_file.checked = ( zip_file_required || zip_file_manually_checked );
	$( '#tables-export-zip-file-description' ).style.display = zip_file_required ? 'inline' : 'none';
	// Set state of "Select all" checkbox, depending on whether all tables are selected.
	$cb_tables_export_select_all.checked = ( $tables_export_dropdown.options.length === $tables_export_dropdown.selectedOptions.length );
} );
$tables_export_dropdown.dispatchEvent( new Event( 'change' ) );

/**
 * (De-)selects all entries from the multiple-select tables dropdown when the "Select All" checkbox is toggled.
 *
 * @since 1.0.0
 */
$cb_tables_export_select_all.addEventListener( 'change', function () {
	[ ...$tables_export_dropdown.options ].forEach( ( option ) => ( option.selected = this.checked ) );
	$tables_export_dropdown.dispatchEvent( new Event( 'change' ) ); // Update ZIP file checkbox.
} );

/**
 * Reverses all entries of the multiple-select tables dropdown when the "Reverse list" checkbox is toggled.
 *
 * @since 2.1.0
 */
$( '#tables-export-reverse-list' ).addEventListener( 'change', function () {
	[ ...$tables_export_dropdown.children ].forEach( ( option ) => {
		$tables_export_dropdown.prepend( option );
	} );
} );
