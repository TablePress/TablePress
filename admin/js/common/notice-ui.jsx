/**
 * JavaScript code for the "Edit" section integration of notices.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import {
	SnackbarList,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import {
	Notice,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { IconButton } from './icon-button';

/**
 * Returns the NoticeUI component's JSX markup.
 *
 * @param {Object} props            Component props.
 * @param {string} props.noticeType Type of notices to display ('snackbar' or 'default').
 * @return {Object} NoticeUI component.
 */
export const NoticeUI = ( { noticeType } ) => {
	const { removeNotice } = useDispatch( noticesStore );
	const allNotices = useSelect(
		( select ) => select( noticesStore ).getNotices( 'tablepress' ),
		[],
	);

	const filteredNotices = allNotices.filter( ( { type } ) => noticeType === type );

	if ( 'snackbar' === noticeType ) {
		return (
			<SnackbarList
				notices={ filteredNotices }
				onRemove={ ( id ) => removeNotice( id, 'tablepress' ) }
			/>
		);
	}

	if ( 'default' === noticeType ) {
		return (
			<Notice.List
				className="tablepress-notice-list"
			>
				{ filteredNotices.reverse().map( ( { id, content, details, isDismissible, status } ) => (
					<Notice.Root
						key={ id }
						intent={ status }
						politeness={ 'error' === status ? 'assertive' : 'polite' }
						className={ `tablepress-notice notice-${ id }` }
					>
						{
							// There is no dedicated `title` property for notices, so we repurpose the `details` property.
							( details ) && (
								<Notice.Title>
									{ details }
								</Notice.Title>
							)
						}
						<Notice.Description>
							<RawHTML>{ content }</RawHTML>
						</Notice.Description>
						{
							( isDismissible ) && (
								/*
								 * The `CloseIconButton` component depends on the native `IconButton` component, which is not compatible with older versions of WordPress.
								 * We use a custom `IconButton` component instead for now.
								 */
								<IconButton
									onClick={ () => removeNotice( id, 'tablepress' ) }
									variant="minimal"
									size="small"
									tone="neutral"
									icon={ closeSmall }
									label={ __( 'Dismiss', 'default' ) } // eslint-disable-line @wordpress/i18n-text-domain
									style={ {
										gridColumn: 3,
										gridRow: 1,
										marginInlineStart: 'var(--wpds-dimension-gap-xs)',
									} }
								/>
								/*
								// @todo Replace the `<IconButton />` component above with the `<Notice.CloseIconButton />` component once possible.
								<Notice.CloseIconButton
									onClick={ () => removeNotice( id, 'tablepress' ) }
								/>
								*/
							)
						}
					</Notice.Root>
				) ) }
			</Notice.List>
		);
	}

	return <></>;
};
