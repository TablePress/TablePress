/**
 * JavaScript code for the "Export Screen" component.
 *
 * @package TablePress
 * @subpackage Export Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef, useState } from 'react';
import {
	Button,
	CheckboxControl,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	SelectControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { info } from '@wordpress/icons';
import { __, _x, sprintf } from '@wordpress/i18n';

// Number of tables.
const tablesCount = Object.keys( tp.export.tables ).length;

// Show at least one empty row in the select, and between 3 and 12 total rows.
let exportTablesSelectSize = tablesCount + 1;
const maxExportTablesSelectSize = 12;
exportTablesSelectSize = Math.max( exportTablesSelectSize, 3 );
exportTablesSelectSize = Math.min( exportTablesSelectSize, maxExportTablesSelectSize );
const exportTablesSelectMultiple = tp.export.zipSupportAvailable;

const tablesSelectOptions = Object.entries( tp.export.tables ).map( ( [ tableId, tableName ] ) => {
	if ( '' === tableName.trim() ) {
		tableName = __( '(no name)', 'tablepress' );
	}
	const optionText = sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), tableId, tableName );
	return { value: tableId, label: optionText };
} );
if ( ! exportTablesSelectMultiple ) {
	tablesSelectOptions.unshift( { value: '', label: __( '— Select —', 'tablepress' ), disabled: true } );
}

const exportFormatsSelectOptions = Object.entries( tp.export.exportFormats ).map( ( [ exportFormat, exportFormatName ] ) => ( { value: exportFormat, label: exportFormatName } ) );
const csvDelimitersSelectOptions = Object.entries( tp.export.csvDelimiters ).map( ( [ csvDelimiter, csvDelimiterName ] ) => ( { value: csvDelimiter, label: csvDelimiterName } ) );

/**
 * Returns the "Export Screen" component's JSX markup.
 *
 * @return {Object} Export Screen component.
 */
