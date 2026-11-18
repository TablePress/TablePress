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
	Card,
	CardBody,
	CardHeader,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	Button,
	InputControl,
	Stack,
	TextareaControl,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { NumberControl } from '../common/number-control';

/**
 * Returns the "Add New Screen" component's JSX markup.
 *
 * @return {Object} Add New Screen component.
 */
const Screen = () => {
	const [ name, setName ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ rows, setRows ] = useState( 5 );
	const [ columns, setColumns ] = useState( 5 );

	return (
		<Card>
			<CardHeader>
				<h2>{ __( 'Add New Table', 'tablepress' ) }</h2>
			</CardHeader>
			<CardBody>
				<Stack
					direction="column"
					gap="lg"
					style={ {
						maxWidth: '500px',
					} }
				>
					<InputControl
						name="table[name]"
						label={ __( 'Table Name', 'tablepress' ) }
						description={ __( 'The name or title of your table.', 'tablepress' ) }
						value={ name }
						onValueChange={ ( newName ) => setName( newName ) }
					/>
					<TextareaControl
						name="table[description]"
						label={ __( 'Description', 'tablepress' ) + ' ' + __( '(optional)', 'tablepress' ) }
						description={ __( 'A description of the contents of your table.', 'tablepress' ) }
						value={ description }
						onValueChange={ ( newDescription ) => setDescription( newDescription ) }
						rows="4"
					/>
					<Stack direction="row" gap="lg" wrap="wrap">
						<Stack style={ { width: '150px' } }>
							<NumberControl
								name="table[rows]"
								label={ __( 'Number of Rows', 'tablepress' ) }
								description={ __( 'The number of rows in your table.', 'tablepress' ) }
								title={ __( 'This field must contain a positive number.', 'tablepress' ) }
								value={ rows }
								onChange={ ( newRows ) => setRows( newRows ) }
								min={ 1 }
								max={ 99999 }
								required={ true }
							/>
						</Stack>
						<Stack style={ { width: '150px' } }>
							<NumberControl
								name="table[columns]"
								label={ __( 'Number of Columns', 'tablepress' ) }
								description={ __( 'The number of columns in your table.', 'tablepress' ) }
								title={ __( 'This field must contain a positive number.', 'tablepress' ) }
								value={ columns }
								onChange={ ( newColumns ) => setColumns( newColumns ) }
								min={ 1 }
								max={ 99999 }
								required={ true }
							/>
						</Stack>
					</Stack>
					<Stack>
						<Button type="submit">
							{ __( 'Add Table', 'tablepress' ) }
						</Button>
					</Stack>
				</Stack>
			</CardBody>
		</Card>
	);
};

export default Screen;
