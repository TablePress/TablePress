/**
 * JavaScript code for the "Edit" section integration of the "Header Bar".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.3.0
 */

/**
 * WordPress dependencies.
 */
import { useMemo, useState } from 'react';
import {
	Button as LegacyButton,
	KeyboardShortcuts,
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { arrowUp, blockTable, box, copy, download, info, moreVertical, settings, shortcode, siteLogo, trash } from '@wordpress/icons';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import { store as noticesStore } from '@wordpress/notices';
import {
	// IconButton, // Due to compatibility issues with WP < 7.0, we use a custom `IconButton` component instead of the one from the `@wordpress/ui` package.
	Menu,
	Spinner,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';
import { TablePressIconSimple } from '../../img/icons';
import { ConfirmDeleteModal } from '../common/modals';
import { IconButton } from '../common/icon-button';
import { keyboardShortcutHelper, showNotice } from '../common/functions';
import { NoticeUI } from '../common/notice-ui';
import { saveTableChanges, showPreview } from './callbacks';
import { useScreenSettings, useUpdateScreenSettings } from './context/screen-settings';
import { useTableMeta, useUpdateTableMeta } from './context/table-meta';
import { useTableOptions } from './context/table-options';

/**
 * Scrolls the window to the top of the page with a smooth animation.
 */
const scrollToTop = () => window.scrollTo( { left: 0, top: 0, behavior: 'smooth' } );

/**
 * Scrolls the window to the specified section of the page with a smooth animation.
 *
 * @param {string} section Section to scroll to.
 */
const scrollToSection = ( section ) => {
	const element = document.getElementById( `tablepress_edit-${ section }` );
	if ( ! element ) {
		return;
	}

	element.style.scrollMarginInlineStart = '200px'; // Ensure that we are scrolling to the left edge of the table editor.
	element.scrollIntoView( { behavior: 'smooth', block: 'start', inline: 'start' } );
	element.style.removeProperty( 'scroll-margin-inline-start' );
};

/**
 * Returns the Header Bar component's JSX markup.
 *
 * @param {Object} props          Component props.
 * @param {Array}  props.features The feature modules that are enabled on the site.
 * @return {Object} Header Bar component.
 */
const HeaderBar = ( { features } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const { copyUrl, deleteUrl, exportUrl, fullscreenIsActive, isSaving, previewIsLoading, previewUrl } = useScreenSettings();
	const updateScreenSettings = useUpdateScreenSettings();
	const tableMeta = useTableMeta();
	const updateTableMeta = useUpdateTableMeta();
	const tableOptions = useTableOptions();
	const [ quickNavigationDropdownIsOpen, setQuickNavigationDropdownIsOpen ] = useState( false );
	const [ moreActionsMenuDropdownIsOpen, setMoreActionsMenuDropdownIsOpen ] = useState( false );
	const [ confirmDeleteModalIsOpen, setConfirmDeleteModalIsOpen ] = useState( false );

	// Copy shortcode to clipboard.
	const shortcodeText = `[${ tp.table.shortcode } id=${ tableMeta.id } /]`;
	const copyShortcodeButtonRef = useCopyToClipboard( shortcodeText, () => {
		showNotice(
			{
				status: 'success',
				type: 'snackbar',
				content: __( 'Copied Shortcode to clipboard.', 'tablepress' ),
			},
			noticesStoreDispatch
		);
	} );

	/**
	 * Toggles the Quick Navigation dropdown menu.
	 *
	 * @param {Event} event The keyboard event that triggered the callback.
	 */
	const toggleNavigationDropdownCallback = ( event ) => {
		event.preventDefault();
		setQuickNavigationDropdownIsOpen( ! quickNavigationDropdownIsOpen );
	};

	const featureModules = useMemo( () => (
		Object.keys( tp?.modules ?? {} )
			.filter( ( module ) => features.includes( module ) )
			.map( ( module ) => ( {
				module,
				name: tp.modules[ module ].name,
			} ) )
			.sort( ( a, b ) => a.name.localeCompare( b.name ) )
	), [ features ] );

	return (
		<>
			<Stack direction="column">
				<Stack
					direction="row"
					gap="md"
					justify="space-between"
					align="center"
					wrap="wrap"
					className="tablepress-header-bar-wrapper"
				>
					<Stack direction="row" gap="md" align="center">
						{ /* Left side: Quick Navigation, Table ID, Table name */ }
						<Menu.Root
							modal={ false }
							open={ ! fullscreenIsActive && quickNavigationDropdownIsOpen } // Force the dropdown to be closed in fullscreen mode as it would hover above the fullscreen overlay.
							onOpenChange={ ( isOpen ) => setQuickNavigationDropdownIsOpen( isOpen ) }
						>
							<Menu.Trigger
								render={
									<IconButton
										variant="minimal"
										size="compact"
										icon={ TablePressIconSimple }
										label={ __( 'Quick navigation', 'tablepress' ) }
										shortcut={ keyboardShortcutHelper( 'primary', 'j' ) }
										onDoubleClick={ () => scrollToTop() } /* Scroll to Top on Double-click. (Double-click will also close the dropdown.) */
										tone="neutral"
									/>
								}
							/>
							<Menu.Popup
								positioner={ <Menu.Positioner sideOffset={ 12 } /> } // Increase the offset to not have the top border be right on top of the bottom border of the header bar.
								style={
									// Allow the menu to be higher than the default, so that more feature modules fit.
									featureModules.length > 0 && {
										maxHeight: 'min(var(--available-height), 80dvh)',
									}
								}
							>
								<Menu.Item
									prefix={ <Menu.PrefixIcon icon={ arrowUp } /> }
									onClick={ () => scrollToTop() }
								>
									<Menu.ItemLabel>
										{ __( 'Scroll to Top', 'tablepress' ) }
									</Menu.ItemLabel>
								</Menu.Item>
								<Menu.Separator />
								<Menu.Group>
									{ featureModules.length > 0 && (
										<Menu.GroupLabel>
											{ __( 'Common', 'tablepress' ) }
										</Menu.GroupLabel>
									) }
									{
											[
												{ section: 'table-information', label: __( 'Table Information', 'tablepress' ), icon: info },
												{ section: 'table-data', label: __( 'Table Content', 'tablepress' ), icon: blockTable },
												{ section: 'table-options', label: __( 'Table Options', 'tablepress' ), icon: settings },
												{ section: 'datatables-features', label: __( 'Table Features for Site Visitors', 'tablepress' ), icon: siteLogo },
											].map( ( { section, label, icon } ) => (
												<Menu.Item
													key={ section }
													prefix={ <Menu.PrefixIcon icon={ icon } /> }
													onClick={ () => scrollToSection( section ) }
												>
													<Menu.ItemLabel>
														{ label }
													</Menu.ItemLabel>
												</Menu.Item>
											) )
										}
								</Menu.Group>
								{
									featureModules.length > 0 && (
										<>
											<Menu.Separator />
											<Menu.Group>
												<Menu.GroupLabel>
													{ __( 'Feature Modules', 'tablepress' ) }
												</Menu.GroupLabel>
												{ featureModules.map( ( { module, name } ) => (
													<Menu.Item
														key={ module }
														prefix={ <Menu.PrefixIcon icon={ box } style={ { color: 'transparent' } } /> } // The icon is needed as a placeholder to align the labels.
														onClick={ () => scrollToSection( module ) }
													>
														<Menu.ItemLabel>
															{ name }
														</Menu.ItemLabel>
													</Menu.Item>
												) ) }
											</Menu.Group>
										</>
									)
								}
							</Menu.Popup>
						</Menu.Root>
						<Text
							className="tablepress-header-table-id"
							style={ { flexShrink: 0 } }>
							{ sprintf( __( 'ID: %s', 'tablepress' ), tableMeta.id ) }
						</Text>
						<h2 className="tablepress-header-table-name">
							{ tableMeta.name }
						</h2>
					</Stack>
					<Stack
						direction="row"
						gap="md"
						align="center"
						style={ {
							marginInlineStart: '16px',
						} }
					>
						{ /* Right side: Spinner, Preview, Save Changes, Dropdown */ }
						{
							( isSaving || previewIsLoading ) ? (
								<Spinner />
							) : (
								<div style={ { width: '16px', flexShrink: 0 } } />
							)
						}
						{
							tp.screenOptions.currentUserCanPreviewTable && (
								<LegacyButton
									// This is a LegacyButton because the `@wordpress/ui` Button component does not support the "shortcut" prop.
									variant="secondary"
									href={ previewUrl }
									shortcut={ previewIsLoading ? undefined :
										{
											ariaLabel: shortcutAriaLabel.primary( 'p' ),
											display: displayShortcut.primary( 'p' ),
										}
									}
									isBusy={ previewIsLoading }
									disabled={ previewIsLoading }
									accessibleWhenDisabled={ true }
									onClick={ ( event ) => {
										// Open the Preview Modal if the button is clicked, except if an unmodified preview in a new tab is requested.
										if ( tp.made_changes || ! ( event.ctrlKey || event.metaKey || event.shiftKey ) ) {
											event.preventDefault();
											showPreview( { noticesStoreDispatch, tableOptions, tableMeta, updateScreenSettings } );
										}
									} }
								>
									{ __( 'Preview', 'tablepress' ) }
								</LegacyButton>
							)
						}
						<LegacyButton
							// This is a LegacyButton because the `@wordpress/ui` Button component does not support the "shortcut" prop.
							variant="primary"
							shortcut={ isSaving ? undefined :
								{
									ariaLabel: shortcutAriaLabel.primary( 's' ),
									display: displayShortcut.primary( 's' ),
								}
							}
							isBusy={ isSaving }
							disabled={ isSaving }
							accessibleWhenDisabled={ true }
							onClick={ () => saveTableChanges( { noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings, updateTableMeta } ) }
						>
							{ __( 'Save Changes', 'tablepress' ) }
						</LegacyButton>
						<Menu.Root
							modal={ false }
							open={ ! fullscreenIsActive && moreActionsMenuDropdownIsOpen } // Force the dropdown to be closed in fullscreen mode as it would hover above the fullscreen overlay.
							onOpenChange={ ( isOpen ) => setMoreActionsMenuDropdownIsOpen( isOpen ) }
						>
							<Menu.Trigger
								render={
									<IconButton
										variant="minimal"
										size="compact"
										icon={ moreVertical }
										label={ __( 'More actions', 'tablepress' ) }
										tone="neutral"
									/>
								}
							/>
							<Menu.Popup
								positioner={ <Menu.Positioner sideOffset={ 12 } /> } // Increase the offset to not have the top border be right on top of the bottom border of the header bar.
								className="tablepress-more-actions-menu"
							>
								<Menu.Item
									ref={ copyShortcodeButtonRef }
									prefix={ <Menu.PrefixIcon icon={ shortcode } /> }
									className="shifted-description"
								>
									<Menu.ItemLabel>
										{ __( 'Copy Shortcode', 'tablepress' ) }
									</Menu.ItemLabel>
									<Menu.ItemDescription>
										{ shortcodeText }
									</Menu.ItemDescription>
								</Menu.Item>
								{ ( tp.screenOptions.currentUserCanCopyTable || tp.screenOptions.currentUserCanExportTable ) && (
									<>
										<Menu.Separator />
										{ tp.screenOptions.currentUserCanCopyTable && (
											<Menu.LinkItem
												prefix={ <Menu.PrefixIcon icon={ copy } /> }
												href={ copyUrl }
											>
												<Menu.ItemLabel>
													{ __( 'Copy Table', 'tablepress' ) }
												</Menu.ItemLabel>
											</Menu.LinkItem>
										) }
										{ tp.screenOptions.currentUserCanExportTable && (
											<Menu.LinkItem
												prefix={ <Menu.PrefixIcon icon={ download } /> }
												href={ exportUrl }
											>
												<Menu.ItemLabel>
													{ __( 'Export Table', 'tablepress' ) }
												</Menu.ItemLabel>
											</Menu.LinkItem>
										) }
									</>
								) }
								{ tp.screenOptions.currentUserCanDeleteTable && (
									<>
										<Menu.Separator />
										<Menu.Item
											prefix={ <Menu.PrefixIcon icon={ trash } style={ { color: '#cc1818' } } /> }
											onClick={ () => setConfirmDeleteModalIsOpen( true ) }
											style={ { color: '#cc1818' } }
										>
											<Menu.ItemLabel>
												{ __( 'Delete table', 'tablepress' ) }
											</Menu.ItemLabel>
										</Menu.Item>
									</>
								) }
							</Menu.Popup>
						</Menu.Root>
					</Stack>
				</Stack>
				{ ! fullscreenIsActive && (
					<NoticeUI noticeType="default" />
				) }
				{ confirmDeleteModalIsOpen && (
					<ConfirmDeleteModal
						title={ sprintf( __( 'Delete “%1$s” (ID %2$s)', 'tablepress' ), tableMeta.name, tableMeta.id ) }
						deleteUrl={ deleteUrl }
						closeConfirmDeleteModal={ () => {
							// Prevent onunload warning, by calling the unset method.
							tp.helpers.unsaved_changes.unset();
							setConfirmDeleteModalIsOpen( false );
						} }
					/>
				) }
			</Stack>
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ {
					'mod+j': toggleNavigationDropdownCallback,
				} }
			/>
		</>
	);
};

initializeReactComponentInPortal(
	'header-bar',
	'edit',
	HeaderBar,
);
