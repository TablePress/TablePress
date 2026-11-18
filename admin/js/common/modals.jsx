/**
 * JavaScript code for commonly shared Modals on the admin screens.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useRef } from 'react';
import {
	Button as LegacyButton,
	Modal,
} from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { __, _n } from '@wordpress/i18n';
import {
	Button,
	Icon,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Returns the Confirm Delete Modal component's JSX markup.
 *
 * @param {Object}   props                         Component props.
 * @param {string}   props.title                   Title of the modal.
 * @param {string}   props.deleteUrl               URL to delete the table.
 * @param {Function} props.closeConfirmDeleteModal Callback to close the Confirm Delete modal.
 * @return {Object} Confirm Delete Modal component.
 */
export const ConfirmDeleteModal = ( { title, deleteUrl, closeConfirmDeleteModal } ) => {
	const cancelButtonRef = useRef();
	const deleteButtonRef = useRef();

	/**
	 * Handles the Enter key press.
	 *
	 * @param {Event} event The event object.
	 */
	const handleEnter = useCallback(
		( event ) => {
			if ( 'Enter' !== event.key ) {
				return;
			}

			// Avoid triggering the action when a button is focused, as this can cause a double submission.
			const isCancelOrDeleteButton = event.target === cancelButtonRef.current || event.target === deleteButtonRef.current;
			if ( ! isCancelOrDeleteButton ) {
				deleteButtonRef.current.click();
			}
		},
		[],
	);

	return (
		<Modal
			size="medium"
			icon={ <Icon icon={ trash } style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ title }
			isDismissible={ false }
			onKeyDown={ handleEnter }
			onRequestClose={ closeConfirmDeleteModal }
		>
			<Stack direction="column" gap="lg">
				<Text>
					{ _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', 1, 'tablepress' ) }
					<br />
					{ __( 'Deleting a table is permanent and can not be undone!', 'tablepress' ) }
				</Text>
				<Stack direction="row" gap="lg" justify="end">
					<Button
						ref={ cancelButtonRef }
						variant="minimal"
						onClick={ closeConfirmDeleteModal }
					>
						{ __( 'Cancel', 'tablepress' ) }
					</Button>
					<LegacyButton
						// This is a LegacyButton because the `@wordpress/ui` Button component does not support the "isDestructive" prop.
						__next40pxDefaultSize
						ref={ deleteButtonRef }
						variant="primary"
						isDestructive={ true }
						href={ deleteUrl }
						onClick={ closeConfirmDeleteModal }
					>
						{ __( 'Delete permanently', 'tablepress' ) }
					</LegacyButton>
				</Stack>
			</Stack>
		</Modal>
	);
};