const Screen = () => {
	const tablesExportListSelect = useRef( null );
	const [ screenData, setScreenData ] = useState( {
		selectedTables: tp.export.selectedTables,
		exportFormat: tp.export.exportFormat,
		csvDelimiter: tp.export.csvDelimiter,
		createZipFile: false,
		reverseList: false,
	} );

	// If more than one table is selected, force the ZIP file checkbox to checked.
	const zipFileRequired = screenData.selectedTables.length > 1;

	/**
	 * Handles screen data state changes.
	 *
	 * @param {Object} updatedScreenData Data in the screen data state that should be updated.
	 */
	const updateScreenData = ( updatedScreenData ) => {
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			...updatedScreenData,
		} ) );
	};

	/*
	 * Set the size of the export tables dropdown to the number of tables, if ZIP file support is available.
	 * `SelectControl` does not support the HTML `size` attribute, so that this has to be done manually with an effect.
	 */
	useEffect( () => {
		tablesExportListSelect.current.size = exportTablesSelectMultiple ? exportTablesSelectSize : 1;
	}, [] );

	return (
		<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr>
					<th className="column-1 top-align" scope="row">
						<VStack
							spacing="20px"
						>
							<label htmlFor="tables-export-list">
								{ __( 'Tables to Export', 'tablepress' ) }:
							</label>
							{ exportTablesSelectMultiple &&
								<VStack>
									<CheckboxControl
										// Show a "Select all" checkbox to select all entries in the export tables dropdown.
										__nextHasNoMarginBottom
										label={ __( 'Select all', 'tablepress' ) }
										checked={ screenData.selectedTables.length === tablesCount }
										onChange={ () => {
											const selectedTables = ( screenData.selectedTables.length === tablesCount ) ? [] : Object.keys( tp.export.tables );
											updateScreenData( { selectedTables } );
										} }
									/>
									{ tablesCount > maxExportTablesSelectSize &&
										<CheckboxControl
											// Show a "Reverse List" checkbox if more tables are shown than what the height of the export tables dropdown holds.
											__nextHasNoMarginBottom
											label={ __( 'Reverse list', 'tablepress' ) }
											checked={ screenData.reverseList }
											onChange={ ( reverseList ) => {
												updateScreenData( { reverseList } );
												tablesSelectOptions.reverse();
											} }
										/>
									}
								</VStack>
							}
						</VStack>
					</th>
					<td className="column-2">
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							ref={ tablesExportListSelect }
							id="tables-export-list"
							// size={ exportTablesSelectMultiple ? exportTablesSelectSize : 1 } // Not supported by `SelectControl`, so done with an effect above.
							multiple={ exportTablesSelectMultiple }
							value={ exportTablesSelectMultiple ? screenData.selectedTables : ( screenData.selectedTables[0] ?? '' ) }
							onChange={ ( selectedTables ) => {
								if ( 'string' === typeof selectedTables ) {
									selectedTables = [ selectedTables ];
								}
								updateScreenData( { selectedTables } );
							} }
							options={ tablesSelectOptions }
						/>
						{ exportTablesSelectMultiple &&
							<HStack
								alignment="left"
							>
								<Icon icon={ info } />
								<span>
									{ sprintf(
										__( 'You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.', 'tablepress' ),
										window?.navigator?.platform?.includes( 'Mac' ) ? _x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) : _x( 'Ctrl', 'keyboard key', 'tablepress' ),
										_x( 'Shift', 'keyboard key', 'tablepress' ) )
									}
								</span>
							</HStack>
						}
					</td>
				</tr>
				<tr>
					<th className="column-1" scope="row">
						<label htmlFor="tables-export-format">
							{ __( 'Export Format', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<HStack>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								id="tables-export-format"
								name="export[format]"
								value={ screenData.exportFormat }
								label={ __( 'Export Format', 'tablepress' ) }
								hideLabelFromVision={ true }
								onChange={ ( exportFormat ) => updateScreenData( { exportFormat } ) }
								options={ exportFormatsSelectOptions }
							/>
						</HStack>
					</td>
				</tr>
				<tr>
					<th className="column-1" scope="row">
						<label htmlFor="tables-export-csv-delimiter">
							{ __( 'CSV Delimiter', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<HStack
							alignment="left"
						>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								id="tables-export-csv-delimiter"
								name="export[csv_delimiter]"
								value={ screenData.csvDelimiter }
								label={ __( 'CSV Delimiter', 'tablepress' ) }
								hideLabelFromVision={ true }
								onChange={ ( csvDelimiter ) => updateScreenData( { csvDelimiter } ) }
								options={ csvDelimitersSelectOptions }
								disabled={ 'csv' !== screenData.exportFormat }
							/>
							{ 'csv' !== screenData.exportFormat &&
								<span>
									{ __( '(Only needed for CSV export.)', 'tablepress' ) }
								</span>
							}
						</HStack>
					</td>
				</tr>
				<tr className="bottom-border">
					<th className="column-1" scope="row">
						{ __( 'ZIP file', 'tablepress' ) }:
					</th>
					<td className="column-2">
						{ tp.export.zipSupportAvailable &&
							<HStack
								alignment="left"
							>
								<CheckboxControl
									__nextHasNoMarginBottom
									label={ __( 'Create a ZIP archive.', 'tablepress' ) }
									checked={ screenData.createZipFile || zipFileRequired }
									disabled={ zipFileRequired }
									onChange={ ( createZipFile ) => updateScreenData( { createZipFile } ) }
								/>
								{ zipFileRequired &&
									<span>
										{ __( '(Mandatory if more than one table is selected.)', 'tablepress' ) }
									</span>
								}
							</HStack>
						}
						{ ! tp.export.zipSupportAvailable &&
							__( 'Note: Support for ZIP file creation seems not to be available on this server.', 'tablepress' )
						}
					</td>
				</tr>
				<tr className="top-border">
					<td className="column-1"></td>
					<td className="column-2">
						<input
							// Send the list of tables to be exported as a string and not an array, to reduce potential issues with large arrays.
							type="hidden"
							name="export[tables_list]"
							value={ screenData.selectedTables.join() }
						/>
						<input
							// Send the ZIP file attribute as a hidden field, as disabled checkboxes are not sent in HTTP POST requests.
							type="hidden"
							name="export[zip_file]"
							value={ screenData.createZipFile || zipFileRequired }
						/>
						<Button
							variant="primary"
							type="submit"
							disabled={ 0 === screenData.selectedTables.length }
							text={ __( 'Download Export File', 'tablepress' ) }
						/>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default Screen;
