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
	Button,
	Icon,
	Modal,
} from '@wordpress/components';
import { help } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

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
			<Button
				variant="secondary"
				size="small"
				onClick={ openModal }
				text={ __( 'Help', 'tablepress' ) }
				{ ...buttonProps }
			/>
			{ modalOpen && (
				<Modal
					className="has-size-small" // Using size="small" is only possible in WP 6.5+.
					icon={ <Icon icon={ help } style={ { display: 'flex', marginRight: '4px' } }/> }
					title={ title }
					onRequestClose={ closeModal }
					{ ...modalProps }
				>
					{ children }
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
		document.getElementById( `tablepress-${section}-section` ).closest( '.postbox' ).querySelector( '.handle-actions' ).prepend( helpContainer.current );
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
