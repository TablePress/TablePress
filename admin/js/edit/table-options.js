/**
 * JavaScript code for the "Edit" section integration of the "Table Options".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { memo } from 'react';
import {
	FormTokenField,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import{
	CheckboxControl,
	Link,
	SelectControl,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { NumberControl } from '../common/number-control';
import { useTableOptions, useUpdateTableOptions } from './context/table-options';

const positionOptions = [
	{ label: __( 'above', 'tablepress' ), value: 'above' },
	{ label: __( 'below', 'tablepress' ), value: 'below' },
];

/**
 * Returns the "Table Options" section's JSX markup, optimized via memoization to avoid unnecessary re-renders.
 * For this, only the actually used table options are passed to the Section component as props.
 *
 * @return {Object} "Table Options" section component.
 */
const SectionWrapper = () => {
	const { table_head, table_foot, alternating_row_colors, row_hover, print_name_position, print_name, print_description_position, print_description, extra_css_classes } = useTableOptions();

	return (
		<Section
			table_head={ table_head }
			table_foot={ table_foot }
			alternating_row_colors={ alternating_row_colors }
			row_hover={ row_hover }
			print_name_position={ print_name_position }
			print_name={ print_name }
			print_description_position={ print_description_position }
			print_description={ print_description }
			extra_css_classes={ extra_css_classes }
		/>
	);
};

/**
 * Returns the "Table Options" section's JSX markup.
 *
 * The component is memoized to avoid unnecessary re-renders when the table options it uses have not changed.
 *
 * @return {Object} "Table Options" section component.
 */
const Section = memo( ( { table_head, table_foot, alternating_row_colors, row_hover, print_name_position, print_name, print_description_position, print_description, extra_css_classes } ) => {
	const updateTableOptions = useUpdateTableOptions();

	return (
		<Stack
			direction="column"
			gap="lg"
			style={ {
				paddingTop: '6px',
			} }
		>
			<table className="tablepress-postbox-table fixed">
				<tbody>
					<tr>
						<th className="column-1" scope="row">{ __( 'Table Header', 'tablepress' ) }:</th>
						<td className="column-2">
							<label htmlFor="option-table_head">
								{
									createInterpolateElement(
										__( 'The first <input /> rows are the table header.', 'tablepress' ),
										{
											input: (
												<NumberControl
													size="compact"
													label={ __( 'Number of rows to show as table header', 'tablepress' ) }
													hideLabelFromVision={ true }
													id="option-table_head"
													className="component-inline-flex"
													title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
													value={ table_head }
													onChange={ ( new_table_head ) => {
														new_table_head = '' !== new_table_head ? parseInt( new_table_head, 10 ) : '';
														updateTableOptions( { table_head: new_table_head } );
													} }
													min={ 0 }
													max={ 9 }
													required={ true }
													digits={ 1 }
												/>
											),
										},
									)
								}
							</label>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Table Footer', 'tablepress' ) }:</th>
						<td className="column-2">
							<label htmlFor="option-table_foot">
								{
									createInterpolateElement(
										__( 'The last <input /> rows are the table footer.', 'tablepress' ),
										{
											input: (
												<NumberControl
													size="compact"
													label={ __( 'Number of rows to show as table footer', 'tablepress' ) }
													hideLabelFromVision={ true }
													id="option-table_foot"
													className="component-inline-flex"
													title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
													value={ table_foot }
													onChange={ ( new_table_foot ) => {
														new_table_foot = '' !== new_table_foot ? parseInt( new_table_foot, 10 ) : '';
														updateTableOptions( { table_foot: new_table_foot } );
													} }
													min={ 0 }
													max={ 9 }
													required={ true }
													digits={ 1 }
												/>
											),
										},
									)
								}
							</label>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Alternating Row Colors', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'The background colors of consecutive rows shall alternate.', 'tablepress' ) }
								checked={ alternating_row_colors }
								onCheckedChange={ ( new_alternating_row_colors ) => updateTableOptions( { alternating_row_colors: new_alternating_row_colors } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Row Hover Highlighting', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								label={ __( 'Highlight a row while the mouse cursor hovers above it by changing its background color.', 'tablepress' ) }
								checked={ row_hover }
								onCheckedChange={ ( new_row_hover ) => updateTableOptions( { row_hover: new_row_hover } ) }
							/>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Print Table Name', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								className="checkbox-select-in-label"
								label={
									createInterpolateElement(
										_x( 'Show the table name <select /> the table.', 'position (above or below)', 'tablepress' ),
										{
											select: (
												<SelectControl
													label={ __( 'Position of the table name', 'tablepress' ) }
													hideLabelFromVision={ true }
													className="component-inline-flex"
													size="compact"
													value={ positionOptions.find( ( item ) => item.value === print_name_position ) }
													disabled={ ! print_name }
													onValueChange={ ( selectedItem ) => updateTableOptions( { print_name_position: selectedItem.value } ) }
													items={ positionOptions }
												/>
											),
										},
									)
								}
								checked={ print_name }
								onCheckedChange={ ( new_print_name ) => updateTableOptions( { print_name: new_print_name } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Print Table Description', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								className="checkbox-select-in-label"
								label={
									createInterpolateElement(
										_x( 'Show the table description <select /> the table.', 'position (above or below)', 'tablepress' ),
										{
											select: (
												<SelectControl
													label={ __( 'Position of the table description', 'tablepress' ) }
													hideLabelFromVision={ true }
													className="component-inline-flex"
													size="compact"
													value={ positionOptions.find( ( item ) => item.value === print_description_position ) }
													disabled={ ! print_description }
													onValueChange={ ( selectedItem ) => updateTableOptions( { print_description_position: selectedItem.value } ) }
													items={ positionOptions }
												/>
											),
										},
									)
								}
								checked={ print_description }
								onCheckedChange={ ( new_print_description ) => updateTableOptions( { print_description: new_print_description } ) }
							/>
						</td>
					</tr>
					<tr className="top-border">
							<th className="column-1 top-align" scope="row"><label htmlFor="option-extra_css_classes">{ __( 'Extra CSS Classes', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<Stack direction="column" gap="sm">
									<FormTokenField
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										id="option-extra_css_classes"
										label=""
										className="code"
										title={ __( 'This field can only contain letters, numbers, spaces, hyphens (-), underscores (_), and colons (:).', 'tablepress' ) }
										pattern="[A-Za-z0-9 _:\-]*"
										onChange={ ( new_extra_css_classes ) => updateTableOptions( { extra_css_classes: new_extra_css_classes.join( ' ' ).trim().replace( /[^A-Za-z0-9 _:-]/g, '' ) } ) }
										value={ '' !== extra_css_classes ? extra_css_classes.split( ' ' ) : [] }
										tokenizeOnBlur={ true }
										tokenizeOnSpace={ true }
										__experimentalShowHowTo={ false }
									/>
									<Text style={ {
										fontSize: '12px',
										color: '#757575',
									} }>
										{
											createInterpolateElement(
												__( 'Additional CSS classes for styling purposes can be entered here.', 'tablepress' ) + ' ' + __( 'This is NOT the place to enter <a>Custom CSS</a> code!', 'tablepress' ),
												{
													a: <Link href={ tp.screenOptions.optionsUrl } />,
												},
											)
										}
									</Text>
								</Stack>
							</td>
						</tr>
				</tbody>
			</table>
		</Stack>
	);
} );

initializeReactComponentInPortal(
	'table-options',
	'edit',
	SectionWrapper,
);
