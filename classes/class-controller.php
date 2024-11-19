<?php
/**
 * TablePress Base Controller with members and methods for all controllers
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Base Controller class
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
abstract class TablePress_Controller {

	/**
	 * Initializes all controllers.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Update check, in all controllers (frontend and admin), to make sure we always have up-to-date options, should be done very early.
		$this->plugin_update_check();
	}

	/**
	 * Check if the plugin was updated and perform necessary actions, like updating the options.
	 *
	 * @since 1.0.0
	 */
	protected function plugin_update_check(): void {
		// First activation or plugin update.
		$current_plugin_options_db_version = TablePress::$model_options->get( 'plugin_options_db_version' );
		if ( $current_plugin_options_db_version < TablePress::db_version ) {
			// Allow more PHP execution time for update process.
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

			// Add TablePress capabilities to the WP_Roles objects, for new installations and all versions below 12.
			if ( $current_plugin_options_db_version < 12 ) {
				TablePress::$model_options->add_access_capabilities();
			}

			if ( 0 === TablePress::$model_options->get( 'first_activation' ) ) {
				// Save initial set of plugin options, and time of first activation of the plugin, on first activation.
				TablePress::$model_options->update( array(
					'first_activation'          => time(),
					'plugin_options_db_version' => TablePress::db_version,
				) );
			} else {
				// Update Plugin Options Options, if necessary.
				TablePress::$model_options->merge_plugin_options_defaults();
				$updated_options = array(
					'plugin_options_db_version' => TablePress::db_version,
					'prev_tablepress_version'   => TablePress::$model_options->get( 'tablepress_version' ),
					'tablepress_version'        => TablePress::version,
					'message_plugin_update'     => true,
				);

				// If used, re-save "Custom CSS" to re-create all files (as TablePress Default CSS might have changed).
				$custom_css = TablePress::$model_options->get( 'custom_css' );
				if ( TablePress::$model_options->get( 'use_custom_css' ) && '' !== $custom_css ) {
					/**
					 * Load WP file functions to provide filesystem access functions early.
					 */
					require_once ABSPATH . 'wp-admin/includes/file.php'; // @phpstan-ignore requireOnce.fileNotFound (This is a WordPress core file that always exists.)
					/**
					 * Load WP admin template functions to provide `submit_button()` which is necessary for `request_filesystem_credentials()`.
					 */
					require_once ABSPATH . 'wp-admin/includes/template.php'; // @phpstan-ignore requireOnce.fileNotFound (This is a WordPress core file that always exists.)
					$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );

					$custom_css_minified = TablePress::$model_options->get( 'custom_css_minified' );

					// Update "Custom CSS" to be compatible with DataTables 2, introduced in TablePress 3.0.
					if ( $current_plugin_options_db_version < 96 ) {
						$old_custom_css = $custom_css;
						$custom_css = TablePress::convert_datatables_api_data( $custom_css );
						if ( $old_custom_css !== $custom_css ) {
							$custom_css = $tablepress_css->sanitize_css( $custom_css );
							$custom_css_minified = $tablepress_css->minify_css( $custom_css );
							$updated_options['custom_css'] = $custom_css;
							$updated_options['custom_css_minified'] = $custom_css_minified;
						}
						unset( $old_custom_css );
					}

					$result = $tablepress_css->save_custom_css_to_file( $custom_css, $custom_css_minified );
					// If saving was successful, use "Custom CSS" file.
					$updated_options['use_custom_css_file'] = $result;
					// Increase the "Custom CSS" version number for cache busting.
					if ( $result ) {
						$updated_options['custom_css_version'] = TablePress::$model_options->get( 'custom_css_version' ) + 1;
					}
				}

				TablePress::$model_options->update( $updated_options );

				// Clear table caches.
				TablePress::$model_table->invalidate_table_output_caches();

				// Add mime type field to existing posts with the TablePress Custom Post Type, in TablePress 1.5.
				if ( $current_plugin_options_db_version < 25 ) {
					TablePress::$model_table->add_mime_type_to_posts();
				}

				// Add new access capabilities that were introduced in TablePress 2.3.2.
				if ( $current_plugin_options_db_version < 77 ) {
					TablePress::$model_options->add_access_capabilities_tp232();
				}

				// Update all tables' "Custom Commands" to be compatible with DataTables 2, introduced in TablePress 3.0.
				if ( $current_plugin_options_db_version < 96 ) {
					TablePress::$model_table->update_custom_commands_datatables_tp30();
				}
			}
		}

		// Maybe update the table scheme in each existing table, independently from updating the plugin options.
		if ( TablePress::$model_options->get( 'table_scheme_db_version' ) < TablePress::table_scheme_version ) {
			TablePress::$model_table->merge_table_options_defaults();
			TablePress::$model_options->update( 'table_scheme_db_version', TablePress::table_scheme_version );
		}

		/*
		 * Update User Options, if necessary.
		 * User Options are not saved in DB until first change occurs.
		 */
		if ( is_user_logged_in() && TablePress::$model_options->get( 'user_options_db_version' ) < TablePress::db_version ) {
			TablePress::$model_options->merge_user_options_defaults();
			$updated_options = array(
				'user_options_db_version'       => TablePress::db_version,
				'message_superseded_extensions' => true,
			);
			TablePress::$model_options->update( $updated_options );
		}
	}

} // class TablePress_Controller
