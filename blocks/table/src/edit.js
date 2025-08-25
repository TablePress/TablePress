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
import { shortcodeAttrsToString } from './common/functions';
import TablePressTableIcon from './icon';

/**
 * Load CSS code that only applies inside the block editor.
 */
import './editor.scss';

// Options for the table selection dropdown, in the form [ { value: <id>, label: <text> }, ... ].
const ComboboxControlOptions = Object.entries( tp.tables ).map( ( [ id, name ] ) => {
	return {
		value: id,
		/* translators: %1$s: Table ID, %2$s: Table name */
		label: sprintf( __( 'ID %1$s: “%2$s”', 'tablepress' ), id, name ),
	};
} );

/**
 * Custom component for the "Manage your tables." link.
 */
const ManageTablesLink = function () {
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
 * @return {Element} Element to render.
 */
const TablePressTableEdit = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();

	let blockMarkup;
	if ( attributes.id && tp.tables.hasOwnProperty( attributes.id ) ) {
		blockMarkup = (
			<div { ...blockProps }>
				{ tp.load_block_preview &&
					<ServerSideRender
						block={ block.name }
						attributes={ {
							id: attributes.id,
							parameters: `block_preview=true ${ attributes.parameters }`.trim(), // Set the `block_preview` parameter to allow detecting that this is a block preview.
						} }
						className="render-wrapper"
					/>
				}
				<div className="table-overlay">
					{/* translators: %1$s: Table ID, %2$s: Table name */}
					{ sprintf( __( 'TablePress table %1$s: “%2$s”', 'tablepress' ), attributes.id, tp.tables[ attributes.id ] ) }
				</div>
			</div>
		);
	} else {
		let instructions = 0 < ComboboxControlOptions.length ? __( 'Select the TablePress table that you want to embed in the Settings sidebar.', 'tablepress' ) : __( 'There are no TablePress tables on this site yet.', 'tablepress' );
		if ( attributes.id ) {
			// Show an error message if a table could not be found (e.g. after a table was deleted). The tp.tables.hasOwnProperty( attributes.id ) check happens above.
			/* translators: %1$s: Table ID */
			instructions = sprintf( __( 'There is a problem: The TablePress table with the ID “%1$s” could not be found.', 'tablepress' ), attributes.id ) + ' ' + instructions;
		}
		blockMarkup = (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Icon icon={ TablePressTableIcon } /> }
					label={ __( 'TablePress table', 'tablepress' ) }
					instructions={ instructions }
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
					{ 0 < ComboboxControlOptions.length
						?
						<ComboboxControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Table:', 'tablepress' ) }
							help={
								<>
									{ __( 'Select the TablePress table that you want to embed.', 'tablepress' ) }
									{ '' !== tp.url && ' ' }
									<ManageTablesLink />
								</>
							}
							value={ attributes.id }
							options={ ComboboxControlOptions }
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
						__nextHasNoMarginBottom
						__next40pxDefaultSize
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
									return ' ' + shortcodeAttrsToString( shortcodeAttrs ) + ' '; // Add spaces around replacement text to have separation to possibly already existing parameters.
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
			{ sidebarMarkup }
		</>
	);
};

export default TablePressTableEdit;
