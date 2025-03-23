/**
 * JavaScript code for the "Edit" section integration of the "Table Features for Site Visitors".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	CheckboxControl,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextareaControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { Alert } from '../common/alert';

// Wrap the "Custom Commands" in a separate component, to keep internal states at a lower level.
const CustomCommandsControl = ( { tableOptionCustomCommands, dataTablesEnabled, updateTableOptions } ) => {
	const [ customCommands, setCustomCommands ] = useState( tableOptionCustomCommands );
	const [ customCommandsExpanded, setCustomCommandsExpanded ] = useState( false );

	/*
	// Updating the state variable when the table option changes is not necessary, as it's never modified externally.
	useEffect( () => {
		setCustomCommands( tableOptionCustomCommands );
	}, [ tableOptionCustomCommands ] );
	*/

	return (
		<TextareaControl
			__nextHasNoMarginBottom
			id="option-datatables_custom_commands"
			rows={
				customCommandsExpanded && '' !== customCommands
					? Math.min( 15, customCommands.split( '\n' ).length )
					: 1
			}
			className="code"
			value={ customCommands }
			disabled={ ! dataTablesEnabled }
			onChange={ ( newCustomCommands ) => setCustomCommands( newCustomCommands ) }
			onBlur={ () => {
				if ( customCommands !== tableOptionCustomCommands ) {
					updateTableOptions( { datatables_custom_commands: customCommands } );
				}
			} }
			onFocus={ () => setCustomCommandsExpanded( true ) }
			help={
				createInterpolateElement(
					__( 'Additional parameters from the <a>DataTables documentation</a> to be added to the JS call.', 'tablepress' ) + ' ' + __( 'For advanced use only.', 'tablepress' ),
					{
						a: <a href="https://datatables.net/" />, // eslint-disable-line jsx-a11y/anchor-has-content
					},
				)
			}
		/>
	);
};

const Section = ( { tableOptions, updateTableOptions } ) => {
	const [ alertEnableDataTablesMergedCellsIsShown, setAlertEnableDataTablesMergedCellsIsShown ] = useState( false );

	const tableHeadEnabled = ( tableOptions.table_head > 0 );
	const dataTablesEnabled = ( tableHeadEnabled && tableOptions.use_datatables );

	return (
		<VStack
			spacing="16px"
			style={ {
				paddingTop: '6px',
			} }
		>
			{
				( ! tableHeadEnabled ) && (
					<span>
						<em>
							{
								sprintf(
									__( 'These features and options are only available when the “%1$s” setting in the “%2$s” section is used.', 'tablepress' ),
									__( 'Table Header', 'tablepress' ),
									__( 'Table Options', 'tablepress' ),
								)
							}
						</em>
					</span>
				)
			}
			<table className="tablepress-postbox-table fixed">
				<tbody>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Enable Visitor Features', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Offer the following functions for site visitors with this table:', 'tablepress' ) }
								checked={ tableOptions.use_datatables }
								disabled={ ! tableHeadEnabled }
								onChange={ ( use_datatables ) => {
									// Don't turn on "Enable Visitor Features" if the table has merged cells.
									if ( use_datatables ) {
										tp.helpers.visibility.update(); // Update information about hidden rows and columns.
										if ( tp.helpers.editor.has_merged_body_cells() ) {
											setAlertEnableDataTablesMergedCellsIsShown( true );
											return;
										}
									}
									updateTableOptions( { use_datatables } );
								} }
							/>
							{ alertEnableDataTablesMergedCellsIsShown && (
								<Alert
									title={ __( 'Enable Visitor Features', 'tablepress' ) }
									text={ __( 'You can not enable the Table Features for Site Visitors, because your table’s body rows contain combined/merged cells.', 'tablepress' ) }
									onConfirm={ () => setAlertEnableDataTablesMergedCellsIsShown( false ) }
									modalProps={ {
										className: 'has-size-medium', // Using size: 'medium' is only possible in WP 6.5+.
									} }
								/>
							) }
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Sorting', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Enable sorting of the table by the visitor.', 'tablepress' ) }
								checked={ tableOptions.datatables_sort }
								disabled={ ! dataTablesEnabled }
								onChange={ ( datatables_sort ) => updateTableOptions( { datatables_sort } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Search/Filtering', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Enable the visitor to filter or search the table. Only rows with the search word in them are shown.', 'tablepress' ) }
								checked={ tableOptions.datatables_filter }
								disabled={ ! dataTablesEnabled }
								onChange={ ( datatables_filter ) => updateTableOptions( { datatables_filter } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Pagination', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Enable pagination of the table (viewing only a certain number of rows at a time) by the visitor.', 'tablepress' ) }
								checked={ tableOptions.datatables_paginate }
								disabled={ ! dataTablesEnabled }
								onChange={ ( datatables_paginate ) => updateTableOptions( { datatables_paginate } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row"></th>
						<td className="column-2">
							<label htmlFor="option-datatables_paginate_entries">
								<HStack
									alignment="left"
									style={ {
										paddingLeft: '24px',
									} }
								>
									{
										createInterpolateElement(
											__( 'Show <input /> rows per page.', 'tablepress' ),
											{
												input: (
													<NumberControl
														size="compact"
														id="option-datatables_paginate_entries"
														title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
														isDragEnabled={ false }
														value={ tableOptions.datatables_paginate_entries }
														disabled={ ! dataTablesEnabled || ! tableOptions.datatables_paginate }
														onChange={ ( datatables_paginate_entries ) => {
															datatables_paginate_entries = '' !== datatables_paginate_entries ? parseInt( datatables_paginate_entries, 10 ) : 1;
															updateTableOptions( { datatables_paginate_entries } );
														} }
														min={ 1 }
														required={ true }
														style={ {
															width: '65px',
														} }
													/>
												),
											},
										)
									}
								</HStack>
							</label>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Pagination Length Change', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Allow the visitor to change the number of rows shown when using pagination.', 'tablepress' ) }
								checked={ tableOptions.datatables_lengthchange }
								disabled={ ! dataTablesEnabled || ! tableOptions.datatables_paginate }
								onChange={ ( datatables_lengthchange ) => updateTableOptions( { datatables_lengthchange } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Info', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Enable the table information display, with information about the currently visible data, like the number of rows.', 'tablepress' ) }
								checked={ tableOptions.datatables_info }
								disabled={ ! dataTablesEnabled }
								onChange={ ( datatables_info ) => updateTableOptions( { datatables_info } ) }
							/>
						</td>
					</tr>
					<tr className={ tp.screenOptions.showCustomCommands ? 'bottom-border' : undefined }>
						<th className="column-1" scope="row">{ __( 'Horizontal Scrolling', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Enable horizontal scrolling, to make viewing tables with many columns easier.', 'tablepress' ) }
								checked={ tableOptions.datatables_scrollx }
								disabled={ ! dataTablesEnabled }
								onChange={ ( datatables_scrollx ) => updateTableOptions( { datatables_scrollx } ) }
							/>
						</td>
					</tr>
					{ ( tp.screenOptions.showCustomCommands ) && (
						<tr className="top-border">
							<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_custom_commands">{ __( 'Custom Commands', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<CustomCommandsControl
									tableOptionCustomCommands={ tableOptions.datatables_custom_commands }
									updateTableOptions={ updateTableOptions }
									dataTablesEnabled={ dataTablesEnabled }
								/>
							</td>
						</tr>
					) }
				</tbody>
			</table>
		</VStack>
	);
};

initializeReactComponentInPortal(
	'datatables-features',
	'edit',
	Section,
);
