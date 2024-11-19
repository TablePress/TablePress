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
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextareaControl,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { $ } from '../common/functions';

const Section = ( { tableMeta, updateTableMeta } ) => {
	const [ tableId, setTableId ] = useState( tableMeta.newId );

	useEffect( () => {
		setTableId( tableMeta.newId );
	}, [ tableMeta.newId ] );

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
									id="table-id"
									title={ __( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ) }
									pattern="[A-Za-z1-9_\-]|[A-Za-z0-9_\-]{2,}"
									value={ tableId }
									onChange={ ( newTableId ) => setTableId( newTableId.replace( /[^0-9a-zA-Z_-]/g, '' ) ) }
									onBlur={ ( event ) => {
										if ( tableMeta.newId === tableId ) {
											return;
										}

										// The table IDs "" and "0" are not allowed, or in other words, the table ID has to fulfill /[A-Za-z1-9-_]|[A-Za-z0-9-_]{2,}/.
										if ( '' === tableId || '0' === tableId ) {
											window.alert( __( 'This table ID is invalid. Please enter a different table ID.', 'tablepress' ) );
											setTableId( tableMeta.newId );
											event.target.focus();
											return;
										}

										if ( ! window.confirm( __( 'Do you really want to change the Table ID? All blocks and Shortcodes for this table in your posts and pages will have to be adjusted!', 'tablepress' ) ) ) {
											setTableId( tableMeta.newId );
											return;
										}

										// Set the new table ID.
										updateTableMeta( { newId: tableId } );
										$( '#table-information-shortcode' ).focus();
									} }
									required={ true }
									readOnly={ ! tp.screen_options.currentUserCanEditTableId }
								/>
								<label htmlFor="table-information-shortcode">
									<HStack alignment="left">
										{
											createInterpolateElement(
												__( 'Shortcode: <input />', 'tablepress' ),
												{
													input: (
														<TextControl
															__nextHasNoMarginBottom
															id="table-information-shortcode"
															value={ `[${ tp.table.shortcode } id=${ tableMeta.newId } /]` }
															onFocus={ ( event ) => event.target.select() }
															readOnly={ true }
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
