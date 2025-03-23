/**
 * JavaScript code for the "Edit" section integration of the "Table Manipulation" buttons.
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
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement, RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { Alert } from '../common/alert';
import { HelpBox } from '../common/help';

const Section = () => {
	const [ columnsAppendNumber, setColumnsAppendNumber ] = useState( 1 );
	const [ rowsAppendNumber, setRowsAppendNumber ] = useState( 1 );
	const [ alertMoveInvalidIsShown, setAlertMoveInvalidIsShown ] = useState( false );
	const [ alertDeleteRowsInvalidIsShown, setAlertDeleteRowsInvalidIsShown ] = useState( false );
	const [ alertDeleteColumnsInvalidIsShown, setAlertDeleteColumnsInvalidIsShown ] = useState( false );

	const move = ( direction, type ) => {
		if ( ! tp.helpers.move_allowed( type, direction ) ) {
			setAlertMoveInvalidIsShown( true );
			return;
		}
		tp.callbacks.move( direction, type );
	};

	const remove = ( type ) => {
		const handlingRows = ( 'rows' === type );
		const numRoCs = handlingRows ? tp.editor.options.data.length : tp.editor.options.columns.length;
		if ( numRoCs === tp.helpers.selection[ type ].length ) {
			if ( handlingRows ) {
				setAlertDeleteRowsInvalidIsShown( true );
			} else {
				setAlertDeleteColumnsInvalidIsShown( true );
			}
			return;
		}
		tp.callbacks.remove( type );
	};

	return (
		<VStack
			spacing="16px"
			style={ {
				paddingTop: '6px',
			} }
		>
			<table className="tablepress-postbox-table fixed">
				<tbody>
					<tr className="bottom-border">
						<td className="column-1">
							<HStack alignment="left">
								<span>{ __( 'Selected cells', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Insert Link', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primary( 'l' ),
											display: displayShortcut.primary( 'l' ),
										} }
										onClick={ () => tp.callbacks.insert_link.open_dialog() }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Insert Image', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primary( 'i' ),
											display: displayShortcut.primary( 'i' ),
										} }
										onClick={ () => tp.callbacks.insert_image.open_dialog() }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Advanced Editor', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primary( 'e' ),
											display: displayShortcut.primary( 'e' ),
										} }
										onClick={ () => tp.callbacks.advanced_editor.open_dialog() }
									/>
								</HStack>
							</HStack>
						</td>
						<td className="column-2">
							<HStack alignment="left">
								<span>{ __( 'Selected cells', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Combine/Merge', 'tablepress' ) }
										onClick={ () => {
											if ( tp.helpers.cell_merge_allowed( 'alert' ) ) {
												tp.callbacks.merge_cells();
											}
										} }
									/>
									<HelpBox
										buttonProps={ {
											size: 'compact',
											text: __( '?', 'tablepress' ),
											label: __( 'Help on combining cells', 'tablepress' ),
										} }
										modalProps={ {
											className: 'has-size-medium', // Using size: 'medium' is only possible in WP 6.5+.
										} }
										title={ __( 'Help on combining cells', 'tablepress' ) }
									>
										<p>
											{ __( 'Table cells can span across more than one column or row.', 'tablepress' ) }
										</p>
										<RawHTML>
											{ '<p>' }
											{ __( 'Combining consecutive cells within the same row is called &#8220;colspanning&#8221;.', 'tablepress' ) }
											{ ' ' + __( 'Combining consecutive cells within the same column is called &#8220;rowspanning&#8221;.', 'tablepress' ) }
											{ '</p>' }
										</RawHTML>
										<RawHTML>
											{ '<p>' }
											{ sprintf( __( 'To combine adjacent cells, select the desired cells and click the “%s” button or use the context menu.', 'tablepress' ), __( 'Combine/Merge', 'tablepress' ) ) }
											{ ' ' + __( 'The corresponding keywords, <code>#colspan#</code> and <code>#rowspan#</code>, will then be added for you.', 'tablepress' ) }
											{ '</p>' }
										</RawHTML>
										<p>
											<strong>
												{ __( 'Be aware that the Table Features for Site Visitors, like sorting, filtering, and pagination, will not work in tables which have combined cells in their body rows.', 'tablepress' ) }
											</strong>
											{ ' ' }
											{ __( 'It is however possible to use these features in tables that have combined cells in the table header or footer rows, to allow for creating complex header and footer layouts.', 'tablepress' ) }
										</p>
									</HelpBox>
								</HStack>
							</HStack>
						</td>
					</tr>
					<tr className="top-border">
						<td className="column-1">
							<HStack alignment="left">
								<span>{ __( 'Selected rows', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Duplicate', 'tablepress' ) }
										onClick={ () => tp.callbacks.insert_duplicate( 'duplicate', 'rows' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Insert', 'tablepress' ) }
										onClick={ () => tp.callbacks.insert_duplicate( 'insert', 'rows' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Delete', 'tablepress' ) }
										onClick={ () => remove( 'rows' ) }
									/>
								</HStack>
							</HStack>
						</td>
						<td className="column-2">
							<HStack alignment="left">
								<span>{ __( 'Selected columns', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Duplicate', 'tablepress' ) }
										onClick={ () => tp.callbacks.insert_duplicate( 'duplicate', 'columns' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Insert', 'tablepress' ) }
										onClick={ () => tp.callbacks.insert_duplicate( 'insert', 'columns' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Delete', 'tablepress' ) }
										onClick={ () => remove( 'columns' ) }
									/>
								</HStack>
							</HStack>
						</td>
					</tr>
					<tr>
						<td className="column-1">
							<HStack alignment="left">
								<span>{ __( 'Selected rows', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move up', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primaryShift( '↑' ),
											display: displayShortcut.primaryShift( '↑' ),
										} }
										onClick={ () => move( 'up', 'rows' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move down', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primaryShift( '↓' ),
											display: displayShortcut.primaryShift( '↓' ),
										} }
										onClick={ () => move( 'down', 'rows' ) }
									/>
								</HStack>
							</HStack>
						</td>
						<td className="column-2">
							<HStack alignment="left">
								<span>{ __( 'Selected columns', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move left', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primaryShift( '←' ),
											display: displayShortcut.primaryShift( '←' ),
										} }
										onClick={ () => move( 'left', 'columns' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move right', 'tablepress' ) }
										shortcut={ {
											ariaLabel: shortcutAriaLabel.primaryShift( '→' ),
											display: displayShortcut.primaryShift( '→' ),
										} }
										onClick={ () => move( 'right', 'columns' ) }
									/>
								</HStack>
							</HStack>
						</td>
					</tr>
					<tr className="bottom-border">
						<td className="column-1">
							<HStack alignment="left">
								<span>{ __( 'Selected rows', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Hide', 'tablepress' ) }
										onClick={ () => tp.callbacks.hide_unhide( 'hide', 'rows' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Show', 'tablepress' ) }
										onClick={ () => tp.callbacks.hide_unhide( 'unhide', 'rows' ) }
									/>
								</HStack>
							</HStack>
						</td>
						<td className="column-2">
							<HStack alignment="left">
								<span>{ __( 'Selected columns', 'tablepress' ) }:</span>
								<HStack spacing="4px" alignment="left" expanded={ false } wrap={ true }>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Hide', 'tablepress' ) }
										onClick={ () => tp.callbacks.hide_unhide( 'hide', 'columns' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Show', 'tablepress' ) }
										onClick={ () => tp.callbacks.hide_unhide( 'unhide', 'columns' ) }
									/>
								</HStack>
							</HStack>
						</td>
					</tr>
					<tr className="top-border">
						<td className="column-1">
							<HStack alignment="left">
								<label htmlFor="rows-append-number">
									<HStack>
										{
											createInterpolateElement(
												__( 'Add <input /> row(s)', 'tablepress' ),
												{
													input: (
														<NumberControl
															size="compact"
															id="rows-append-number"
															title={ __( 'This field must contain a positive number.', 'tablepress' ) }
															isDragEnabled={ false }
															value={ rowsAppendNumber }
															onChange={ ( newRowsAppendNumber ) => {
																newRowsAppendNumber = '' !== newRowsAppendNumber ? parseInt( newRowsAppendNumber, 10 ) : 1;
																setRowsAppendNumber( newRowsAppendNumber )
															} }
															min={ 1 }
															required={ true }
															style={ {
																width: '55px',
															} }
														/>
													),
												},
											)
										}
									</HStack>
								</label>
								<Button
									variant="secondary"
									size="compact"
									text={ __( 'Add', 'tablepress' ) }
									onClick={ () => tp.callbacks.append( 'rows', rowsAppendNumber ) }
								/>
							</HStack>
						</td>
						<td className="column-2">
							<HStack alignment="left">
								<label htmlFor="columns-append-number">
									<HStack>
										{
											createInterpolateElement(
												__( 'Add <input /> column(s)', 'tablepress' ),
												{
													input: (
														<NumberControl
															size="compact"
															id="columns-append-number"
															title={ __( 'This field must contain a positive number.', 'tablepress' ) }
															isDragEnabled={ false }
															value={ columnsAppendNumber }
															onChange={ ( newColumnsAppendNumber ) => {
																newColumnsAppendNumber = '' !== newColumnsAppendNumber ? parseInt( newColumnsAppendNumber, 10 ) : 1;
																setColumnsAppendNumber( newColumnsAppendNumber )
															} }
															min={ 1 }
															required={ true }
															style={ {
																width: '55px',
															} }
														/>
													),
												},
											)
										}
									</HStack>
								</label>
								<Button
									variant="secondary"
									size="compact"
									text={ __( 'Add', 'tablepress' ) }
									onClick={ () => tp.callbacks.append( 'columns', columnsAppendNumber ) }
								/>
							</HStack>
						</td>
					</tr>
				</tbody>
			</table>
			{ alertMoveInvalidIsShown && (
				<Alert
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not do this move, because you reached the border of the table.', 'tablepress' ) }
					onConfirm={ () => setAlertMoveInvalidIsShown( false ) }
					modalProps={ {
						className: 'has-size-small', // Using size: 'small' is only possible in WP 6.5+.
					} }
				/>
			) }
			{ alertDeleteRowsInvalidIsShown && (
				<Alert
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not delete all table rows!', 'tablepress' ) }
					onConfirm={ () => setAlertDeleteRowsInvalidIsShown( false ) }
				/>
			) }
			{ alertDeleteColumnsInvalidIsShown && (
				<Alert
					title={ __( 'Table Manipulation', 'tablepress' ) }
					text={ __( 'You can not delete all table columns!', 'tablepress' ) }
					onConfirm={ () => setAlertDeleteColumnsInvalidIsShown( false ) }
				/>
			) }
		</VStack>
	);
};

initializeReactComponentInPortal(
	'table-manipulation',
	'edit',
	Section,
);
