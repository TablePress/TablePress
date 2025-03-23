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
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	RadioControl,
	ComboboxControl,
	Disabled,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { info } from '@wordpress/icons';
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
if ( ! tp.import.showImportSourceUrl ) {
	delete importSources.url;
}
if ( ! tp.import.showImportSourceServer ) {
	delete importSources.server;
}

const importSourcesRadioOptions = Object.entries( importSources ).map( ( [ importSource, importSourceData ] ) => ( { value: importSource, label: importSourceData.label } ) );

// Number of tables.
const tablesCount = Object.keys( tp.import.tables ).length;

const tablesSelectOptions = Object.entries( tp.import.tables ).map( ( [ tableId, tableName ] ) => {
	if ( '' === tableName.trim() ) {
		tableName = __( '(no name)', 'tablepress' );
	}
	const optionText = sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), tableId, tableName );
	return { value: tableId, label: optionText };
} );

// Custom component to conditionally disable its children, used for the ComboboxControl.
const ConditionalDisabled = ( { condition, children } ) => (
	condition ? ( <Disabled>{ children }</Disabled> ) : children
);

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

	// Update the validation highlighting (using APIs and DOM elements outside of the React components) when the state changes.
	useEffect( () => {
		document.getElementById( 'tablepress_import-import-form' ).classList.toggle( 'no-validation-highlighting', ! screenData.validationHighlighting );
		if ( ! screenData.validationHighlighting ) {
			importServerInput.current?.setCustomValidity( '' );
			// We need to use this dynamically generated ID by the ComboboxControl component. It does not (yet?) support a static ID or a ref.
			document.getElementById( 'components-form-token-input-combobox-control-1' )?.setCustomValidity( '' );
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
						<RadioControl
							name="import[source]"
							label={ __( 'Import Source', 'tablepress' ) }
							hideLabelFromVision={ true }
							selected={ screenData.importSource }
							onChange={ ( importSource ) => updateScreenData( { importSource } ) }
							options={ importSourcesRadioOptions }
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
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								type="url"
								name="import[url]"
								id="tables-import-url"
								className="code"
								required={ true }
								value={ screenData.importUrl }
								onChange={ ( importUrl ) => updateScreenData( { importUrl } ) }
							/>
						}
						{ tp.import.showImportSourceServer && 'server' === screenData.importSource &&
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								ref={ importServerInput }
								name="import[server]"
								id="tables-import-server"
								className="code"
								required={ true }
								value={ screenData.importServer }
								onChange={ ( importServer ) => updateScreenData( { importServer } ) }
							/>
						}
						{ 'form-field' === screenData.importSource &&
							<TextareaControl
								__nextHasNoMarginBottom
								name="import[form-field]"
								id="tables-import-form-field"
								rows="15"
								cols="40"
								className="code"
								required={ true }
								value={ screenData.importFormField }
								onChange={ ( importFormField ) => updateScreenData( { importFormField } ) }
							/>
						}
						{ 'form-field' !== screenData.importSource &&
							<HStack
								alignment="left"
							>
								<Icon icon={ info } />
								<span>
									{ __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) }
								</span>
							</HStack>
						}
					</td>
				</tr>
				<tr className="top-border">
					<th className="column-1" scope="row">
						{ __( 'Add, Replace, or Append?', 'tablepress' ) }:
					</th>
					<td className="column-2">
						<RadioControl
							name="import[type]"
							label={ __( 'Import Type', 'tablepress' ) }
							hideLabelFromVision={ true }
							selected={ 0 === tablesCount ? 'add' : screenData.importType } // Always select "Add" if there are no tables.
							onChange={ ( importType ) => updateScreenData( { importType } ) }
							options={ [
								{ value: 'add', label: __( 'Add as new table', 'tablepress' ) },
								{ value: 'replace', label: __( 'Replace existing table', 'tablepress' ), disabled: 0 === tablesCount },
								{ value: 'append', label: __( 'Append rows to existing table', 'tablepress' ), disabled: 0 === tablesCount },
							] }
						/>
					</td>
				</tr>
				<tr className="top-border bottom-border">
					<th className="column-1 top-align" scope="row">
						<label htmlFor="tables-import-existing-table">{ __( 'Table to replace or append to', 'tablepress' ) }:</label>
					</th>
					<td className="column-2">
						<ConditionalDisabled
							condition={ appendReplaceDropdownDisabled }
						>
							<ComboboxControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								id="tables-import-existing-table"
								label={ __( 'Table to replace or append to', 'tablepress' ) }
								hideLabelFromVision={ true }
								placeholder={ __( '— Select or type —', 'tablepress' ) }
								value={ screenData.importExistingTable }
								options={ tablesSelectOptions }
								onChange={ ( importExistingTable ) => updateScreenData( { importExistingTable } ) }
							/>
						</ConditionalDisabled>
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
						<input
							// Send the Table to be replaced/appended to the server, if a table was selected. The ComboboxControl is not an actual form element with a name.
							type="hidden"
							name="import[existing_table]"
							value={ screenData.importExistingTable ?? '' }
						/>
						<Button
							variant="primary"
							type="submit"
							text={ _x( 'Import', 'button', 'tablepress' ) }
							onClick={ () => {
								// Show validation :invalid CSS pseudo-selector highlighting.
								updateScreenData( { validationHighlighting: true } );

								// When importing from the server, the value must have been changed from the default (normally ABSPATH).
								if ( 'server' === screenData.importSource && tp.import.importServer === screenData.importServer ) {
									importServerInput.current.setCustomValidity( __( 'You must specify a path to a file on the server.', 'tablepress' ) );
								}

								// If the table selection dropdown for replace or append is enabled, a table must be selected.
								if ( ! appendReplaceDropdownDisabled && ! screenData.importExistingTable ) {
									// We need to use this dynamically generated ID by the ComboboxControl component. It does not (yet?) support a static ID or a ref.
									document.getElementById( 'components-form-token-input-combobox-control-1' )?.setCustomValidity( __( 'You must select a table.', 'tablepress' ) );
								}
							} }
						/>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default Screen;
