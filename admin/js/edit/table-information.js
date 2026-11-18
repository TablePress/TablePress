/**
 * JavaScript code for the "Edit" section integration of the "Table Information".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useState } from 'react';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { copy } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import {
	// IconButton, // Due to compatibility issues with WP < 7.0, we use a custom `IconButton` component instead of the one from the `@wordpress/ui` package.
	InputControl,
	InputLayout,
	Stack,
	TextareaControl,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { IconButton } from '../common/icon-button';
import { showNotice } from '../common/functions';
import { useTableMeta, useUpdateTableMeta } from './context/table-meta';

/**
 * Returns the "Table Information" section's JSX markup.
 *
 * @return {Object} "Table Information" section component.
 */
const Section = () => {
	const tableMeta = useTableMeta();
	const updateTableMeta = useUpdateTableMeta();
	const noticesStoreDispatch = useDispatch( noticesStore );
	const [ tableId, setTableId ] = useState( tableMeta.id );

	const copyShortcodeButtonRef = useCopyToClipboard( `[${ tp.table.shortcode } id=${ tableMeta.id } /]`, () => {
		showNotice(
			{
				status: 'success',
				type: 'snackbar',
				content: __( 'Copied Shortcode to clipboard.', 'tablepress' ),
			},
			noticesStoreDispatch
		);
	} );

	useEffect( () => {
		setTableId( tableMeta.id );
	}, [ tableMeta.id ] );

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
					<tr className="bottom-border">
						<th className="column-1" scope="row"><label htmlFor="table-id">{ __( 'Table ID', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<Stack direction="row" gap="md" justify="space-between" align="center">
								<InputControl
									label={ __( 'Table ID', 'tablepress' ) }
									hideLabelFromVision={ true }
									id="table-id"
									title={ __( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ) }
									pattern="[A-Za-z1-9_\-]|[A-Za-z0-9_\-]{2,}"
									value={ tableId }
									onValueChange={ ( newTableId ) => setTableId( newTableId.replace( /[^0-9a-zA-Z_-]/g, '' ) ) }
									onBlur={ ( event ) => {
										if ( tableMeta.id === tableId ) {
											return;
										}

										// The table IDs "" and "0" are not allowed, or in other words, the table ID has to fulfill /[A-Za-z1-9-_]|[A-Za-z0-9-_]{2,}/.
										if ( '' === tableId || '0' === tableId ) {
											// This alert can not be replaced by the `Alert` component, as that does not stop the cmd+S keyboard shortcut from running.
											window.alert( __( 'This table ID is invalid. Please enter a different table ID.', 'tablepress' ) );
											setTableId( tableMeta.id );
											event.target.focus();
											return;
										}

										// This alert can not be replaced by a `Modal` component, as that does not stop the cmd+S keyboard shortcut from running.
										if ( ! window.confirm( __( 'Do you really want to change the Table ID? All blocks and Shortcodes for this table in your posts and pages will have to be adjusted!', 'tablepress' ) ) ) {
											setTableId( tableMeta.id );
											return;
										}

										// Set the new table ID.
										updateTableMeta( { id: tableId } );
										document.getElementById( 'table-information-shortcode' ).focus();
									} }
									required={ true }
									readOnly={ ! tp.screenOptions.currentUserCanEditTableId }
								/>
								<label htmlFor="table-information-shortcode">
									<Stack direction="row" gap="sm" align="center">
										{
											createInterpolateElement(
												__( 'Shortcode: <input />', 'tablepress' ),
												{
													input: (
														<InputControl
															label={ __( 'Table Shortcode', 'tablepress' ) }
															hideLabelFromVision={ true }
															id="table-information-shortcode"
															value={ `[${ tp.table.shortcode } id=${ tableMeta.id } /]` }
															onFocus={ ( event ) => event.target.select() }
															readOnly={ true }
															suffix={
																<InputLayout.Slot padding="minimal">
																	<IconButton
																		label={ __( 'Copy Shortcode to clipboard', 'tablepress' ) }
																		icon={ copy }
																		ref={ copyShortcodeButtonRef }
																		size="small"
																		variant="minimal"
																		tone="neutral"
																	/>
																</InputLayout.Slot>
															}
														/>
													),
												},
											)
										}
									</Stack>
								</label>
							</Stack>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row"><label htmlFor="table-name">{ __( 'Table Name', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<InputControl
								label={ __( 'Table Name', 'tablepress' ) }
								hideLabelFromVision={ true }
								id="table-name"
								value={ tableMeta.name }
								onValueChange={ ( name ) => updateTableMeta( { name } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1 top-align" scope="row"><label htmlFor="table-description">{ __( 'Description', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<TextareaControl
								id="table-description"
								label={ __( 'Description', 'tablepress' ) }
								hideLabelFromVision={ true }
								value={ tableMeta.description }
								onValueChange={ ( description ) => updateTableMeta( { description } ) }
								rows="4"
							/>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row">{ __( 'Last Modified', 'tablepress' ) }:</th>
						<td className="column-2">
							{ sprintf( __( '%1$s by %2$s', 'tablepress' ), tableMeta.lastModified, tableMeta.lastEditor ) }
						</td>
					</tr>
				</tbody>
			</table>
		</Stack>
	);
};

initializeReactComponentInPortal(
	'table-information',
	'edit',
	Section,
);
