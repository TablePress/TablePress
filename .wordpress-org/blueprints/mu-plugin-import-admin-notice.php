<?php
/**
 * Helper file for the TablePress integration in the WordPress Playground.
 *
 * @package TablePress
 * @subpackage WordPress Playground
 * @author Tobias Bäthge
 * @since 3.0.0
 */

declare(strict_types=1);

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/*
 * Print a warning notice about the import from URLs likely not working for all URLs.
 * WordPress Playground tries getting the import URL via JavaScript, which is subject to CORS restritions on the target server.
 */
add_action(
	'admin_notices',
	static function () {
		if ( 'tablepress_import' === get_current_screen()->id ) {
			echo '<div class="notice components-notice is-warning notice-warning"><div class="components-notice__content"><p><strong>Important notice:</strong><br>Due to how this in-browser demo works behind the scenes, import from URLs might not work for all URLs! It will however work fine on a real WordPress installation!</p></div></div>';
		}
	}
);
