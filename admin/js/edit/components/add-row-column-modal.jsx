/**
 * JavaScript code for the "Add Row/Column" modal on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Modal,
} from '@wordpress/components';
import {
	plus,
} from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Icon,
	Stack,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { NumberControl } from '../../common/number-control';

/**
 * Returns the Add Row/Column Modal component's JSX markup.
 *
 * @param {Object}   props                        Component props.
 * @param {Function} props.closeAddRowColumnModal Callback to close the Add Row/Column modal.
 * @param {string}   props.fieldLabel             The label for the number input field.
 * @param {string}   props.title                  The title of the modal.
 * @param {string}   props.type                   The type of element to add ('rows' or 'columns').
 * @return {Object} Add Row/Column Modal component.
 */
export const AddRowColumnModal = ( { closeAddRowColumnModal, fieldLabel, title, type } ) => {
	const [ appendNumber, setAppendNumber ] = useState( 1 );

	return (
		<Modal
			icon={ <Icon icon={ plus } style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ title }
			onRequestClose={ closeAddRowColumnModal }
			focusOnMount="firstInputElement"
			style={ {
				minWidth: '270px',
			} }
		>
			<Stack direction="row" gap="lg" align="end">
				<NumberControl
					size="compact"
					label={ fieldLabel }
					title={ __( 'This field must contain a positive number.', 'tablepress' ) }
					value={ appendNumber }
					onChange={ ( newAppendNumber ) => {
						newAppendNumber = '' !== newAppendNumber ? parseInt( newAppendNumber, 10 ) : '';
						setAppendNumber( newAppendNumber );
					} }
					min={ 1 }
					required={ true }
					onKeyUp={ ( event ) => {
						if ( 'Enter' === event.key ) {
							event.preventDefault();
							tp.callbacks.append( type, appendNumber );
							if ( ! event.shiftKey ) {
								closeAddRowColumnModal();
							}
						}
					} }
					style={ {
						width: '150px',
					} }
				/>
				<Button
					size="compact"
					onClick={ ( event ) => {
						tp.callbacks.append( type, appendNumber );
						if ( ! event.shiftKey ) {
							closeAddRowColumnModal();
						}
					} }
				>
					{ __( 'Add', 'tablepress' ) }
				</Button>
			</Stack>
		</Modal>
	);
};
