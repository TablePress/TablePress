/**
 * JavaScript code for the "Edit" section integration of the "Other Actions".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useRef, useState } from 'react';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Icon,
	Modal,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { TablePressIcon } from '../../img/tablepress-icon';

/**
 * Returns the Confirm Delete Modal component's JSX markup.
 *
 * @param {Object}   props                         Component props.
 * @param {string}   props.title                   Title of the modal.
 * @param {string}   props.deleteUrl               URL to delete the table.
 * @param {Function} props.closeConfirmDeleteModal Callback to close the Confirm Delete modal.
 * @return {Object} Confirm Delete Modal component.
 */
const ConfirmDeleteModal = ( { title, deleteUrl, closeConfirmDeleteModal } ) => {
	const cancelButtonRef = useRef();
	const deleteButtonRef = useRef();

	const handleEnter = useCallback(
		( event ) => {
			// Avoid triggering the action when a button is focused, as this can cause a double submission.
			const isCancelOrDeleteButton =
				event.target === cancelButtonRef.current ||
				event.target === deleteButtonRef.current;

			if ( ! isCancelOrDeleteButton && 'Enter' === event.key ) {
				deleteButtonRef.current.click();
			}
		},
		[],
	);

	return (
		<Modal
			className="has-size-medium" // Using size="medium" is only possible in WP 6.5+.
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			isDismissible={ false }
			onKeyDown={ handleEnter }
			onRequestClose={ closeConfirmDeleteModal }
		>
			<VStack spacing={ 8 }>
				<span>
					{ _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', 1, 'tablepress' ) }
					<br />
					{ __( 'Deleting a table is permanent and can not be undone!', 'tablepress' ) }
				</span>
				<HStack alignment="right">
					<Button
						ref={ cancelButtonRef }
						variant="tertiary"
						text={ __( 'Cancel', 'tablepress' ) }
						onClick={ closeConfirmDeleteModal }
					/>
					<Button
						ref={ deleteButtonRef }
						variant="primary"
						isDestructive={ true }
						href={ deleteUrl }
						text={ __( 'Delete', 'tablepress' ) }
						onClick={ () => {
							// Prevent onunload warning, by calling the unset method.
							tp.helpers.unsaved_changes.unset();
							closeConfirmDeleteModal();
						} }
					/>
				</HStack>
			</VStack>
		</Modal>
	);
};

const Section = ( { screenData, tableMeta } ) => {
	const [ confirmDeleteModalIsOpen, setConfirmDeleteModalIsOpen ] = useState( false );

	if ( ! ( tp.screenOptions.currentUserCanCopyTable || tp.screenOptions.currentUserCanExportTable || tp.screenOptions.currentUserCanDeleteTable ) ) {
		return <></>;
	}

	return (
		<HStack
			alignment="left"
			style={ { margin: '2rem 0' } }
		>
			<span>{ __( 'Other Actions', 'tablepress' ) }</span>
			{ tp.screenOptions.currentUserCanCopyTable && (
				<Button
					variant="secondary"
					size="compact"
					href={ screenData.copyUrl }
					text={ __( 'Copy Table', 'tablepress' ) }
				/>
			) }
			{ tp.screenOptions.currentUserCanExportTable && (
				<Button
					variant="secondary"
					size="compact"
					href={ screenData.exportUrl }
					text={ __( 'Export Table', 'tablepress' ) }
				/>
			) }
			{ tp.screenOptions.currentUserCanDeleteTable && (
				<>
					<Button
						variant="secondary"
						size="compact"
						isDestructive={ true }
						href={ screenData.deleteUrl }
						text={ __( 'Delete Table', 'tablepress' ) }
						onClick={ ( event ) => {
							setConfirmDeleteModalIsOpen( true );
							event.preventDefault();
						} }
					/>
					{ confirmDeleteModalIsOpen && (
						<ConfirmDeleteModal
							title={ sprintf( __( 'Delete “%1$s” (ID %2$s)', 'tablepress' ), tableMeta.name, tableMeta.id ) }
							deleteUrl={ screenData.deleteUrl }
							closeConfirmDeleteModal={ () => setConfirmDeleteModalIsOpen( false ) }
						/>
					) }
				</>
			) }
		</HStack>
	);
};

initializeReactComponentInPortal(
	'other-actions',
	'edit',
	Section,
);
