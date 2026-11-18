/**
 * JavaScript code for the HelpBox and Help components.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import {
	Modal,
} from '@wordpress/components';
import { help } from '@wordpress/icons';
import {
	Icon,
	// IconButton, // Due to compatibility issues with WP < 7.0, we use a custom `IconButton` component instead of the one from the `@wordpress/ui` package.
	Stack,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { IconButton } from './icon-button';

/**
 * Returns the HelpBox component's JSX markup.
 *
 * @param {Object} props             Function parameters.
 * @param {string} props.title       The title of the HelpBox.
 * @param {Object} props.buttonProps Additional props for the Button.
 * @param {Object} props.modalProps  Additional props for the Modal.
 * @param {Object} props.children    The Help content.
 * @return {Object} HelpBox component.
 */
export const HelpBox = ( { title, buttonProps= {}, modalProps = {}, children } ) => {
	const [ modalOpen, setModalOpen ] = useState( false );
	const openModal = () => setModalOpen( true );
	const closeModal = () => setModalOpen( false );

	return (
		<>
			<IconButton
				variant="minimal"
				icon={ help }
				size="small"
				onClick={ openModal }
				{ ...buttonProps }
			/>
			{ modalOpen && (
				<Modal
					size="small"
					icon={ <Icon icon={ help } style={ { display: 'flex', marginInlineEnd: '1em', fill: 'none' } }/> }
					title={ title }
					onRequestClose={ closeModal }
					{ ...modalProps }
				>
					<Stack direction="column" gap="md">
						{ children }
					</Stack>
				</Modal>
			) }
		</>
	);
};

/**
 * Returns the Help component's JSX markup.
 *
 * @param {Object} props             Function parameters.
 * @param {string} props.section     The section on the "Edit" screen.
 * @param {string} props.title       The title of the module.
 * @param {Object} props.buttonProps Additional props for the Button.
 * @param {Object} props.modalProps  Additional props for the Modal.
 * @param {Object} props.children    The Help content.
 * @return {Object} Help component.
 */
export const Help = ( { section, title, buttonProps = {}, modalProps = {}, children } ) => {
	// Store a reference to the Help Box container, which is moved in the DOM, to hold the Portal.
	const helpContainer = useRef( null );

	// Create a container for the Help Box and move it to the desired position in the DOM.
	if ( ! helpContainer.current ) {
		helpContainer.current = document.createElement( 'div' );
		helpContainer.current.className = 'help-container';
		document.getElementById( `tablepress-${ section }-section` ).closest( '.postbox' ).querySelector( 'h2.hndle' ).appendChild( helpContainer.current );
	}

	return createPortal(
		<HelpBox
			title={ title }
			buttonProps={ buttonProps }
			modalProps={ modalProps }
		>
			{ children }
		</HelpBox>,
		helpContainer.current,
	);
};
