/**
 * JavaScript code for the "Import" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals jSuites */

/**
 * WordPress dependencies.
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { $ } from './_common-functions';

const data = {
	source: '',
	type: '',
};

const $import_form = $( '#tablepress_import-import-form' );
const $tables_import_file_upload_field = $( '#tables-import-file-upload' );
const $tables_import_file_upload_field_dropzone = $( '#tables-import-file-upload-dropzone' );
const $tables_import_server_field = $( '#tables-import-server' ); // The input field only exists for admins!
const $import_existing_table_dropdown = $( '#tables-import-existing-table' );

/**
 * Progressively enhance the "Table to replace or append to" HTML select field with live-search and auto-complete.
 *
 * @since 2.0.0
 */
jSuites.dropdown( $import_existing_table_dropdown, {
	autocomplete: true,
	placeholder: $import_existing_table_dropdown.options[0].textContent,
} );

/**
 * Set state of visible form elements depending on selected options.
 *
 * @since 2.0.0
 */
$import_form.addEventListener( 'change', () => {
	data.source = document.querySelector( '#row-import-source input:checked' ).value;
	data.type = document.querySelector( '#row-import-type input:checked' ).value;

	// Don't show validation :invalid CSS pseudo-selector highlighting, but only when the user wants to submit the form.
	$import_form.classList.add( 'no-validation-highlighting' );
	// Reset validation messages after a change to a form field was made.
	$tables_import_server_field?.setCustomValidity( '' ); // The input field only exists for admins!
	$import_existing_table_dropdown.previousElementSibling.querySelector( '.jdropdown-header' ).setCustomValidity( '' ); // Use the jSuites dropdown input field, as the actual <select> is hidden.

	// Show the correct input field section depending on the chosen import source. Set the contained form field to required.
	[ 'file-upload', 'url', 'server', 'form-field' ].forEach( ( source ) => {
		$( `#row-import-source-${ source }` ).style.display = ( source === data.source ) ? 'table-row' : 'none';
		$( `#tables-import-${ source }` ).required = ( source === data.source );
	} );
	// Only make the URL field a URL field if URL is the selected import source, to prevent validation errors.
	$( '#tables-import-url' ).type = ( 'url' === data.source ) ? 'url' : 'text';

	// Disable the existing table dropdown when adding new tables, or when uploading more than one file, or when uploading a ZIP file.
	const dropdown_disabled = ( 'add' === data.type ||
		(
			'file-upload' === data.source &&
			(
				1 < $tables_import_file_upload_field?.files?.length ||
				( 1 === $tables_import_file_upload_field?.files?.length && $tables_import_file_upload_field?.files?.[0].name.endsWith( '.zip' ) )
			)
		)
	);
	$import_existing_table_dropdown.disabled = dropdown_disabled;
	$import_existing_table_dropdown.previousElementSibling.classList.toggle( 'disabled', dropdown_disabled ); // Disable the artificial dropdown via a class, as it can not use :disabled.
} );
document.querySelector( '#row-import-source input:checked' ).dispatchEvent( new Event( 'change', { bubbles: true } ) ); // Trigger the change handler on page load to initialize the form fields.

/**
 * Add a list of selected files for the import from file upload.
 *
 * @since 2.0.0
 */
$tables_import_file_upload_field.addEventListener( 'change', function( event) {
	if ( event?.target?.files?.length > 0 ) {
		$tables_import_file_upload_field_dropzone.innerHTML = `<span>${ sprintf( _n( 'You have selected %1$d file:', 'You have selected %1$d files:', event.target.files.length, 'tablepress' ), event.target.files.length ) }</span>`;
		[ ...event.target.files ].forEach( ( file ) => {
			const $file_span = document.createElement( 'span' );
			$file_span.textContent = file.name;
			$tables_import_file_upload_field_dropzone.appendChild( $file_span );
		} );
	} else {
		$tables_import_file_upload_field_dropzone.innerHTML = `<span>${ __( 'Click to select a file, or drag it here.', 'tablepress' ) }</span>`;
	}
} );

/**
 * Visualize when files are dragged over the file upload drop zone.
 *
 * @since 2.0.0
 */
$tables_import_file_upload_field.addEventListener( 'dragenter', function() {
	$tables_import_file_upload_field_dropzone.classList.add( 'dragover' );
} );
$tables_import_file_upload_field.addEventListener( 'dragleave', function() {
	$tables_import_file_upload_field_dropzone.classList.remove( 'dragover' );
} );

/**
 * Check, whether special form elements are valid.
 *
 * The submit button's `click` event is used so that the validation warnings can be shown.
 * This does not work when using the form's `submit` event.
 *
 * @since 2.0.0
 */
$( '#import-submit-button' ).addEventListener( 'click', () => {
	// Show validation :invalid CSS pseudo-selector highlighting.
	$import_form.classList.remove( 'no-validation-highlighting' );

	// When importing from the server, the value must have been changed from the default (normally ABSPATH). The input field only exists for admins!
	if ( 'server' === data.source && $tables_import_server_field ) {
		if ( $tables_import_server_field.defaultValue === $tables_import_server_field.value ) {
			$tables_import_server_field.setCustomValidity( __( 'You must specify a path to a file on the server.', 'tablepress' ) );
		}
	}

	// If replace or append is selected while a single non-ZIP file is to be uploaded, a table must be selected.
	if ( ( 'replace' === data.type || 'append' === data.type ) &&
		(
			'file-upload' !== data.source ||
			( 1 === $tables_import_file_upload_field?.files?.length && ! $tables_import_file_upload_field?.files?.[0].name.endsWith( '.zip' ) )
			)
		) {
		// The "- Select or type -" entry has an empty string as its value.
		if ( '' === $import_existing_table_dropdown.value ) {
			$import_existing_table_dropdown.previousElementSibling.querySelector( '.jdropdown-header' ).setCustomValidity( __( 'You must select a table.', 'tablepress' ) ); // Use the jSuites dropdown input field, as the actual <select> is hidden.
		}
	}
} );
