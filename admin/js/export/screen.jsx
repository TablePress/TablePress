/**
 * JavaScript code for the "Export Screen" component.
 *
 * @package TablePress
 * @subpackage Export Screen
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/* globals tp */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Icon,
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

// The <option> entries for the dropdowns do not depend on the state, so they can be created once.
const tablesSelectOptions = Object.entries( tp.export.tables ).map( ( [ tableId, tableName ] ) => {
	if ( '' === tableName.trim() ) {
		tableName = __( '(no name)', 'tablepress' );
	}
	const optionText = sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), tableId, tableName );
	return <option key={ tableId } value={ tableId }>{ optionText }</option>;
} );
const exportFormatsSelectOptions = Object.entries( tp.export.exportFormats ).map( ( [ exportFormat, exportFormatName ] ) =>
	<option key={ exportFormat } value={ exportFormat }>{ exportFormatName }</option>
);
const csvDelimitersSelectOptions = Object.entries( tp.export.csvDelimiters ).map( ( [ csvDelimiter, csvDelimiterName ] ) =>
	<option key={ csvDelimiter } value={ csvDelimiter }>{ csvDelimiterName }</option>
);

/**
 * Returns the "Export Screen" component's JSX markup.
 *
 * @return {Object} Export Screen component.
 */
const Screen = () => {
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
	 * @param {Object} updatedData Data in the screen data state that should be updated.
	 */
	const updateScreenData = ( updatedData ) => {
		const newScreenData = {
			...screenData,
			...updatedData,
		};
		setScreenData( newScreenData );
	};

	return (
		<table className="tablepress-postbox-table fixed">
			<tbody>
				<tr>
					<th className="column-1 top-align" scope="row">
						<label htmlFor="tables-export-list">
							{ __( 'Tables to Export', 'tablepress' ) }:
						</label>
						{ tp.export.zipSupportAvailable &&
							<>
								{
									// Show a "Select all" checkbox to select all entries in the export tables dropdown.
								}
								<br /><br />
								<label htmlFor="tables-export-select-all">
									<input
										type="checkbox"
										id="tables-export-select-all"
										checked={ screenData.selectedTables.length === tablesCount }
										onChange={ () => {
											const selectedTables = ( screenData.selectedTables.length === tablesCount ) ? [] : Object.keys( tp.export.tables );
											updateScreenData( { selectedTables } );
										} }
									/> { __( 'Select all', 'tablepress' ) }
								</label>
								{ tablesCount > maxExportTablesSelectSize &&
									<>
										{
											// Show a "Reverse List" checkbox if more tables are shown than what the height of the export tables dropdown holds.
										}
										<br /><br />
										<label htmlFor="tables-export-reverse-list">
											<input
												id="tables-export-reverse-list"
												type="checkbox"
												checked={ screenData.reverseList }
												onChange={ () => {
													updateScreenData( { reverseList: ! screenData.reverseList } );
													tablesSelectOptions.reverse();
												} }
											/> { __( 'Reverse list', 'tablepress' ) }
										</label>
									</>
								}
							</>
						}
					</th>
					<td className="column-2">
						<select
							id="tables-export-list"
							size={ tp.export.zipSupportAvailable ? exportTablesSelectSize : 1 }
							multiple={ tp.export.zipSupportAvailable }
							value={ screenData.selectedTables }
							onChange={ ( event ) => {
								const selectedTables = [ ...event.target.selectedOptions ].map( ( option ) => option.value );
								updateScreenData( { selectedTables } );
							} }
							style={ {
								width: '100%',
							} }
						>
							{ tablesSelectOptions }
						</select>
						{ tp.export.zipSupportAvailable &&
							<>
								<br />
								<p className="info-text">
									<Icon icon={ info } />
									<span>
										{ sprintf(
											__( 'You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.', 'tablepress' ),
											window?.navigator?.platform?.includes( 'Mac' ) ? _x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) : _x( 'Ctrl', 'keyboard key', 'tablepress' ),
											_x( 'Shift', 'keyboard key', 'tablepress' ) )
										}
									</span>
								</p>
							</>
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
						<select
							id="tables-export-format"
							name="export[format]"
							value={ screenData.exportFormat }
							onChange={ ( event ) => updateScreenData( { exportFormat: event.target.value } ) }
						>
							{ exportFormatsSelectOptions }
						</select>
					</td>
				</tr>
				<tr>
					<th className="column-1" scope="row">
						<label htmlFor="tables-export-csv-delimiter">
							{ __( 'CSV Delimiter', 'tablepress' ) }:
						</label>
					</th>
					<td className="column-2">
						<select
							id="tables-export-csv-delimiter"
							name="export[csv_delimiter]"
							disabled={ 'csv' !== screenData.exportFormat }
							value={ screenData.csvDelimiter }
							onChange={ ( event ) => updateScreenData( { csvDelimiter: event.target.value } ) }
						>
							{ csvDelimitersSelectOptions }
						</select>
						{ 'csv' !== screenData.exportFormat &&
							<>
								{ ' ' }
								<span className="description">
									{ __( '(Only needed for CSV export.)', 'tablepress' ) }
								</span>
							</>
						}
					</td>
				</tr>
				<tr className="bottom-border">
					<th className="column-1" scope="row">
						{ __( 'ZIP file', 'tablepress' ) }:
					</th>
					<td className="column-2">
						{ tp.export.zipSupportAvailable &&
							<label htmlFor="tables-export-zip-file">
								<input
									type="checkbox"
									id="tables-export-zip-file"
									checked={ screenData.createZipFile || zipFileRequired }
									disabled={ zipFileRequired }
									onChange={ () => updateScreenData( { createZipFile: ! screenData.createZipFile } ) }
								/> { __( 'Create a ZIP archive.', 'tablepress' ) }
								{ zipFileRequired &&
									<>
										{ ' ' }
										<span className="description">
											{ __( '(Mandatory if more than one table is selected.)', 'tablepress' ) }
										</span>
									</>
								}
							</label>
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
							type="hidden"
							name="export[tables_list]"
							value={ screenData.selectedTables.join() }
						/>
						<input
							type="hidden"
							name="export[zip_file]"
							value={ screenData.createZipFile || zipFileRequired }
						/>
						<input
							type="submit"
							value={ __( 'Download Export File', 'tablepress' ) }
							className="button button-primary button-large"
							disabled={ 0 === screenData.selectedTables.length }
						/>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default Screen;
