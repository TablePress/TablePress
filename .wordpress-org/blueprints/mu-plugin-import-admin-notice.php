<?php
/**
 * Helper file for the TablePress integration in the WordPress Playground.
 *
 * @package TablePress
 * @subpackage WordPress Playground
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/*
 * Print a warning notice about the import from URLs likely not working for all URLs.
 * WordPress Playground tries getting the import URL via JavaScript, which is subject to CORS restritions on the target server.
 */
add_action(
	'admin_notices',
	function () {
		if ( 'tablepress_import' !== get_current_screen()->id ) {
			return;
		}
		echo '<div class="notice notice-warning notice-alt"><p><strong>Important notice:</strong><br>Due to how this in-browser demo works behind the scenes, import from URLs might not work for all URLs! It will however work fine on a real WordPress installation!</p></div>';
	}
);
