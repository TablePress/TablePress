/**
 * JavaScript code for the "Table Preview" on the "Edit" and "List of Tables" screens.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import {
	Button as LegacyButton,
	Modal,
} from '@wordpress/components';
import { external } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	Icon,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { TablePressIcon } from '../../img/icons';

/**
 * Returns the Table Preview modal's JSX markup.
 *
 * @param {Object}   props                Function parameters.
 * @param {Function} props.onRequestClose Callback to close the preview.
 * @param {string}   props.srcDoc         HTML content for the preview. (Optional, only for previewing unsaved changes.)
 * @param {string}   props.title          Title of the preview.
 * @param {string}   props.url            URL of the preview. (Optional, only for previewing saved changes.)
 * @return {Object} Table Preview modal component.
 */
export const TablePreview = ( { onRequestClose, srcDoc = '', title, url } ) => {
	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ title }
			className="table-preview-modal"
			onRequestClose={ onRequestClose }
			size="fill"
			headerActions={ '' === srcDoc && '' !== url && (
				<LegacyButton
					// This is a LegacyButton because the `@wordpress/ui` Button/ButtonLink/IconButton components can not yet properly recreate this.
					icon={ external }
					size="compact"
					label={ __( 'Open the preview in a new tab', 'tablepress' ) }
					href={ url }
					target="_blank"
				/>
			) }
		>
			<iframe
				title={ title }
				src={ '' === srcDoc ? url : undefined }
				srcDoc={ '' !== srcDoc ? srcDoc : undefined }
			/>
		</Modal>
	);
};
