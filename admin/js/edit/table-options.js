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
import {
	CheckboxControl,
	FormTokenField,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';

const Section = ( { tableOptions, updateTableOptions } ) => {
	return (
		<VStack
			spacing="16px"
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
								<HStack alignment="left">
									{
										createInterpolateElement(
											__( 'The first <input /> rows are the table header.', 'tablepress' ),
											{
												input: (
													<NumberControl
														size="compact"
														id="option-table_head"
														title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
														isDragEnabled={ false }
														value={ tableOptions.table_head }
														onChange={ ( table_head ) => {
															table_head = '' !== table_head ? parseInt( table_head, 10 ) : 0;
															updateTableOptions( { table_head } );
														} }
														min={ 0 }
														max={ 9 }
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
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Table Footer', 'tablepress' ) }:</th>
						<td className="column-2">
							<label htmlFor="option-table_foot">
								<HStack alignment="left">
									{
										createInterpolateElement(
											__( 'The last <input /> rows are the table footer.', 'tablepress' ),
											{
												input: (
													<NumberControl
														size="compact"
														id="option-table_foot"
														title={ __( 'This field must contain a non-negative number.', 'tablepress' ) }
														isDragEnabled={ false }
														value={ tableOptions.table_foot }
														onChange={ ( table_foot ) => {
															table_foot = '' !== table_foot ? parseInt( table_foot, 10 ) : 0;
															updateTableOptions( { table_foot } );
														} }
														min={ 0 }
														max={ 9 }
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
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Alternating Row Colors', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'The background colors of consecutive rows shall alternate.', 'tablepress' ) }
								checked={ tableOptions.alternating_row_colors }
								onChange={ ( alternating_row_colors ) => updateTableOptions( { alternating_row_colors } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Row Hover Highlighting', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								label={ __( 'Highlight a row while the mouse cursor hovers above it by changing its background color.', 'tablepress' ) }
								checked={ tableOptions.row_hover }
								onChange={ ( row_hover ) => updateTableOptions( { row_hover } ) }
							/>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Print Table Name', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								className="checkbox-select-in-label"
								label={
									createInterpolateElement(
										_x( 'Show the table name <select /> the table.', 'position (above or below)', 'tablepress' ),
										{
											select: (
												<SelectControl
													__nextHasNoMarginBottom
													size="compact"
													variant="minimal"
													value={ tableOptions.print_name_position }
													disabled={ ! tableOptions.print_name }
													onChange={ ( print_name_position ) => updateTableOptions( { print_name_position } ) }
													options={ [
														{ label: __( 'above', 'tablepress' ), value: 'above' },
														{ label: __( 'below', 'tablepress' ), value: 'below' },
													] }
												/>
											),
										},
									)
								}
								checked={ tableOptions.print_name }
								onChange={ ( print_name ) => updateTableOptions( { print_name } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1" scope="row">{ __( 'Print Table Description', 'tablepress' ) }:</th>
						<td className="column-2">
							<CheckboxControl
								__nextHasNoMarginBottom
								className="checkbox-select-in-label"
								label={
									createInterpolateElement(
										_x( 'Show the table description <select /> the table.', 'position (above or below)', 'tablepress' ),
										{
											select: (
												<SelectControl
													__nextHasNoMarginBottom
													size="compact"
													variant="minimal"
													value={ tableOptions.print_description_position }
													disabled={ ! tableOptions.print_description }
													onChange={ ( print_description_position ) => updateTableOptions( { print_description_position } ) }
													options={ [
														{ label: __( 'above', 'tablepress' ), value: 'above' },
														{ label: __( 'below', 'tablepress' ), value: 'below' },
													] }
												/>
											),
										},
									)
								}
								checked={ tableOptions.print_description }
								onChange={ ( print_description ) => updateTableOptions( { print_description } ) }
							/>
						</td>
					</tr>
					<tr className="top-border">
							<th className="column-1 top-align" scope="row"><label htmlFor="option-extra_css_classes">{ __( 'Extra CSS Classes', 'tablepress' ) }:</label></th>
							<td className="column-2">
								<VStack>
									<FormTokenField
										__nextHasNoMarginBottom
										__next40pxDefaultSize
										id="option-extra_css_classes"
										label=""
										className="code"
										title={ __( 'This field can only contain letters, numbers, spaces, hyphens (-), underscores (_), and colons (:).', 'tablepress' ) }
										pattern="[A-Za-z0-9 _:\-]*"
										onChange={ ( extra_css_classes ) => updateTableOptions( { extra_css_classes: extra_css_classes.join( ' ' ).trim().replace( /[^A-Za-z0-9 _:-]/g, '' ) } ) }
										value={ '' !== tableOptions.extra_css_classes ? tableOptions.extra_css_classes.split( ' ' ) : [] }
										tokenizeOnBlur={ true }
										tokenizeOnSpace={ true }
										__experimentalShowHowTo={ false }
									/>
									<span style={ {
										fontSize: '12px',
										color: '#757575',
									} }>
										{
											createInterpolateElement(
												__( 'Additional CSS classes for styling purposes can be entered here.', 'tablepress' ) + ' ' + __( 'This is NOT the place to enter <a>Custom CSS</a> code!', 'tablepress' ),
												{
													a: <a href={ tp.screenOptions.optionsUrl } />, // eslint-disable-line jsx-a11y/anchor-has-content
												},
											)
										}
									</span>
								</VStack>
							</td>
						</tr>
				</tbody>
			</table>
		</VStack>
	);
};

initializeReactComponentInPortal(
	'table-options',
	'edit',
	Section,
);
