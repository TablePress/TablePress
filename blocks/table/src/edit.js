/**
 * JavaScript code for the TablePress table block in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps, InspectorControls, InspectorAdvancedControls } from '@wordpress/block-editor';
import { ComboboxControl, ExternalLink, Icon, PanelBody, Placeholder, TextControl } from '@wordpress/components';

/**
 * Get the block name from the block.json.
 */
import block from '../block.json';

/**
 * Load CSS code that only applies inside the block editor.
 */
import './editor.scss';

// Options for the table selection dropdown, in the form [ { value: <id>, label: <text> }, ... ].
const ComboboxControl_options = Object.entries( tp.tables ).map( ( [ id, name ] ) => {
	return {
		value: id,
		label: sprintf( __( 'ID %1$s: “%2$s”', 'tablepress' ), id, name ),
	};
} );

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   params               Function parameters.
 * @param {Object}   params.attributes    Block attributes.
 * @param {Function} params.setAttributes Function to set block attributes.
 * @return {WPElement} Element to render.
 */
export default function TablePressTableEdit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	let blockMarkup;
	if ( attributes.id && tp.tables.hasOwnProperty( attributes.id ) ) {
		blockMarkup = (
			<div { ...blockProps }>
				<ServerSideRender
					block={ block.name }
					attributes={ attributes }
					className="render-wrapper"
				/>
				<div className="table-overlay">
					{ sprintf( __( 'TablePress table %1$s: “%2$s”', 'tablepress' ), attributes.id, tp.tables[ attributes.id ] ) }
				</div>
			</div>
		);
	} else {
		// Show an error message if a table could not be found (e.g. after a table was deleted).
		let message_not_found = '';
		if ( attributes.id ) { // The tp.tables.hasOwnProperty( attributes.id ) check happens above.
			message_not_found = sprintf( __( 'There is a problem: The TablePress table with the ID “%1$s” could not be found.', 'tablepress' ), attributes.id ) + ' ';
		}

		blockMarkup = (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Icon icon="list-view" /> }
					label={ __( 'TablePress table', 'tablepress' ) }
					instructions={ message_not_found + __( 'Select the TablePress table that you want to embed in the Settings sidebar.', 'tablepress' ) }
				>
					{ ( '' !== tp.url ) && (
						<ExternalLink href={ tp.url }>
							{ __( 'Manage your tables.', 'tablepress' ) }
						</ExternalLink>
					) }
				</Placeholder>
			</div>
		);
	}

	const sidebarMarkup = (
		<>
			<InspectorControls>
				<PanelBody
					opened={ true }
				>
					<ComboboxControl
						label={ __( 'Table:', 'tablepress' ) }
						help={
							<>
								{ __( 'Select the TablePress table that you want to embed.', 'tablepress' ) }
								{ ( '' !== tp.url ) && ' ' }
								{ ( '' !== tp.url ) && (
									<ExternalLink href={ tp.url }>
										{ __( 'Manage your tables.', 'tablepress' ) }
									</ExternalLink>
								) }
							</>
						}
						value={ attributes.id }
						options={ ComboboxControl_options }
						onChange={ ( id ) => {
							id ??= '';
							setAttributes( { id: id.replace( /[^0-9a-zA-Z-_]/g, '' ) } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<TextControl
					label={ __( 'Configuration parameters:', 'tablepress' ) }
					help={ __( 'These additional parameters can be used to modify specific table features.', 'tablepress' ) + ' ' + __( 'See the TablePress Documentation for more information.', 'tablepress' ) }
					value={ attributes.parameters }
					onChange={ ( parameters ) => setAttributes( { parameters } ) }
				/>
			</InspectorAdvancedControls>
		</>
	);

	return (
		<>
			{blockMarkup}
			{sidebarMarkup}
		</>
	);
}
