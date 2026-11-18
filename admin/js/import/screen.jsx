/**
 * JavaScript code for the "Import Screen" component.
 *
 * @package TablePress
 * @subpackage Import Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef, useState } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { info } from '@wordpress/icons';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import {
	Button,
	Icon,
	InputControl,
	RadioGroupControl,
	SearchableSelectControl,
	Stack,
	Text,
	TextareaControl,
} from '@wordpress/ui';

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
if ( ! tp.import.showImportSourceUrl ) {
	delete importSources.url;
}
if ( ! tp.import.showImportSourceServer ) {
	delete importSources.server;
}

const importSourcesRadioOptions = Object.entries( importSources ).map( ( [ importSource, importSourceData ] ) => ( { value: importSource, label: importSourceData.label } ) );

// Number of tables.
const tablesCount = Object.keys( tp.import.tables ).length;

// Create an array of objects for the table selection dropdown, in the form [ { value: <id>, label: <text> }, ... ].
const tablesSelectOptions = Object.entries( tp.import.tables ).map( ( [ value, label ] ) => ( { value, label } ) );

/**
 * Returns the "Import Screen" component's JSX markup.
 *
 * @return {Object} Import Screen component.
 */
const Screen = () => {
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
	 * @param {Object} updatedScreenData Data in the screen data state that should be updated.
	 */
	const updateScreenData = ( updatedScreenData ) => {
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			validationHighlighting: false, // Reset with every UI state change.
			...updatedScreenData,
		} ) );
	};

	// References to DOM elements.
	const importServerInput = useRef( null );
	const fileUploadDropzone = useRef( null );
	const existingTableSelect = useRef( null );

	// Update the validation highlighting (using APIs and DOM elements outside of the React components) when the state changes.
	useEffect( () => {
		document.getElementById( 'tablepress_import-import-form' ).classList.toggle( 'no-validation-highlighting', ! screenData.validationHighlighting );
		if ( ! screenData.validationHighlighting ) {
			importServerInput.current?.setCustomValidity( '' );
			existingTableSelect.current?.parentElement?.parentElement?.querySelector( 'input' )?.setCustomValidity( '' );
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

	return (
		<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr>
					<th className="column-1" scope="row">
						{ __( 'Import Source', 'tablepress' ) }:
					</th>
					<td className="column-2">
						<RadioGroupControl
							name="import[source]"
							label={ __( 'Import Source', 'tablepress' ) }
							hideLabelFromVision={ true }
							className="tablepress-horizontal-radio-group" // To show the radio buttons horizontally, with CSS.
							value={ screenData.importSource }
							onValueChange={ ( importSource ) => updateScreenData( { importSource } ) }
							items={ importSourcesRadioOptions }
						/>
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1 top-align" scope="row">
						<label htmlFor={ `tables-import-${ screenData.importSource }` }>
							{ importSources[ screenData.importSource ].instruction }:
						</label>
					</th>
					<td className="column-2">
						<Stack direction="column" gap="xs">
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
									onChange={ ( event ) => ( event.target.files && updateScreenData( { importFileUpload: event.target.files } ) ) }
									onDragEnter={ () => fileUploadDropzone.current.classList.add( 'dragover' ) }
									onDragLeave={ () => fileUploadDropzone.current.classList.remove( 'dragover' ) }
								/>
								<div
									ref={ fileUploadDropzone }
									id="tables-import-file-upload-dropzone"
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
							{ tp.import.showImportSourceUrl && 'url' === screenData.importSource &&
								<InputControl
									type="url"
									label={ __( 'URL', 'tablepress' ) }
									hideLabelFromVision={ true }
									name="import[url]"
									id="tables-import-url"
									className="code"
									required={ true }
									value={ screenData.importUrl }
									onValueChange={ ( importUrl ) => updateScreenData( { importUrl } ) }
								/>
							}
							{ tp.import.showImportSourceServer && 'server' === screenData.importSource &&
								<InputControl
									ref={ importServerInput }
									label={ __( 'File on server', 'tablepress' ) }
									hideLabelFromVision={ true }
									name="import[server]"
									id="tables-import-server"
									className="code"
									required={ true }
									value={ screenData.importServer }
									onValueChange={ ( importServer ) => updateScreenData( { importServer } ) }
								/>
							}
							{ 'form-field' === screenData.importSource &&
								<TextareaControl
									name="import[form-field]"
									id="tables-import-form-field"
									label={ __( 'Manual Input', 'tablepress' ) }
									hideLabelFromVision={ true }
									rows="15"
									cols="40"
									className="code"
									required={ true }
									value={ screenData.importFormField }
									onValueChange={ ( importFormField ) => updateScreenData( { importFormField } ) }
								/>
							}
							{ 'form-field' !== screenData.importSource &&
								<Stack direction="row" gap="xs" align="center">
									<Icon icon={ info } style={ { flexShrink: 0 } } />
									<Text>
										{ __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) }
									</Text>
								</Stack>
							}
						</Stack>
					</td>
				</tr>
				<tr className="top-border">
					<th className="column-1" scope="row">
						{ __( 'Add, Replace, or Append?', 'tablepress' ) }:
					</th>
					<td className="column-2">
						<RadioGroupControl
							name="import[type]"
							label={ __( 'Import Type', 'tablepress' ) }
							hideLabelFromVision={ true }
							className="tablepress-horizontal-radio-group" // To show the radio buttons horizontally, with CSS.
							value={ 0 === tablesCount ? 'add' : screenData.importType } // Always select "Add" if there are no tables.
							onValueChange={ ( importType ) => updateScreenData( { importType } ) }
							items={ [
								{ value: 'add', label: __( 'Add as new table', 'tablepress' ) },
								{ value: 'replace', label: __( 'Replace existing table', 'tablepress' ), disabled: 0 === tablesCount },
								{ value: 'append', label: __( 'Append rows to existing table', 'tablepress' ), disabled: 0 === tablesCount },
							] }
						/>
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1" scope="row">
						<label htmlFor="tables-import-existing-table">{ __( 'Table to replace or append to', 'tablepress' ) }:</label>
					</th>
					<td className="column-2">
						<SearchableSelectControl
							ref={ existingTableSelect }
							id="tables-import-existing-table"
							name="import[existing_table]"
							className="tables-import-existing-table-select"
							label={ __( 'Table to replace or append to', 'tablepress' ) }
							hideLabelFromVision={ true }
							disabled={ appendReplaceDropdownDisabled }
							placeholder={ __( 'Select table', 'tablepress' ) }
							searchPlaceholder={ __( 'Search table', 'tablepress' ) }
							value={ screenData.importExistingTable }
							items={ tablesSelectOptions }
							onValueChange={ ( importExistingTable ) => updateScreenData( { importExistingTable } ) }
							autoHighlight={ true }
							required={ ! appendReplaceDropdownDisabled }
							itemToStringLabel={ ( item ) => sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), item.value, item.label ) } // This function's return value is used for the component (trigger) label and for the search functionality.
							emptyContent={ __( 'No tables found.', 'tablepress' ) }
						>
							{
								( item ) => (
									<SearchableSelectControl.Item key={ item.value } value={ item }>
										<SearchableSelectControl.ItemLabel>
											{
												createInterpolateElement(
													/* translators: %1$s: Table ID, %2$s: Table name */
													sprintf( __( 'ID <strong>%1$s</strong>: %2$s', 'tablepress' ), item.value, item.label ),
													{
														strong: <strong />,
													},
												)
											}
										</SearchableSelectControl.ItemLabel>
									</SearchableSelectControl.Item>
								)
							}
						</SearchableSelectControl>
					</td>
				</tr>
				<tr className="top-border">
					<td className="column-1"></td>
					<td className="column-2">
						<input
							// Send the legacy import flag to the server, so that it can handle the import accordingly.
							type="hidden"
							name="import[legacy_import]"
							value={ tp.import.legacyImport }
						/>
						<Button
							type="submit"
							onClick={ () => {
								// Show validation :invalid CSS pseudo-selector highlighting.
								updateScreenData( { validationHighlighting: true } );

								// When importing from the server, the value must have been changed from the default (normally ABSPATH).
								if ( 'server' === screenData.importSource && tp.import.importServer === screenData.importServer ) {
									importServerInput.current?.setCustomValidity( __( 'You must specify a path to a file on the server.', 'tablepress' ) );
								}

								// If the table selection dropdown for replace or append is enabled, a table must be selected.
								if ( ! appendReplaceDropdownDisabled && ! screenData.importExistingTable ) {
									existingTableSelect.current?.parentElement?.parentElement?.querySelector( 'input' )?.setCustomValidity( __( 'You must select a table.', 'tablepress' ) );
								}
							} }
						>
							{ _x( 'Import', 'button', 'tablepress' ) }
						</Button>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default Screen;
