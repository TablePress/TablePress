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
import { memo, useEffect, useState } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	CheckboxControl,
	Link,
	Stack,
	Text,
	TextareaControl,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { Alert } from '../common/alert';
import { NumberControl } from '../common/number-control';
import { useTableOptions, useUpdateTableOptions } from './context/table-options';

/**
 * Returns the "Table Features for Site Visitors" section's JSX markup, optimized via memoization to avoid unnecessary re-renders.
 * For this, only the actually used table options are passed to the Section component as props.
 *
 * @return {Object} "Table Features for Site Visitors" section component.
 */
const SectionWrapper = () => {
	const { table_head, use_datatables, datatables_sort, datatables_filter, datatables_paginate, datatables_paginate_entries, datatables_lengthchange, datatables_info, datatables_scrollx, datatables_custom_commands } = useTableOptions();

	return (
		<Section
			table_head={ table_head}
			use_datatables={ use_datatables}
			datatables_sort={ datatables_sort}
			datatables_filter={ datatables_filter}
			datatables_paginate={ datatables_paginate}
			datatables_paginate_entries={ datatables_paginate_entries}
			datatables_lengthchange={ datatables_lengthchange}
			datatables_info={ datatables_info}
			datatables_scrollx={ datatables_scrollx}
			datatables_custom_commands={ datatables_custom_commands}
		/>
	);
};

/**
 * Wraps the "Custom Commands" in a separate component, to keep internal states at a lower level.
 *
 * @param {Object}   props                Function parameters.
 * @param {string}   props.customCommands The current value of the "Custom Commands" table option.
 * @param {boolean}  props.disabled       Whether the control should be disabled or not.
 * @param {Function} props.onChange       Function to update the external state.
 * @return {Object} "Custom Commands" control component.
 */
const CustomCommandsControl = ( { customCommands, disabled, onChange } ) => {
	const [ internalCustomCommands, setInternalCustomCommands ] = useState( customCommands );
	const [ fieldExpanded, setFieldExpanded ] = useState( false );

	/*
	// Updating the state variable when the table option changes is not necessary, as it's never modified externally.
	useEffect( () => {
		setInternalCustomCommands( customCommands );
	}, [ customCommands ] );
	*/

	return (
		<TextareaControl
			id="option-datatables_custom_commands"
			label={ __( 'Custom Commands', 'tablepress' ) }
			hideLabelFromVision={ true }
			rows={
				fieldExpanded && '' !== internalCustomCommands
					? Math.min( 15, internalCustomCommands.split( '\n' ).length )
					: 1
			}
			className="code"
			value={ internalCustomCommands }
			disabled={ disabled }
			onValueChange={ ( newInternalCustomCommands ) => setInternalCustomCommands( newInternalCustomCommands ) }
			onBlur={ () => {
				if ( internalCustomCommands !== customCommands ) {
					onChange( internalCustomCommands );
				}
			} }
			onFocus={ () => setFieldExpanded( true ) }
			description={
				createInterpolateElement(
					__( 'Additional parameters from the <a>DataTables documentation</a> to be added to the JS call.', 'tablepress' ) + ' ' + __( 'For advanced use only.', 'tablepress' ),
					{
						a: <Link href="https://datatables.net/" openInNewTab={ true } />,
					},
				)
			}
		/>
	);
};

/**
 * Returns the "Table Features for Site Visitors" section's JSX markup.
 *
 * The component is memoized to avoid unnecessary re-renders when the table options it uses have not changed.
 *
 * @return {Object} "Table Features for Site Visitors" section component.
 */
