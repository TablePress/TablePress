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
import { createInterpolateElement } from '@wordpress/element';
import { __, _x, sprintf } from '@wordpress/i18n';

const modifierKey = ( window?.navigator?.platform?.includes( 'Mac' ) ) ?
	_x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) :
	_x( 'Ctrl+', 'keyboard shortcut modifier key on a non-Mac keyboard', 'tablepress' );

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';

const Section = () => {
	const [ columnsAppendNumber, setColumnsAppendNumber ] = useState( 1 );
	const [ rowsAppendNumber, setRowsAppendNumber ] = useState( 1 );

	const move = ( direction, type ) => {
		if ( ! tp.helpers.move_allowed( type, direction ) ) {
			window.alert( __( 'You can not do this move, because you reached the border of the table.', 'tablepress' ) );
			return;
		}
		tp.callbacks.move( direction, type );
	};

	const remove = ( type ) => {
		const handling_rows = ( 'rows' === type );
		const num_rocs = handling_rows ? tp.editor.options.data.length : tp.editor.options.columns.length;
		if ( num_rocs === tp.helpers.selection[ type ].length ) {
			window.alert( handling_rows ? __( 'You can not delete all table rows!', 'tablepress' ) : __( 'You can not delete all table columns!', 'tablepress' ) );
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
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$sL', 'keyboard shortcut for Insert Link', 'tablepress' ), modifierKey ) ) }
										onClick={ () => tp.callbacks.insert_link.open_dialog() }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Insert Image', 'tablepress' ) }
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$sI', 'keyboard shortcut for Insert Image', 'tablepress' ), modifierKey ) ) }
										onClick={ () => tp.callbacks.insert_image.open_dialog() }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Advanced Editor', 'tablepress' ) }
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$sE', 'keyboard shortcut for Advanced Editor', 'tablepress' ), modifierKey ) ) }
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
									<Button
										variant="secondary"
										size="compact"
										text={ __( '?', 'tablepress' ) }
										title={ __( 'Help on combining cells', 'tablepress' ) }
										onClick={ () => tp.callbacks.help_box.open_dialog( '#help-box-combine-cells' ) }
									/>
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
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$s⇧↑', 'keyboard shortcut for Move up', 'tablepress' ), modifierKey ) ) }
										onClick={ () => move( 'up', 'rows' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move down', 'tablepress' ) }
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$s⇧↓', 'keyboard shortcut for Move down', 'tablepress' ), modifierKey ) ) }
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
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$s⇧←', 'keyboard shortcut for Move left', 'tablepress' ), modifierKey ) ) }
										onClick={ () => move( 'left', 'columns' ) }
									/>
									<Button
										variant="secondary"
										size="compact"
										text={ __( 'Move right', 'tablepress' ) }
										title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$s⇧→', 'keyboard shortcut for Move right', 'tablepress' ), modifierKey ) ) }
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
		</VStack>
	);
};

initializeReactComponentInPortal(
	'table-manipulation',
	'edit',
	Section,
);
