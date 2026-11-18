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
	SelectControl as LegacySelectControl,
} from '@wordpress/components';
import { info } from '@wordpress/icons';
import { __, _x, sprintf } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Icon,
	SelectControl,
	Stack,
	Text,
} from '@wordpress/ui';

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
	 * `LegacySelectControl` does not support the HTML `size` attribute, so that this has to be done manually with an effect.
	 */
	useEffect( () => {
		tablesExportListSelect.current.size = exportTablesSelectMultiple ? exportTablesSelectSize : 1;
	}, [] );

	return (
		<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr>
					<th className="column-1 top-align" scope="row">
						<Stack direction="column" gap="xl">
							<label htmlFor="tables-export-list">
								{ __( 'Tables to Export', 'tablepress' ) }:
							</label>
							{ exportTablesSelectMultiple &&
								<Stack direction="column" gap="sm">
									<CheckboxControl
										// Show a "Select all" checkbox to select all entries in the export tables dropdown.
										label={ __( 'Select all', 'tablepress' ) }
										checked={ screenData.selectedTables.length === tablesCount }
										onCheckedChange={ () => {
											const selectedTables = ( screenData.selectedTables.length === tablesCount ) ? [] : Object.keys( tp.export.tables );
											updateScreenData( { selectedTables } );
										} }
									/>
									{ tablesCount > maxExportTablesSelectSize &&
										<CheckboxControl
											// Show a "Reverse List" checkbox if more tables are shown than what the height of the export tables dropdown holds.
											label={ __( 'Reverse list', 'tablepress' ) }
											checked={ screenData.reverseList }
											onCheckedChange={ ( reverseList ) => {
												updateScreenData( { reverseList } );
												tablesSelectOptions.reverse();
											} }
										/>
									}
								</Stack>
							}
						</Stack>
					</th>
					<td className="column-2">
						<Stack direction="column" gap="xs">
							<LegacySelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								ref={ tablesExportListSelect }
								id="tables-export-list"
								// size={ exportTablesSelectMultiple ? exportTablesSelectSize : 1 } // Not supported by `LegacySelectControl`, so done with an effect above.
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
								<Stack direction="row" gap="xs" align="center">
									<Icon icon={ info } style={ { flexShrink: 0 } } />
									<Text>
										{ sprintf(
											__( 'You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.', 'tablepress' ),
											window?.navigator?.platform?.includes( 'Mac' ) ? _x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) : _x( 'Ctrl', 'keyboard key', 'tablepress' ),
											_x( 'Shift', 'keyboard key', 'tablepress' ) )
										}
									</Text>
								</Stack>
							}
						</Stack>
					</td>
				</tr>
				<tr>
					<th className="column-1" scope="row">
						<label htmlFor="tables-export-format">
							{ __( 'Export Format', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<Stack>
							<SelectControl
								id="tables-export-format"
								name="export[format]"
								label={ __( 'Export Format', 'tablepress' ) }
								hideLabelFromVision={ true }
								value={ exportFormatsSelectOptions.find( ( item ) => item.value === screenData.exportFormat ) }
								onValueChange={ ( selectedItem ) => updateScreenData( { exportFormat: selectedItem.value } ) }
								items={ exportFormatsSelectOptions }
							/>
						</Stack>
					</td>
				</tr>
				<tr>
					<th className="column-1" scope="row">
						<label htmlFor="tables-export-csv-delimiter">
							{ __( 'CSV Delimiter', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<Stack direction="row" gap="sm" align="center">
							<SelectControl
								id="tables-export-csv-delimiter"
								name="export[csv_delimiter]"
								label={ __( 'CSV Delimiter', 'tablepress' ) }
								hideLabelFromVision={ true }
								value={ csvDelimitersSelectOptions.find( ( item ) => item.value === screenData.csvDelimiter ) }
								onValueChange={ ( selectedItem ) => updateScreenData( { csvDelimiter: selectedItem.value } ) }
								items={ csvDelimitersSelectOptions }
								disabled={ 'csv' !== screenData.exportFormat }
							/>
							{ 'csv' !== screenData.exportFormat &&
								<Text variant="body-sm">
									{ __( '(Only needed for CSV export.)', 'tablepress' ) }
								</Text>
							}
						</Stack>
					</td>
				</tr>
				<tr className="bottom-border">
					<th className="column-1" scope="row">
						{ __( 'ZIP file', 'tablepress' ) }:
					</th>
					<td className="column-2">
						{ tp.export.zipSupportAvailable &&
							<Stack direction="row" gap="xs" align="center">
								<CheckboxControl
									label={ __( 'Create a ZIP archive.', 'tablepress' ) }
									checked={ screenData.createZipFile || zipFileRequired }
									disabled={ zipFileRequired }
									onCheckedChange={ ( createZipFile ) => updateScreenData( { createZipFile } ) }
								/>
								{ zipFileRequired &&
									<Text variant="body-sm">
										{ __( '(Mandatory if more than one table is selected.)', 'tablepress' ) }
									</Text>
								}
							</Stack>
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
							type="submit"
							disabled={ 0 === screenData.selectedTables.length }
						>
							{ __( 'Download Export File', 'tablepress' ) }
						</Button>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default Screen;