const Section = memo( ( { table_head, use_datatables, datatables_sort, datatables_filter, datatables_paginate, datatables_paginate_entries, datatables_lengthchange, datatables_info, datatables_scrollx, datatables_custom_commands } ) => {
	const updateTableOptions = useUpdateTableOptions();
	const [ alertEnableDataTablesMergedCellsIsShown, setAlertEnableDataTablesMergedCellsIsShown ] = useState( false );

	const tableHeadEnabled = ( table_head > 0 );
	const dataTablesEnabled = ( tableHeadEnabled && use_datatables );

	// Turn off "Enable Visitor Features" if the table has merged cells.
	useEffect( () => {
		if ( use_datatables && tp.helpers.editor.has_merged_body_cells() ) {
			updateTableOptions( { use_datatables: false } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	return (
		<Stack
			direction="column"
			gap="lg"
			style={ {
				paddingTop: '6px',
			} }
		>
			{
				( ! tableHeadEnabled ) && (
					<Text>
						<em>
							{
								sprintf(
									__( 'These features and options are only available when the “%1$s” setting in the “%2$s” section is used.', 'tablepress' ),
									__( 'Table Header', 'tablepress' ),
									__( 'Table Options', 'tablepress' ),
								)
							}
						</em>
					</Text>
				)
			}
			<table className="tablepress-postbox-table fixed">
				<tbody>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Enable Visitor Features', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Offer the following functions for site visitors with this table:', 'tablepress' ) }
								checked={ use_datatables }
								disabled={ ! tableHeadEnabled }
								onCheckedChange={ ( new_use_datatables ) => {
									// Don't turn on "Enable Visitor Features" if the table has merged cells.
									if ( new_use_datatables ) {
										tp.helpers.visibility.update(); // Update information about hidden rows and columns.
										if ( tp.helpers.editor.has_merged_body_cells() ) {
											setAlertEnableDataTablesMergedCellsIsShown( true );
											return;
										}
									}
									updateTableOptions( { use_datatables: new_use_datatables } );
								} }
							/>
							{ alertEnableDataTablesMergedCellsIsShown && (
								<Alert
									title={ __( 'Enable Visitor Features', 'tablepress' ) }
									text={ __( 'You can not enable the Table Features for Site Visitors, because your table’s body rows contain combined/merged cells.', 'tablepress' ) }
									onConfirm={ () => setAlertEnableDataTablesMergedCellsIsShown( false ) }
									modalProps={ {
										size: 'medium',
									} }
								/>
							) }
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Sorting', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Enable sorting of the table by the visitor.', 'tablepress' ) }
								checked={ datatables_sort }
								disabled={ ! dataTablesEnabled }
								onCheckedChange={ ( new_datatables_sort ) => updateTableOptions( { datatables_sort: new_datatables_sort } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Search/Filtering', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Enable the visitor to filter or search the table. Only rows with the search word in them are shown.', 'tablepress' ) }
								checked={ datatables_filter }
								disabled={ ! dataTablesEnabled }
								onCheckedChange={ ( new_datatables_filter ) => updateTableOptions( { datatables_filter: new_datatables_filter } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Pagination', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Enable pagination of the table (viewing only a certain number of rows at a time) by the visitor.', 'tablepress' ) }
								checked={ datatables_paginate }
								disabled={ ! dataTablesEnabled }
								onCheckedChange={ ( new_datatables_paginate ) => updateTableOptions( { datatables_paginate: new_datatables_paginate } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row"></th>
						<td className="column-2">
							<label
								htmlFor="option-datatables_paginate_entries"
								style={ {
									display: 'inline-block',
									marginInlineStart: '24px',
								} }
							>
								{
									createInterpolateElement(
										__( 'Show <input /> rows per page.', 'tablepress' ),
										{
											input: (
												<NumberControl
													size="compact"
													label={ __( 'Number of rows per page', 'tablepress' ) }
													hideLabelFromVision={ true }
													id="option-datatables_paginate_entries"
													className="component-inline-flex"
													title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
													value={ datatables_paginate_entries }
													disabled={ ! dataTablesEnabled || ! datatables_paginate }
													onChange={ ( new_datatables_paginate_entries ) => {
														new_datatables_paginate_entries = '' !== new_datatables_paginate_entries ? parseInt( new_datatables_paginate_entries, 10 ) : '';
														updateTableOptions( { datatables_paginate_entries: new_datatables_paginate_entries } );
													} }
													min={ 1 }
													required={ true }
													digits={ 3 }
												/>
											),
										},
									)
								}
							</label>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Pagination Length Change', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Allow the visitor to change the number of rows shown when using pagination.', 'tablepress' ) }
								checked={ datatables_lengthchange }
								disabled={ ! dataTablesEnabled || ! datatables_paginate }
								onCheckedChange={ ( new_datatables_lengthchange ) => updateTableOptions( { datatables_lengthchange: new_datatables_lengthchange } ) }
							/>
						</td>
					</tr>
					<tr>
						<th className="column-1" scope="row">{ __( 'Info', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Enable the table information display, with information about the currently visible data, like the number of rows.', 'tablepress' ) }
								checked={ datatables_info }
								disabled={ ! dataTablesEnabled }
								onCheckedChange={ ( new_datatables_info ) => updateTableOptions( { datatables_info: new_datatables_info } ) }
							/>
						</td>
					</tr>
					<tr className={ tp.screenOptions.showCustomCommands ? 'bottom-border' : undefined }>
						<th className="column-1" scope="row">{ __( 'Horizontal Scrolling', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Enable horizontal scrolling, to make viewing tables with many columns easier.', 'tablepress' ) }
								checked={ datatables_scrollx }
								disabled={ ! dataTablesEnabled }
								onCheckedChange={ ( new_datatables_scrollx ) => updateTableOptions( { datatables_scrollx: new_datatables_scrollx } ) }
							/>
						</td>
					</tr>
					{ ( tp.screenOptions.showCustomCommands ) && (
						<tr className="top-border">
							<th className="column-1 top-align" scope="row"><label htmlFor="option-datatables_custom_commands">{ __( 'Custom Commands', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<CustomCommandsControl
									customCommands={ datatables_custom_commands }
									onChange={ ( newCustomCommands ) => updateTableOptions( { datatables_custom_commands: newCustomCommands } ) }
									disabled={ ! dataTablesEnabled }
								/>
							</td>
						</tr>
					) }
				</tbody>
			</table>
		</Stack>
	);
} );

initializeReactComponentInPortal(
	'datatables-features',
	'edit',
	SectionWrapper,
);
