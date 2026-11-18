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
import { useBlockProps, InspectorControls, InspectorAdvancedControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Placeholder,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import shortcode from '@wordpress/shortcode';
import {
	Icon,
	InputControl,
	Link,
	SearchableSelect,
	SearchableSelectControl,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { shortcodeAttrsToString } from './common/functions';
import TablePressTableIcon from './icon';

/**
 * Get the block name from the block.json.
 */
import block from '../block.json';

/**
 * Load CSS code that only applies inside the block editor.
 */
import './editor.scss';

// Create an array of objects for the table selection dropdown, in the form [ { value: <id>, label: <text> }, ... ].
const availableTables = Object.entries( tp.tables ).map( ( [ value, label ] ) => ( { value, label } ) );

/**
 * Custom component for the "Manage your tables." link.
 *
 * @param {Object} props Component props.
 * @return {Object} ManageTablesLink component.
 */
const ManageTablesLink = ( props ) => {
	return (
		( '' !== tp.url ) && (
			<Link
				href={ tp.url }
				openInNewTab={ true }
				{ ...props }
			>
				{ __( 'Manage your tables.', 'tablepress' ) }
			</Link>
		)
	);
}

/**
 * Custom component for the table selection dropdown.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.tableId    The ID of the selected table.
 * @param {Function} props.setTableId Function to set the selected table ID.
 * @param {Object}   props.restProps  Additional props to pass to the SearchableSelectControl component.
 * @return {Object} TableSelectControl component.
 */
const TableSelectControl = ( { tableId, setTableId, ...restProps } ) => {
	/*
	 * Use the `SearchableSelect` component instead of the `SearchableSelectControl` component if a trigger is provided.
	 * This is the case when tp.is_old_wp is true, which means that the legacy CSS for the table selection dropdown needs to be loaded.
	 * The `SearchableSelect` component is then used so that the label is hidden.
	 */
	const Component = ( restProps.trigger ) ? SearchableSelect : SearchableSelectControl;

	return (
		<Component
			label={ __( 'Table:', 'tablepress' ) }
			placeholder={ __( 'Select table', 'tablepress' ) }
			searchPlaceholder={ __( 'Search table', 'tablepress' ) }
			value={ ( tableId && tp.tables.hasOwnProperty( tableId ) ) ? availableTables.find( ( item ) => item.value === tableId ) : null }
			items={ availableTables }
			onValueChange={ ( { value: id } ) => setTableId( id ) }
			autoHighlight={ true }
			itemToStringLabel={ ( item ) => sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), item.value, item.label ) } // This function's return value is used for the component (trigger) label and for the search functionality.
			emptyContent={ __( 'No tables found.', 'tablepress' ) }
			{ ...restProps }
		>
			{
				( item ) => (
					<Component.Item key={ item.value } value={ item }>
						<Component.ItemLabel>
							{
								createInterpolateElement(
									/* translators: %1$s: Table ID, %2$s: Table name */
									sprintf( __( 'ID <strong>%1$s</strong>: %2$s', 'tablepress' ), item.value, item.label ),
									{
										strong: <strong />,
									},
								)
							}
						</Component.ItemLabel>
					</Component.Item>
				)
			}
		</Component>
	);
};

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
		let instructions = '';
		if ( 0 === availableTables.length ) {
			instructions = __( 'There are no TablePress tables on this site yet.', 'tablepress' );
		} else if ( attributes.id ) {
			// Show an error message if a table could not be found (e.g. after a table was deleted). The tp.tables.hasOwnProperty( attributes.id ) check happens above.
			/* translators: %1$s: Table ID */
			instructions = sprintf( __( 'There is a problem: The TablePress table with the ID “%1$s” could not be found.', 'tablepress' ), attributes.id );
		}
		blockMarkup = (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Icon icon={ TablePressTableIcon } /> }
					label={ __( 'TablePress table', 'tablepress' ) }
					instructions={ '' !== instructions ? instructions : '' }
					isColumnLayout={ true }
				>
					{
						( 0 < availableTables.length ) && (
							<>
								<Text>
									{ __( 'Select the TablePress table that you want to embed.', 'tablepress' ) }
								</Text>
								<TableSelectControl
									tableId={ attributes.id }
									setTableId={ ( id ) => setAttributes( { id } ) }
									hideLabelFromVision={ true }
									aria-label={ ( tp.is_old_wp ) ? __( 'Table:', 'tablepress' ) : undefined }
									trigger={
										// Load legacy CSS for the table selection dropdown in WordPress versions < 7.1.
										( tp.is_old_wp )
										?
											<SearchableSelect.Trigger
												style={ {
													display: 'flex',
													width: '100%',
													height: '40px',
													justifyContent: 'space-between',
													alignItems: 'center',
													color: '#1e1e1e',
													backgroundColor: '#ffffff',
													border: '1px solid #8d8d8d',
													borderRadius: '2px',
													padding: '4px 8px 4px 12px',
													outlineOffset: '3.5px',
												} }
											/>
										: undefined
									}
								/>
							</>
						)
					}
					<ManageTablesLink
						style={ {
							alignSelf: 'start',
						} }
					/>
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
					{ 0 < availableTables.length
						?
						<TableSelectControl
							tableId={ attributes.id }
							setTableId={ ( id ) => setAttributes( { id } ) }
							description={
								<>
									{ __( 'Select the TablePress table that you want to embed.', 'tablepress' ) }
									{ '' !== tp.url && ' ' }
									<ManageTablesLink />
								</>
							}
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
					<InputControl
						label={ __( 'Configuration parameters:', 'tablepress' ) }
						description={
							createInterpolateElement(
								__( 'These additional parameters can be used to modify specific table features.', 'tablepress' )
								+ ' '
								+ __( 'See the <a>TablePress Documentation</a> for more information.', 'tablepress' ),
								{
									a: <Link href="https://tablepress.org/faq/configuration-parameter-reference/" />,
								},
							)
						}
						value={ attributes.parameters }
						onValueChange={ ( parameters ) => {
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
