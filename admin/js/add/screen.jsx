/**
 * JavaScript code for the "Add New Screen" component.
 *
 * @package TablePress
 * @subpackage Add New Screen
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalNumberControl as NumberControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	TextareaControl,
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Returns the "Add New Screen" component's JSX markup.
 *
 * @return {Object} Add New Screen component.
 */
const Screen = () => {
	const [ screenData, setScreenData ] = useState( {
		name: '',
		description: '',
		rows: 5,
		columns: 5,
	} );

	/**
	 * Handles screen data state changes.
	 *
	 * @param {Object} updatedScreenData Data in the screen data state that should be updated.
	 */
	const updateScreenData = ( updatedScreenData ) => {
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			...updatedScreenData,
		} ) );
	};

	return (
		<div style={ {
			border: '1px solid #e0e0e0',
		} }>
			<Card>
				<CardHeader>
					<h2>{ __( 'Add New Table', 'tablepress' ) }</h2>
				</CardHeader>
				<CardBody>
					<VStack
						spacing="20px"
						style={ {
							maxWidth: '500px',
						} }
					>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							name="table[name]"
							label={ __( 'Table Name', 'tablepress' ) }
							help={ __( 'The name or title of your table.', 'tablepress' ) }
							value={ screenData.name }
							onChange={ ( name ) => updateScreenData( { name } ) }
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							name="table[description]"
							label={ __( 'Description', 'tablepress' ) + ' ' + __( '(optional)', 'tablepress' ) }
							help={ __( 'A description of the contents of your table.', 'tablepress' ) }
							value={ screenData.description }
							onChange={ ( description ) => updateScreenData( { description } ) }
							rows="4"
						/>
						<HStack
							alignment="left"
							spacing="20px"
						>
							<div style={ {
								width: '150px',
							} }>
								<NumberControl
									__next40pxDefaultSize
									name="table[rows]"
									label={ __( 'Number of Rows', 'tablepress' ) }
									help={ __( 'The number of rows in your table.', 'tablepress' ) }
									title={ __( 'This field must contain a positive number.', 'tablepress' ) }
									isDragEnabled={ false }
									value={ screenData.rows }
									onChange={ ( rows ) => updateScreenData( { rows } ) }
									min={ 1 }
									max={ 99999 }
									required={ true }
								/>
							</div>
							<div style={ {
								width: '150px',
							} }>
								<NumberControl
									__next40pxDefaultSize
									name="table[columns]"
									label={ __( 'Number of Columns', 'tablepress' ) }
									help={ __( 'The number of columns in your table.', 'tablepress' ) }
									title={ __( 'This field must contain a positive number.', 'tablepress' ) }
									isDragEnabled={ false }
									value={ screenData.columns }
									onChange={ ( columns ) => updateScreenData( { columns } ) }
									min={ 1 }
									max={ 99999 }
									required={ true }
								/>
							</div>
						</HStack>
						<HStack>
							<Button
								variant="primary"
								type="submit"
								text={ __( 'Add Table', 'tablepress' ) }
							/>
						</HStack>
					</VStack>
				</CardBody>
			</Card>
		</div>
	);
};

export default Screen;
