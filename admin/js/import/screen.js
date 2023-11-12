/**
 * JavaScript code for the "Import Screen" component.
 *
 * @package TablePress
 * @subpackage Import Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/* globals tp */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef, useState } from 'react';
import { __, _n, _x, sprintf } from '@wordpress/i18n';

// Details for the available import sources.
const importSources = {
	'file-upload': {
		label: __( 'File Upload', 'tablepress' ),
		instruction: __( 'Select files', 'tablepress' ),
	},
	url: {
		label: __( 'URL', 'tablepress' ),
		instruction: __( 'File URL', 'tablepress' ),
	},
	server: {
		label: __( 'File on server', 'tablepress' ),
		instruction: __( 'Server Path to file', 'tablepress' ),
	},
	'form-field': {
		label: __( 'Manual Input', 'tablepress' ),
		instruction: __( 'Import data', 'tablepress' ),
	},
};
if ( ! tp.import.showImportSourceServer ) {
	delete importSources.server;
}

// Number of tables.
const tablesCount = Object.keys( tp.import.tables ).length;

// The <option> entries for the dropdown do not depend on the state, so they can be created once.
const tablesSelectOptions = Object.entries( tp.import.tables ).map( ( [ tableId, tableName ] ) => {
	if ( '' === tableName.trim() ) {
		tableName = __( '(no name)', 'tablepress' );
	}
	const optionText = sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), tableId, tableName );
	return <option key={ tableId } value={ tableId }>{ optionText }</option>;
} );

/**
 * Returns the "Import Screen" component's JSX markup.
 *
 * @return {Object} Import Screen component.
 */
