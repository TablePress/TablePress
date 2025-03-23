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
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextareaControl,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { copySmall } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { TablePressIconSimple } from '../../img/tablepress-icon';

const Section = ( { tableMeta, updateTableMeta } ) => {
	const { createSuccessNotice } = useDispatch( noticesStore );
	const [ tableId, setTableId ] = useState( tableMeta.id );

	const copyShortcodeButtonRef = useCopyToClipboard( `[${ tp.table.shortcode } id=${ tableMeta.id } /]`, () => {
		createSuccessNotice(
			__( 'Copied Shortcode to clipboard.', 'tablepress' ),
			{
				type: 'snackbar',
				icon: <Icon icon={ TablePressIconSimple } />,
			}
		);
	} );

	useEffect( () => {
		setTableId( tableMeta.id );
	}, [ tableMeta.id ] );

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
						<th className="column-1" scope="row"><label htmlFor="table-id">{ __( 'Table ID', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<HStack>
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									id="table-id"
									title={ __( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ) }
									pattern="[A-Za-z1-9_\-]|[A-Za-z0-9_\-]{2,}"
									value={ tableId }
									onChange={ ( newTableId ) => setTableId( newTableId.replace( /[^0-9a-zA-Z_-]/g, '' ) ) }
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
									<HStack alignment="left">
										{
											createInterpolateElement(
												__( 'Shortcode: <input />', 'tablepress' ),
												{
													input: (
														<InputControl
															__next40pxDefaultSize
															type="text"
															id="table-information-shortcode"
															value={ `[${ tp.table.shortcode } id=${ tableMeta.id } /]` }
															onFocus={ ( event ) => event.target.select() }
															readOnly={ true }
															suffix={
																<InputControlSuffixWrapper variant="control">
																	<Button
																		icon={ copySmall }
																		ref={ copyShortcodeButtonRef }
																		size="small"
																		label={ __( 'Copy Shortcode to clipboard', 'tablepress' ) }
																	/>
																</InputControlSuffixWrapper>
															}
														/>
													),
												},
											)
										}
									</HStack>
								</label>
							</HStack>
						</td>
					</tr>
					<tr className="top-border">
						<th className="column-1" scope="row"><label htmlFor="table-name">{ __( 'Table Name', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								id="table-name"
								value={ tableMeta.name }
								onChange={ ( name ) => updateTableMeta( { name } ) }
							/>
						</td>
					</tr>
					<tr className="bottom-border">
						<th className="column-1 top-align" scope="row"><label htmlFor="table-description">{ __( 'Description', 'tablepress' ) }:</label></th>
						<td className="column-2">
							<TextareaControl
								__nextHasNoMarginBottom
								id="table-description"
								value={ tableMeta.description }
								onChange={ ( description ) => updateTableMeta( { description } ) }
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
		</VStack>
	);
};

initializeReactComponentInPortal(
	'table-information',
	'edit',
	Section,
);
