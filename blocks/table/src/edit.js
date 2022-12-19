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
import shortcode from '@wordpress/shortcode';

/**
 * Get the block name from the block.json.
 */
import block from '../block.json';

/**
 * Internal dependencies.
 */
import { shortcode_attrs_to_string } from './_common-functions';

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
 * Custom component for the "Manage your tables." link.
 */
const ManageTablesLink = function() {
	return (
		'' !== tp.url &&
			<ExternalLink href={ tp.url }>
				{ __( 'Manage your tables.', 'tablepress' ) }
			</ExternalLink>
	);
}

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
				{ tp.load_block_preview &&
					<ServerSideRender
						block={ block.name }
						attributes={ attributes }
						className="render-wrapper"
					/>
				}
				<div className="table-overlay">
					{ sprintf( __( 'TablePress table %1$s: “%2$s”', 'tablepress' ), attributes.id, tp.tables[ attributes.id ] ) }
				</div>
			</div>
		);
	} else {
		blockMarkup = (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Icon icon="list-view" /> }
					label={ __( 'TablePress table', 'tablepress' ) }
					instructions={
						<>
							{ /* Show an error message if a table could not be found (e.g. after a table was deleted).
							     The tp.tables.hasOwnProperty( attributes.id ) check happens above. */ }
							{ attributes.id && sprintf( __( 'There is a problem: The TablePress table with the ID “%1$s” could not be found.', 'tablepress' ), attributes.id ) + ' ' }

							{ 0 < ComboboxControl_options.length ? __( 'Select the TablePress table that you want to embed in the Settings sidebar.', 'tablepress' ) : __( 'There are no TablePress tables on this site yet.', 'tablepress' ) }
						</>
					}
				>
					<ManageTablesLink />
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
					{ 0 < ComboboxControl_options.length
						?
						<ComboboxControl
							label={ __( 'Table:', 'tablepress' ) }
							help={
								<>
									{ __( 'Select the TablePress table that you want to embed.', 'tablepress' ) }
									{ '' !== tp.url && ' ' }
									<ManageTablesLink />
								</>
							}
							value={ attributes.id }
							options={ ComboboxControl_options }
							onChange={ ( id ) => {
								id ??= '';
								setAttributes( { id: id.replace( /[^0-9a-zA-Z-_]/g, '' ) } );
							} }
						/>
						:
						<>
							{ __( 'There are no TablePress tables on this site yet.', 'tablepress' ) }
							{ '' !== tp.url && ' ' }
							<ManageTablesLink />
						</>
					}
				</PanelBody>
			</InspectorControls>
			{ attributes.id && tp.tables.hasOwnProperty( attributes.id ) &&
				<InspectorAdvancedControls>
					<TextControl
						label={ __( 'Configuration parameters:', 'tablepress' ) }
						help={ __( 'These additional parameters can be used to modify specific table features.', 'tablepress' ) + ' ' + __( 'See the TablePress Documentation for more information.', 'tablepress' ) }
						value={ attributes.parameters }
						onChange={ ( parameters ) => {
							parameters = shortcode.replace(
								tp.table.shortcode,
								parameters,
								( { attrs: shortcodeAttrs } ) => {
									shortcodeAttrs = { named: { ...shortcodeAttrs.named }, numeric: [ ...shortcodeAttrs.numeric ] }; // Use object destructuring to get a clone of the object.
									delete shortcodeAttrs.named.id;
									return ' ' + shortcode_attrs_to_string( shortcodeAttrs ) + ' '; // Add spaces around replacement text to have separation to possibly already existing parameters.
								}
							);
							parameters = parameters.replace( /=“([^”]*)”/g, '="$1"' ); // Replace curly quotation marks around a value with normal ones.
							setAttributes( { parameters } );
						} }
						onBlur={ ( event ) => {
							const parameters = event.target.value.trim(); // Remove leading and trailing whitespace from the parameter string.
							setAttributes( { parameters } );
						} }
					/>
				</InspectorAdvancedControls>
			}
		</>
	);

	return (
		<>
			{ blockMarkup }
			{ sidebarMarkup}
		</>
	);
}
