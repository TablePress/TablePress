<?php
/**
 * TablePress environment compatibility check.
 *
 * Note: This file must not contain PHP code that does not run on PHP < 7.4!
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 2.2.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Check if the site is using WordPress 6.2 or newer.
 */
// Include an unmodified $wp_version.
require ABSPATH . WPINC . '/version.php';
if ( version_compare( str_replace( '-src', '', $wp_version ), '6.2', '<' ) ) { // @phpstan-ignore variable.undefined ($wp_version is a global variable, defined in the included file.)
	/**
	 * Show an error notice to admins, if the installed version of WordPress is not supported.
	 *
	 * @since 2.2.0
	 *
	 * @phpstan-ignore missingType.return
	 */
	function tablepress_admin_error_notice_minimum_version_wp() /* No return type declaration, due to required PHP compatibility! */ {
		?>
		<div class="notice notice-error notice-alt notice-large">
			<h3><em>
				<span aria-hidden="true" class="dashicons dashicons-warning" style="color:#d63638;vertical-align:bottom"></span>
				<?php _e( 'Attention: Unfortunately, there is a problem!', 'tablepress' ); ?>
			</em></h3>
			<p style="font-size:14px">
				<?php _e( 'The installed version of WordPress is too old for the TablePress plugin! TablePress requires an up-to-date version!', 'tablepress' ); ?>
			</p>
			<p style="font-size:14px">
				<strong>
				<?php
				if ( current_user_can( 'update_core' ) ) {
					printf( __( 'Please <a href="%1$s">update your WordPress installation</a> to at least version %2$s!', 'tablepress' ), esc_url( self_admin_url( 'update-core.php' ) ), '6.2' );
				} else {
					printf( __( 'Please ask your site’s administrator to update WordPress to at least version %1$s!', 'tablepress' ), '6.2' );
				}
				?>
				</strong>
			</p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tablepress_admin_error_notice_minimum_version_wp' );

	// Exit TablePress early.
	return false;
}

/**
 * Check if the server is running PHP 7.4 or newer.
 */
if ( PHP_VERSION_ID < 70400 ) { // @phpstan-ignore smaller.alwaysFalse (PHPStan thinks that the Composer minimum version will always be fulfilled.)
	/**
	 * Show an error notice to admins, if the installed version of PHP is not supported.
	 *
	 * @since 2.2.0
	 *
	 * @phpstan-ignore missingType.return
	 */
	function tablepress_admin_error_notice_minimum_version_php() /* No return type declaration, due to required PHP compatibility! */ {
		?>
		<div class="notice notice-error notice-alt notice-large">
			<h3><em>
				<span aria-hidden="true" class="dashicons dashicons-warning" style="color:#d63638;vertical-align:bottom"></span>
				<?php _e( 'Attention: Unfortunately, there is a problem!', 'tablepress' ); ?>
			</em></h3>
			<p style="font-size:14px">
				<?php printf( __( 'Your server is running a version of PHP that is too old for the TablePress plugin to work. TablePress requires PHP %s or newer, where newer versions are recommended.', 'tablepress' ), '7.4' ); ?>
			</p>
			<p style="font-size:14px">
				<strong><?php printf( __( '<a href="%s">Learn more about updating PHP</a> or contact your server administrator.', 'tablepress' ), esc_url( wp_get_update_php_url() ) ); ?></strong>
			</p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tablepress_admin_error_notice_minimum_version_php' );

	// Exit TablePress early.
	return false;
}

// All environment checks passed.
return true;
