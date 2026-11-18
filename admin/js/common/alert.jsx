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
import {
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Icon,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { TablePressIcon } from '../../img/icons';

/**
 * Returns the Alert component's JSX markup.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.icon       Icon of the alert. Defaults to the TablePress icon.
 * @param {string}   props.title      Title of the alert. No header will be shown if this is not set.
 * @param {string}   props.text       Text of the alert.
 * @param {Function} props.onConfirm  Callback to confirm the alert.
 * @param {Object}   props.modalProps Additional props for the Modal.
 * @return {Object} Alert component.
 */
export const Alert = ( { icon = TablePressIcon, title, text, onConfirm, modalProps } ) => {
	return (
		<Modal
			icon={ <Icon icon={ icon } size={ TablePressIcon === icon ? '36' : undefined } style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ title }
			__experimentalHideHeader={ undefined === title }
			isDismissible={ false }
			shouldCloseOnEsc={ false }
			shouldCloseOnClickOutside={ false }
			focusOnMount="firstElement"
			{ ...modalProps }
		>
			<Stack direction="column" gap="lg">
				<Text>
					{ text }
				</Text>
				<Stack direction="row" gap="lg" justify="end">
					<Button
						onClick={ onConfirm }
					>
						{ __( 'OK', 'tablepress' ) }
					</Button>
				</Stack>
			</Stack>
		</Modal>
	);
};