const ImportScreen = () => {
	const [ screenData, setScreenData ] = useState( {
		importSource: tp.import.importSource,
		importType: tp.import.importType,
		importFileUpload: [],
		importUrl: tp.import.importUrl,
		importServer: tp.import.importServer,
		importFormField: tp.import.importFormField,
		importExistingTable: tp.import.importExistingTable,
		validationHighlighting: false,
	} );

	/**
	 * Handles screen data state changes.
	 *
	 * @param {string}      item  Configuration item name.
	 * @param {string|null} value Value for configuration item.
	 */
	const updateScreenData = ( item, value ) => {
		const newScreenData = {
			...screenData,
			validationHighlighting: false, // Reset with every UI state change.
			[ item ]: value,
		};
		setScreenData( newScreenData );
	};

	// References to DOM elements.
	const importServerInput = useRef( null );
	const appendReplaceDropdown = useRef( null );
	const fileUploadDropzone = useRef( null );

	// Initialize the jSuites dropdown when the component is mounted.
	useEffect( () => {
		jSuites.dropdown( appendReplaceDropdown.current, {
			autocomplete: true,
			placeholder: __( '— Select or type —', 'tablepress' ),
			onchange: ( element, index, oldValue, newValue ) => {
				// Directly update the state with an updater function, as the state is otherwise reset.
				setScreenData( ( newScreenData ) => ( {
					...newScreenData,
					validationHighlighting: false,
					importExistingTable: newValue,
				} ) );
			},
		} );
	}, [] );

	// Update the validation highlighting (using APIs and DOM elements outside of the React components) when the state changes.
	useEffect( () => {
		document.getElementById( 'tablepress_import-import-form' ).classList.toggle( 'no-validation-highlighting', ! screenData.validationHighlighting );
		if ( ! screenData.validationHighlighting ) {
			importServerInput.current?.setCustomValidity( '' );
			appendReplaceDropdown.current.previousElementSibling.querySelector( '.jdropdown-header' )?.setCustomValidity( '' );
		}
	}, [ screenData.validationHighlighting ] );

	// Determine calculated state variables to avoid repeating calculations.
	const fileUploadMultipleFilesChosen = (
		'file-upload' === screenData.importSource
		&& (
			1 < screenData.importFileUpload.length
			|| (
				1 === screenData.importFileUpload.length
				&& screenData.importFileUpload[0].name.endsWith( '.zip' )
			)
		)
	);
	const appendReplaceDropdownDisabled = (
		0 === tablesCount
		|| 'add' === screenData.importType
		|| fileUploadMultipleFilesChosen
	);

	// Disable the artificial dropdown (not inserted by React) via a class, as it can not use :disabled.
	useEffect( () => {
		appendReplaceDropdown.current.previousElementSibling.classList.toggle( 'disabled', appendReplaceDropdownDisabled );
	}, [ appendReplaceDropdownDisabled ] );

	return (
		<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr>
					<th className="column-1" scope="row" id="import-source-header">
						{ __( 'Import Source', 'tablepress' ) }:
					</th>
					<td className="column-2">
						{
							Object.entries( importSources ) .map( ( [ importSource, importSourceData ] ) => (
								<label
									key={ importSource }
									htmlFor={ `tables-import-source-${ importSource }` }
								>
									<input
										name="import[source]"
										id={ `tables-import-source-${ importSource }` }
										type="radio"
										aria-labelledby="import-source-header"
										value={ importSource }
										checked={ importSource === screenData.importSource }
										onChange={ ( event ) => updateScreenData( 'importSource', event.target.value ) }
									/> { importSourceData.label }
								</label>
							) )
						}
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1 top-align" scope="row">
						<label htmlFor={ `tables-import-${ screenData.importSource }` }>
							{ importSources[ screenData.importSource ].instruction }:
						</label>
					</th>
					<td className="column-2">
						{
							/*
							 * Always add the "File Upload" UI to the DOM, but hide it using `style="display: none;"` below.
							 * This ensures that the <input type="file"> field works, as that is "uncontrolled" in React, and setting its value (files) is not possible.
							 */
						}
						<div
							className="file-upload-area"
							style={ {
								display: ( 'file-upload' === screenData.importSource ) ? 'block' : 'none',
							} }
						>
							<input
								name="import_file_upload[]"
								id="tables-import-file-upload"
								type="file"
								multiple
								required={ 'file-upload' === screenData.importSource }
								onChange={ ( event ) => ( event.target.files && updateScreenData( 'importFileUpload', event.target.files ) ) }
								onDragEnter={ () => fileUploadDropzone.current.classList.add( 'dragover' ) }
								onDragLeave={ () => fileUploadDropzone.current.classList.remove( 'dragover' ) }
							/>
							<div
								ref={ fileUploadDropzone }
								className="dropzone"
							>
								<span>
									{ 0 === screenData.importFileUpload.length && __( 'Click to select files, or drag them here.', 'tablepress' ) }
									{ 0 < screenData.importFileUpload.length && sprintf( _n( 'You have selected %1$d file:', 'You have selected %1$d files:', screenData.importFileUpload.length, 'tablepress' ), screenData.importFileUpload.length ) }
								</span>
								{
									[ ...screenData.importFileUpload ].map( ( file ) =>
										<span key={ file.name }>{ file.name }</span>
									)
								}
							</div>
						</div>
						{ 'url' === screenData.importSource &&
							<input
								type="url"
								name="import[url]"
								id="tables-import-url"
								className="large-text code"
								required={ true }
								value={ screenData.importUrl }
								onChange={ ( event ) => updateScreenData( 'importUrl', event.target.value ) }
							/>
						}
						{ tp.import.showImportSourceServer && 'server' === screenData.importSource &&
							<input
								ref={ importServerInput }
								type="text"
								name="import[server]"
								id="tables-import-server"
								className="large-text code"
								required={ true }
								value={ screenData.importServer }
								onChange={ ( event ) => updateScreenData( 'importServer', event.target.value ) }
							/>
						}
						{ 'form-field' === screenData.importSource &&
							<textarea
								name="import[form-field]"
								id="tables-import-form-field"
								rows="15"
								cols="40"
								className="large-text code"
								required={ true }
								value={ screenData.importFormField }
								onChange={ ( event ) => updateScreenData( 'importFormField', event.target.value ) }
							/>
						}
						{ tp.import.zipSupportAvailable && 'form-field' !== screenData.importSource &&
							<span className="description">
								{ __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) }
							</span>
						}
					</td>
				</tr>
				<tr className="top-border">
					<th className="column-1" scope="row" id="import-type-header">
						{ __( 'Add, Replace, or Append?', 'tablepress' ) }:
					</th>
					<td className="column-2">
						<label htmlFor="tables-import-type-add">
							<input
								name="import[type]"
								id="tables-import-type-add"
								type="radio"
								aria-labelledby="import-type-header"
								value="add"
								checked={ 'add' === screenData.importType || 0 === tablesCount }
								onChange={ ( event ) => updateScreenData( 'importType', event.target.value ) }
							/> { __( 'Add as new table', 'tablepress' ) }
						</label>
						<label htmlFor="tables-import-type-replace">
							<input
								name="import[type]"
								id="tables-import-type-replace"
								type="radio"
								aria-labelledby="import-type-header"
								value="replace"
								disabled={ 0 === tablesCount }
								checked={ 'replace' === screenData.importType }
								onChange={ ( event ) => updateScreenData( 'importType', event.target.value ) }
							/> { __( 'Replace existing table', 'tablepress' ) }
						</label>
						<label htmlFor="tables-import-type-append">
							<input
								name="import[type]"
								id="tables-import-type-append"
								type="radio"
								aria-labelledby="import-type-header"
								value="append"
								disabled={ 0 === tablesCount }
								checked={ 'append' === screenData.importType }
								onChange={ ( event ) => updateScreenData( 'importType', event.target.value ) }
							/> { __( 'Append rows to existing table', 'tablepress' ) }
						</label>
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1" scope="row">
						<label htmlFor="tables-import-existing-table">
							{ __( 'Table to replace or append to', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<select
							ref={ appendReplaceDropdown }
							id="tables-import-existing-table"
							name="import[existing_table]"
							disabled={ appendReplaceDropdownDisabled }
							value={ screenData.importExistingTable }
						>
							<option value="">
								{
									' ' // Use a space as an empty string will be printed as `&nbsp;` by jSuites.
								}
							</option>
							{ tablesSelectOptions }
						</select>
					</td>
				</tr>
				<tr className="top-border">
					<td className="column-1"></td>
					<td className="column-2">
						<input
							type="hidden"
							name="import[legacy_import]"
							value={ tp.import.legacyImport }
						/>
						<input
							type="submit"
							value={ _x( 'Import', 'button', 'tablepress' ) }
							className="button button-primary button-large"
							id="import-submit-button"
							onClick={ () => {
								// Show validation :invalid CSS pseudo-selector highlighting.
								updateScreenData( 'validationHighlighting', true );

								// When importing from the server, the value must have been changed from the default (normally ABSPATH).
								if ( 'server' === screenData.importSource && tp.import.importServer === screenData.importServer ) {
									importServerInput.current.setCustomValidity( __( 'You must specify a path to a file on the server.', 'tablepress' ) );
								}

								// If the table selection dropdown for replace or append is enabled, a table must be selected.
								if ( ! appendReplaceDropdownDisabled && '' === screenData.importExistingTable ) {
									// Use the jSuites dropdown input field, as the actual <select> is hidden.
									appendReplaceDropdown.current.previousElementSibling.querySelector( '.jdropdown-header' ).setCustomValidity( __( 'You must select a table.', 'tablepress' ) );
								}
							} }
						/>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default ImportScreen;
