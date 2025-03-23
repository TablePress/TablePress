/**
 * JavaScript code for the "Alert" component.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useRef } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	Modal,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { TablePressIcon } from '../../img/tablepress-icon';

/**
 * Returns the Alert component's JSX markup.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.title      Title of the alert. No header will be shown if this is not set.
 * @param {string}   props.text       Text of the alert.
 * @param {Function} props.onConfirm  Callback to confirm the alert.
 * @param {Object}   props.modalProps Additional props for the Modal.
 * @return {Object} Alert component.
 */
const Alert = ( { title, text, onConfirm, modalProps } ) => {
	const confirmButtonRef = useRef();

	const handleEnter = useCallback(
		( event ) => {
			// Avoid triggering the action when a button is focused, as this can cause a double submission.
			const isConfirmButton = event.target === confirmButtonRef.current;

			if ( ! isConfirmButton && 'Enter' === event.key ) {
				onConfirm();
				event.preventDefault(); // This prevents that the triggering button is "clicked" (via "Enter") again.
			}
		},
		[ onConfirm ],
	);

	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			__experimentalHideHeader={ undefined === title }
			isDismissible={ false }
			shouldCloseOnEsc={ false }
			shouldCloseOnClickOutside={ false }
			onKeyDown={ handleEnter }
			{ ...modalProps }
		>
			<VStack spacing={ 8 }>
				<span>
					{ text }
				</span>
				<HStack alignment="right">
					<Button
						ref={ confirmButtonRef }
						variant="primary"
						text={ __( 'OK', 'tablepress' ) }
						onClick={ onConfirm }
					/>
				</HStack>
			</VStack>
		</Modal>
	);
};

export { Alert };
