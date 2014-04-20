<?php
/**
 * Table Model
 *
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Table Model class
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Table_Model extends TablePress_Model {

	/**
	 * Instance of the Post Type Model
	 *
	 * @since 1.0.0
	 *
	 * @var TablePress_Post_Model
	 */
	protected $model_post;

	/**
	 * Name of the Post Meta Field for table options
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table_options_field_name = '_tablepress_table_options';

	/**
	 * Name of the Post Meta Field for table visibility
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table_visibility_field_name = '_tablepress_table_visibility';

	/**
	 * Default set of tables
	 *
	 * Array fields:
	 * - last_id: last table ID that was given to a new table
	 * - table_post: array of connections between table ID and post ID (key: table ID, value: post ID)
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $default_tables = array(
		'last_id' => 0,
		'table_post' => array(),
	);

	/**
	 * Instance of WP_Option class for the list of tables
	 *
	 * @since 1.0.0
	 *
	 * @var TablePress_WP_Option
	 */
	protected $tables;

	/**
	 * Init the Table model by instantiating a Post model and loading the list of tables option
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->model_post = TablePress::load_model( 'post' );

		$params = array(
			'option_name' => 'tablepress_tables',
			'default_value' => $this->default_tables,
		);
		$this->tables = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );
	}

	/**
	 * Get the tables option, which holds the connection between table ID and post ID
	 *
	 * @since 1.0.0
	 *
	 * @return array Current set of tables
	 */
	public function _debug_get_tables() {
		return $this->tables->get();
	}

	/**
	 * Update the tables option, which holds the connection between table ID and post ID
	 *
	 * @since 1.0.0
	 *
	 * @param array $tables New set of tables
	 */
	public function _debug_update_tables( array $tables ) {
		$this->tables->update( $tables );
	}

	/**
	 * Convert a table to a post, which can be stored in the database
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table
	 * @param int $post_id Post ID
	 * @return array Post
	 */
	protected function _table_to_post( array $table, $post_id ) {
		// Sanitize each cell, if the user is not allowed to work with unfiltered HTML
		// table name and description are sanitized by WordPress directly, but the JSON would break if we don't do it ourselves
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			foreach ( $table['data'] as $row_idx => $row ) {
				foreach ( $row as $column_idx => $cell_content ) {
					$table['data'][ $row_idx ][ $column_idx ] = wp_kses_post( $cell_content ); // equals wp_filter_post_kses(), but without the unncessary slashes handling
				}
			}
		}

		$post = array(
			'ID' => $post_id,
			'post_title' => $table['name'],
//			'post_author' => $table['author'],
			'post_excerpt' => $table['description'],
			'post_content' => json_encode( $table['data'] ),
		);

		return $post;
	}

	/**
	 * Convert a post (from the database) to a table
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post
	 * @param string $table_id Table ID
	 * @param bool $with_data Whether the table data shall be loaded
	 * @return array Table
	 */
	protected function _post_to_table( $post, $table_id, $load_data ) {
		$table = array(
			'id' => $table_id,
			'name' => $post->post_title,
			'description' => $post->post_excerpt,
			'author' => $post->post_author,
//			'created' => $post->post_date,
			'last_modified' => $post->post_modified,
		);

		if ( ! $load_data ) {
			return $table;
		}

		$table['data'] = json_decode( $post->post_content, true );

		// Check if JSON could be decoded
		if ( is_null( $table['data'] ) ) {
			// Set a single cell as the default
			$table['data'] = array( array( "The internal data of table {$table_id} is corrupted." ) );
			// Mark table as corrupted
			$table['is_corrupted'] = true;

			// if possible, try to find out what error prevented the JSON from being decoded
			$table['json_error'] = '';
			if ( function_exists( 'json_last_error' ) ) {
				// Constant JSON_ERROR_UTF8 is only available as of PHP 5.3.3
				if ( ! defined( 'JSON_ERROR_UTF8' ) ) {
					define( 'JSON_ERROR_UTF8', 5 );
				}

				switch ( json_last_error() ) {
					case JSON_ERROR_NONE:
						$table['json_error'] = 'JSON_ERROR_NONE'; // should never happen here, as this is only called in case of an error
						break;
					case JSON_ERROR_DEPTH:
						$table['json_error'] = 'JSON_ERROR_DEPTH';
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$table['json_error'] = 'JSON_ERROR_STATE_MISMATCH';
						break;
					case JSON_ERROR_CTRL_CHAR:
						$table['json_error'] = 'JSON_ERROR_CTRL_CHAR';
						break;
					case JSON_ERROR_SYNTAX:
						$table['json_error'] = 'JSON_ERROR_SYNTAX';
						break;
					case JSON_ERROR_UTF8:
						$table['json_error'] = 'JSON_ERROR_UTF8';
						break;
					default:
						$table['json_error'] = 'UNKNOWN ERROR';
						break;
				}
				$json_error = " ({$table['json_error']})";
			}

			$table['description'] = "[ERROR] TABLE IS CORRUPTED{$json_error}!  DO NOT EDIT THIS TABLE NOW!\nInstead, please see http://tablepress.org/faq/corrupted-tables/ for instructions.\n-\n{$table['description']}";
		}

		return $table;
	}

	/**
	 * Load a table
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @param bool $load_data Whether the table data shall be loaded
	 * @param bool $load_options_visibility Whether the table options and table visibility shall be loaded
	 * @return array|WP_Error Table as an array on success, WP_Error on error
	 */
	public function load( $table_id, $load_data = true, $load_options_visibility = true ) {
		if ( empty( $table_id ) ) {
			return new WP_Error( 'table_load_empty_table_id' );
		}

		$post_id = $this->_get_post_id( $table_id );
		if ( false === $post_id ) {
			return new WP_Error( 'table_load_no_post_id_for_table_id', '', $table_id );
		}

		$post = $this->model_post->get( $post_id );
		if ( false === $post ) {
			return new WP_Error( 'table_load_no_post_for_post_id', '', $post_id );
		}

		$table = $this->_post_to_table( $post, $table_id, $load_data );
		if ( $load_options_visibility ) {
			$table['options'] = $this->_get_table_options( $post_id );
			$table['visibility'] = $this->_get_table_visibility( $post_id );
		}
		return $table;
	}

	/**
	 * Load the IDs of all tables that can be loaded from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $prime_meta_cache Optional. Whether the prime the post meta cache when loading the posts.
	 * @param bool $run_filter Optional. Whether to run a filter on the list of table IDs.
	 * @return array Array of table IDs
	 */
	public function load_all( $prime_meta_cache = true, $run_filter = true ) {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return array();
		}

		// load all table posts with one query, to prime the cache
		$this->model_post->load_posts( array_values( $table_post ), $prime_meta_cache );

		// this loop now uses the WP cache
		$table_ids = array();
		foreach ( $table_post as $table_id => $post_id ) {
			$table_id = (string) $table_id;
			$table = $this->load( $table_id, false, false ); // Load table without data and options to save memory
			// Skip tables that could not be loaded properly
			if ( ! is_wp_error( $table ) ) {
				$table_ids[] = $table_id;
			}
		}

		if ( $run_filter ) {
			/**
			 * Filter all table IDs that are loaded.
			 *
			 * @since 1.4.0
			 *
			 * @param array $table_ids The table IDs that are loaded.
			 */
			$table_ids = apply_filters( 'tablepress_load_all_tables', $table_ids );
		}

		return $table_ids;
	}

	/**
	 * Save a table
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table (needs to have $table['id']!)
	 * @return string|WP_Error WP_Error on error, string table ID on success
	 */
	public function save( array $table ) {
		if ( empty( $table['id'] ) ) {
			return new WP_Error( 'table_save_empty_table_id' );
		}

		$post_id = $this->_get_post_id( $table['id'] );
		if ( false === $post_id ) {
			return new WP_Error( 'table_save_no_post_id_for_table_id', '', $table['id'] );
		}

		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->update( $post );
		if ( is_wp_error( $new_post_id ) ) {
			// Add an error code to the existing WP_Error
			$new_post_id->add( 'table_save_post_update', '', $post_id );
			return $new_post_id;
		}
		if ( $post_id !== $new_post_id ) {
			return new WP_Error( 'table_save_new_post_id_does_not_match', '', $new_post_id );
		}

		$options_saved = $this->_update_table_options( $new_post_id, $table['options'] );
		if ( ! $options_saved ) {
			return new WP_Error( 'table_save_update_table_options_failed', '', $new_post_id );
		}

		$visibility_saved = $this->_update_table_visibility( $new_post_id, $table['visibility'] );
		if ( ! $visibility_saved ) {
			return new WP_Error( 'table_save_update_table_visibility_failed', '', $new_post_id );
		}

		// at this point, post was successfully added

		// invalidate table output caches that belong to this table
		$this->_invalidate_table_output_cache( $table['id'] );
		// Flush caching plugins' caches
		$this->_flush_caching_plugins_caches();

		return $table['id'];
	}

	/**
	 * Add a new table
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table ($table['id'] is not necessary)
	 * @param string|bool $copied_table_id ID of the copied table, if table is a copy, false otherwise
	 * @return string|WP_Error WP_Error on error, string table ID of the new table on success
	 */
	public function add( array $table, $copied_table_id = false ) {
		$post_id = false; // to insert table
		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->insert( $post );
		if ( is_wp_error( $new_post_id ) ) {
			// Add an error code to the existing WP_Error
			$new_post_id->add( 'table_add_post_insert', '' );
			return $new_post_id;
		}

		$options_saved = $this->_add_table_options( $new_post_id, $table['options'] );
		if ( ! $options_saved ) {
			return new WP_Error( 'table_add_update_table_options_failed', '', $new_post_id );
		}

		$visibility_saved = $this->_add_table_visibility( $new_post_id, $table['visibility'] );
		if ( ! $visibility_saved ) {
			return new WP_Error( 'table_add_update_table_visibility_failed', '', $new_post_id );
		}

		// at this point, post was successfully added, now get an unused table ID
		$table_id = $this->_get_new_table_id();
		$this->_update_post_id( $table_id, $new_post_id );

		if ( false !== $copied_table_id ) {
			/**
			 * Fires after an existing table has been copied.
			 *
			 * @since 1.1.0
			 *
			 * @param string $table_id        ID of the copy of the table.
			 * @param string $copied_table_id ID of the existing table that is copied.
			 */
			do_action( 'tablepress_event_copied_table', $table_id, $copied_table_id );
		} else {
			/**
			 * Fires after a new table has been added.
			 *
			 * @since 1.1.0
			 *
			 * @param string $table_id ID of the added table.
			 */
			do_action( 'tablepress_event_added_table', $table_id );
		}

		return $table_id;
	}

	/**
	 * Create a copy of a table and add it
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id ID of the table to be copied
	 * @return string|WP_Error WP_Error on error, string table ID of the new table on success
	 */
	public function copy( $table_id ) {
		$table = $this->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			// Add an error code to the existing WP_Error
			$table->add( 'table_copy_table_load', '', $table_id );
			return $table;
		}

		// Adjust name of copied table
		if ( '' == trim( $table['name'] ) ) {
			$table['name'] = __( '(no name)', 'tablepress' );
		}
		$table['name'] = sprintf( __( 'Copy of %s', 'tablepress' ), $table['name'] );

		// Merge this data into an empty table template
		$table = $this->prepare_table( $this->get_table_template(), $table, false );
		if ( is_wp_error( $table ) ) {
			// Add an error code to the existing WP_Error
			$table->add( 'table_copy_table_prepare', '', $table['id'] );
			return $table;
		}

		// Add the copied table
		$table = $this->add( $table, $table_id );
		if ( is_wp_error( $table ) ) {
			// Add an error code to the existing WP_Error
			$table->add( 'table_copy_table_add', '', $table_id );
			return $table;
		}

		return $table;
	}

	/**
	 * Delete a table (and its options)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id ID of the table to be deleted
	 * @return bool|WP_Error WP_Error on error, true on success
	 */
	public function delete( $table_id ) {
		if ( ! $this->table_exists( $table_id ) ) {
			return new WP_Error( 'table_delete_table_does_not_exist', '', $table_id );
		}

		$post_id = $this->_get_post_id( $table_id ); // no !false check necessary, as this is covered by table_exists() check above
		$deleted = $this->model_post->delete( $post_id ); // Post Meta fields will be deleted automatically by that function
		if ( false === $deleted ) {
			return new WP_Error( 'table_delete_post_could_not_be_deleted', '', $post_id );
		}

		// if post was deleted successfully, remove the table ID from the list of tables
		$this->_remove_post_id( $table_id );

		// invalidate table output caches that belong to this table
		$this->_invalidate_table_output_cache( $table_id );
		// Flush caching plugins' caches
		$this->_flush_caching_plugins_caches();

		/**
		 * Fires after a table has been deleted.
		 *
		 * @since 1.1.0
		 *
		 * @param string $table_id ID of the deleted table.
		 */
		do_action( 'tablepress_event_deleted_table', $table_id );

		return true;
	}

	/**
	 * Delete all tables
	 *
	 * @since 1.0.0
	 */
	public function delete_all() {
		$tables = $this->tables->get();
		if ( empty( $tables['table_post'] ) ) {
			return;
		}

		foreach ( $tables['table_post'] as $table_id => $post_id ) {
			$table_id = (string) $table_id;
			$this->model_post->delete( $post_id ); // Post Meta fields will be deleted automatically by that function
			unset( $tables['table_post'][ $table_id ] );
			$this->_invalidate_table_output_cache( $table_id ); // invalidate table output caches that belong to this table
		}

		$this->tables->update( $tables );
		$this->_flush_caching_plugins_caches(); // Flush caching plugins' caches

		/**
		 * Fires after all tables have been deleted.
		 *
		 * @since 1.1.0
		 */
		do_action( 'tablepress_event_deleted_all_tables' );
	}

	/**
	 * Check if a table ID exists in the list of tables (this does not guarantee that the post with the table data exists!)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return bool Whether the table ID exists
	 */
	public function table_exists( $table_id ) {
		$table_post = $this->tables->get( 'table_post' );
		return isset( $table_post[ $table_id ] );
	}

	/**
	 * Count the number of tables from either just the list, or by also counting the posts in the database
	 *
	 * @since 1.0.0
	 *
	 * @param bool $single_value (optional) Whether to return just the number of tables from the list, or also count in the database
	 * @return bool int|array Number of Tables (if $single_value), or array of Numbers from list/DB (if ! $single_value)
	 */
	public function count_tables( $single_value = true ) {
		$count_list = count( $this->tables->get( 'table_post' ) );
		if ( $single_value ) {
			return $count_list;
		}

		$count_db = $this->model_post->count_posts();
		return array( 'list' => $count_list, 'db' => $count_db );
	}

	/**
	 * Delete all transients used for output caching of a table (e.g. when the table is updated or deleted)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 */
	protected function _invalidate_table_output_cache( $table_id ) {
		$caches_list_transient_name = 'tablepress_c_' . md5( $table_id );
		$caches_list = get_transient( $caches_list_transient_name );
		if ( false !== $caches_list ) {
			$caches_list = json_decode( $caches_list, true );
			foreach ( $caches_list as $cache_transient_name ) {
				delete_transient( $cache_transient_name );
			}
		}
		delete_transient( $caches_list_transient_name );
	}

	/**
	 * Flush the caches of the plugins W3 Total Cache, WP Super Cache, Cachify, and Quick Cache
	 *
	 * @since 1.0.0
	 */
	public function _flush_caching_plugins_caches() {
		/**
		 * Filter whether the caches of common caching plugins shall be flushed.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $flush Whether caches of caching plugins shall be flushed. Default true.
		 */
		if ( ! apply_filters( 'tablepress_flush_caching_plugins_caches', true ) ) {
			return;
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}
		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		// Cachify
		do_action( 'cachify_flush_cache' );

		// Quick Cache
		if ( isset( $GLOBALS['quick_cache'] ) && method_exists( $GLOBALS['quick_cache'], 'clear_cache' ) ) {
			$GLOBALS['quick_cache']->clear_cache();
		}
	}

	/**
	 * Get the post ID of a given table ID (if the table ID exists)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return int|bool Post ID on success, false on error
	 */
	protected function _get_post_id( $table_id ) {
		$table_post = $this->tables->get( 'table_post' );
		if ( isset( $table_post[ $table_id ] ) ) {
			return $table_post[ $table_id ];
		} else {
			return false;
		}
	}

	/**
	 * Update/Add a post ID for a given table ID, and sort the list of tables by their key in natural sort order
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @param int $post_id Post ID
	 */
	protected function _update_post_id( $table_id, $post_id ) {
		$tables = $this->tables->get();
		$tables['table_post'][ $table_id ] = $post_id;
		uksort( $tables['table_post'], 'strnatcasecmp' );
		$this->tables->update( $tables );
	}

	/**
	 * Remove a table ID / post ID connection from the list of tables
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 */
	protected function _remove_post_id( $table_id ) {
		$tables = $this->tables->get();
		unset( $tables['table_post'][ $table_id ] );
		$this->tables->update( $tables );
	}

	/**
	 * Change the table ID of a table
	 *
	 * @since 1.0.0
	 *
	 * @param string $old_id Old table ID
	 * @param string $new_id New table ID
	 * @return bool|WP_Error True on success, WP_Error on error
	 */
	public function change_table_id( $old_id, $new_id ) {
		$post_id = $this->_get_post_id( $old_id );
		if ( false === $post_id ) {
			return new WP_Error( 'table_change_id_no_post_id_for_table_id', '', $old_id );
		}

		// Check new ID for correct format (string from letters, numbers, -, and _ only, except the '0' string)
		if ( empty( $new_id ) || 0 !== preg_match( '/[^a-zA-Z0-9_-]/', $new_id ) ) {
			return new WP_Error( 'table_change_id_new_id_is_invalid', '', $new_id );
		}

		if ( $this->table_exists( $new_id ) ) {
			return new WP_Error( 'table_change_table_new_id_exists', '', $new_id );
		}

		$this->_update_post_id( $new_id, $post_id );
		$this->_remove_post_id( $old_id );

		/**
		 * Fires after the ID of a table has been changed.
		 *
		 * @since 1.1.0
		 *
		 * @param string $new_id New ID of the table.
		 * @param string $old_id Old ID of the table.
		 */
		do_action( 'tablepress_event_changed_table_id', $new_id, $old_id );

		return true;
	}

	/**
	 * Get an unused table ID (e.g. for a new table)
	 *
	 * @since 1.0.0
	 *
	 * @return string Unused table ID (e.g. for a new table)
	 */
	protected function _get_new_table_id() {
		$tables = $this->tables->get();
		// need to check new ID candidate in a loop, because a higher ID might already be in use, if a table ID was changed manually
		do {
			$tables['last_id'] ++;
		} while ( $this->table_exists( $tables['last_id'] ) );
		$this->tables->update( $tables );
		return (string) $tables['last_id'];
	}

	/**
	 * Get the template for an empty table
	 *
	 * Important: This scheme is versioned via TablePress::table_scheme_version; changes likely need a version update!
	 *
	 * @since 1.0.0
	 *
	 * @return array Empty table
	 */
	public function get_table_template() {
		// Attention: Array keys have to be lowercase, to make it possible to match them with Shortcode attributes!
		$table = array(
			'id' => false,
			'name' => '',
			'description' => '',
			'data' => array( array( '' ) ), // one empty cell
//			'created' => current_time( 'mysql' ),
			'last_modified' => current_time( 'mysql' ),
			'author' => get_current_user_id(),
			'options' => array(
				'last_editor' => get_current_user_id(),
				'table_head' => true,
				'table_foot' => false,
				'alternating_row_colors' => true,
				'row_hover' => true,
				'print_name' => false,
				'print_name_position' => 'above',
				'print_description' => false,
				'print_description_position' => 'below',
				'extra_css_classes' => '',
				// DataTables JavaScript library
				'use_datatables' => true,
				'datatables_sort' => true,
				'datatables_filter' => true,
				'datatables_paginate' => true,
				'datatables_lengthchange' => true,
				'datatables_paginate_entries' => 10,
				'datatables_info' => true,
				'datatables_scrollx' => false,
				'datatables_custom_commands' => '',
			),
			'visibility' => array(
				'rows' => array( 1 ), // one visbile row
				'columns' => array( 1 ), // one visible column
			),
		);
		/**
		 * Filter the default template/structure of an empty table.
		 *
		 * @since 1.0.0
		 *
		 * @param array $table Default template/structure of an empty table.
		 */
		return apply_filters( 'tablepress_table_template', $table );
	}

	/**
	 * Combine two tables (e.g. an existing one with the updated data, or an empty one with new data)
	 * Performs consistency checks on data and visibility settings
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table to merge into
	 * @param array $new_table Table to merge
	 * @param bool $table_size_check (optional) Whether to check the number of rows and columns (e.g. not necessary for added or copied tables)
	 * @param bool $extended_visibility_check (optional) Whether to check the counts of hidden rows and columns (only possible for Admin_AJAX controller as of now)
	 * @return array|WP_Error Merged table on success, WP_Error on error
	 */
	public function prepare_table( array $table, array $new_table, $table_size_check = true, $extended_visibility_check = false ) {
		// Table ID must be the same (if there was an ID already)
		if ( false !== $table['id'] ) {
			if ( $table['id'] !== $new_table['id'] ) {
				return new WP_Error( 'table_prepare_no_id_match', '', $new_table['id'] );
			}
		}

		// Name, description, and data array need to exist, data must not be empty, the others could be ''
		if ( ! isset( $new_table['name'] )
		|| ! isset( $new_table['description'] )
		|| empty( $new_table['data'] )
		|| empty( $new_table['data'][0] ) ) {
			return new WP_Error( 'table_prepare_name_description_or_data_not_set' );
		}

		// Visibility needs to exist
		if ( ! isset( $new_table['visibility'] )
		|| ! isset( $new_table['visibility']['rows'] )
		|| ! isset( $new_table['visibility']['columns'] ) ){
			return new WP_Error( 'table_prepare_visibility_not_set' );
		}
		$new_table['visibility']['rows'] = array_map( 'intval', $new_table['visibility']['rows'] );
		$new_table['visibility']['columns'] = array_map( 'intval', $new_table['visibility']['columns'] );

		// Check dimensions of table data array (not done for newly added, copied, or imported tables)
		if ( $table_size_check ) {
			if ( empty( $new_table['number'] )
			|| ! isset( $new_table['number']['rows'] )
			|| ! isset( $new_table['number']['columns'] ) ) {
				return new WP_Error( 'table_prepare_size_check_numbers_not_set' );
			}
			// Table data needs to be ok, and have the correct number of rows and columns
			$new_table['number']['rows'] = intval( $new_table['number']['rows'] );
			$new_table['number']['columns'] = intval( $new_table['number']['columns'] );
			if ( 0 === $new_table['number']['rows']
			|| 0 === $new_table['number']['columns']
			|| $new_table['number']['rows'] !== count( $new_table['data'] )
			|| $new_table['number']['columns'] !== count( $new_table['data'][0] ) ) {
				return new WP_Error( 'table_prepare_size_check_numbers_dont_match' );
			}
			// Visibility also needs to have correct dimensions
			if ( $new_table['number']['rows'] !== count( $new_table['visibility']['rows'] )
			|| $new_table['number']['columns'] !== count( $new_table['visibility']['columns'] ) ) {
				return new WP_Error( 'table_prepare_size_check_visibility_doesnt_match' );
			}

			if ( $extended_visibility_check ) { // only for Admin_AJAX controller
				if ( ! isset( $new_table['number']['hidden_rows'] )
				|| ! isset( $new_table['number']['hidden_columns'] ) ) {
					return new WP_Error( 'table_prepare_extended_visibility_check_numbers_not_set' );
				}
				$new_table['number']['hidden_rows'] = intval( $new_table['number']['hidden_rows'] );
				$new_table['number']['hidden_columns'] = intval( $new_table['number']['hidden_columns'] );
				// count hidden and visible rows
				$num_visible_rows = count( array_keys( $new_table['visibility']['rows'], 1 ) );
				$num_hidden_rows = count( array_keys( $new_table['visibility']['rows'], 0 ) );
				// Check number of hidden and visible rows
				if ( $new_table['number']['hidden_rows'] !== $num_hidden_rows
				|| ( $new_table['number']['rows'] - $new_table['number']['hidden_rows'] ) !== $num_visible_rows ) {
					return new WP_Error( 'table_prepare_extended_visibility_check_rows_dont_match' );
				}
				// count hidden and visible columns
				$num_visible_columns = count( array_keys( $new_table['visibility']['columns'], 1 ) );
				$num_hidden_columns = count( array_keys( $new_table['visibility']['columns'], 0 ) );
				// Check number of hidden and visible columns
				if ( $new_table['number']['hidden_columns'] !== $num_hidden_columns
				|| ( $new_table['number']['columns'] - $new_table['number']['hidden_columns'] ) !== $num_visible_columns ) {
					return new WP_Error( 'table_prepare_extended_visibility_check_columns_dont_match' );
				}
			}
		}

		// All checks were successful, replace original values with new ones
		// $table['id'] is either false (and remains false) or already equal to $new_table['id']
		$table['new_id'] = isset( $new_table['new_id'] ) ? $new_table['new_id'] : $table['id'];
		$table['name'] = $new_table['name'];
		$table['description'] = $new_table['description'];
		$table['data'] = $new_table['data'];
		// $table['author'] = get_current_user_id(); // we don't want this, as it would override the original author
		// $table['created'] = current_time( 'mysql' ); // we don't want this, as it would override the original datetime
		$table['last_modified'] = current_time( 'mysql' );
		$table['options']['last_editor'] = get_current_user_id();
		// Table Options
		if ( isset( $new_table['options'] ) ) { // is for example not set for newly added tables
			// specials check for certain options
			if ( isset( $new_table['options']['extra_css_classes'] ) ) {
				$new_table['options']['extra_css_classes'] = explode( ' ', $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = array_map( 'sanitize_html_class', $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = array_unique( $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = trim( implode( ' ', $new_table['options']['extra_css_classes'] ) );
			}
			if ( isset( $new_table['options']['datatables_paginate_entries'] ) ) {
				$new_table['options']['datatables_paginate_entries'] = intval( $new_table['options']['datatables_paginate_entries'] );
				if ( $new_table['options']['datatables_paginate_entries'] < 1 ) {
					$new_table['options']['datatables_paginate_entries'] = 10; // default value
				}
			}
			// merge new options
			$default_table = $this->get_table_template();
			$table['options'] = array_intersect_key( $table['options'], $default_table['options'] );
			$new_table['options'] = array_intersect_key( $new_table['options'], $default_table['options'] );
			$table['options'] = array_merge( $table['options'], $new_table['options'] );
		}
		// Table Visibility
		$table['visibility']['rows'] = $new_table['visibility']['rows'];
		$table['visibility']['columns'] = $new_table['visibility']['columns'];

		return $table;
	}

	/**
	 * Save the table options of a table (in a post meta field of the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $options Table options
	 * @return bool True on success, false on error
	 */
	protected function _add_table_options( $post_id, array $options ) {
		$options = json_encode( $options );
		return $this->model_post->add_meta_field( $post_id, $this->table_options_field_name, $options );
	}

	/**
	 * Update the table options of a table (in a post meta field in the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $options Table options
	 * @return bool True on success, false on error
	 */
	protected function _update_table_options( $post_id, array $options ) {
		$options = json_encode( $options );
		return $this->model_post->update_meta_field( $post_id, $this->table_options_field_name, $options );
	}

	/**
	 * Get the table options of a table (from a post meta field of the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @return array Table options on success, empty array on error
	 */
	protected function _get_table_options( $post_id ) {
		$options = $this->model_post->get_meta_field( $post_id, $this->table_options_field_name );
		if ( empty( $options ) ) {
			return array();
		}
		return json_decode( $options, true );
	}

	/**
	 * Save the table visibility of a table (in a post meta field of the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $visibility Table visibility
	 * @return bool True on success, false on error
	 */
	protected function _add_table_visibility( $post_id, array $visibility ) {
		$visibility = json_encode( $visibility );
		return $this->model_post->add_meta_field( $post_id, $this->table_visibility_field_name, $visibility );
	}

	/**
	 * Update the table visibility of a table (in a post meta field in the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $visibility Table visibility
	 * @return bool True on success, false on error
	 */
	protected function _update_table_visibility( $post_id, array $visibility ) {
		$visibility = json_encode( $visibility );
		return $this->model_post->update_meta_field( $post_id, $this->table_visibility_field_name, $visibility );
	}

	/**
	 * Get the table visibility of a table (from a post meta field of the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @return array Table visibility on success, empty array on error
	 */
	protected function _get_table_visibility( $post_id ) {
		$visibility = $this->model_post->get_meta_field( $post_id, $this->table_visibility_field_name );
		if ( empty( $visibility ) ) {
			return array();
		}
		return json_decode( $visibility, true );
	}

	/**
	 * Merge existing Table Options with default Table Options,
	 * remove (no longer) existing options, after a table scheme change,
	 * for all tables
	 *
	 * @since 1.0.0
	 */
	public function merge_table_options_defaults() {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// load all table posts with one query, to prime the cache
		$this->model_post->load_posts( array_values( $table_post ), true );

		// get default Table with default Table Options
		$default_table = $this->get_table_template();

		// go through all tables (this loop now uses the WP cache)
		foreach ( $table_post as $table_id => $post_id ) {
			$table_options = $this->_get_table_options( $post_id );
			// remove old (i.e. no longer existing) Table Options:
			$table_options = array_intersect_key( $table_options, $default_table['options'] );
			// merge into new Table Options:
			$table_options = array_merge( $default_table['options'], $table_options );
			$this->_update_table_options( $post_id, $table_options );
		}
	}

	/**
	 * Merge changes made for TablePress 0.6-beta:
	 * Table Name/Table Description
	 *
	 * @since 0.6-beta
	 */
	public function merge_table_options_tp06() {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// go through all tables
		foreach ( $table_post as $table_id => $post_id ) {
			$table_options = $this->_get_table_options( $post_id );

			// Move "Print Name" to new format
			$print_name = in_array( $table_options['print_name'], array( 'above', 'below' ), true );
			if ( $print_name ) {
				$table_options['print_name_position'] = $table_options['print_name'];
			}
			$table_options['print_name'] = $print_name;
			// Move "Print Description" to new format
			$print_description = in_array( $table_options['print_description'], array( 'above', 'below' ), true );
			if ( $print_description ) {
				$table_options['print_description_position'] = $table_options['print_description'];
			}
			$table_options['print_description'] = $print_description;

			$this->_update_table_options( $post_id, $table_options );
		}
	}

	/**
	 * Merge changes made for TablePress 0.8-beta:
	 * Conversion of parameter "datatables_scrollX" to "datatables_scrollx"
	 * Fixes a bug that affects about the first 600 downloaders of 0.8-beta
	 *
	 * @since 0.8-beta
	 */
	public function merge_table_options_tp08() {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// go through all tables
		foreach ( $table_post as $table_id => $post_id ) {
			$table_options = $this->_get_table_options( $post_id );

			// Convert parameter "datatables_scrollX" to "datatables_scrollx"
			if ( isset( $table_options['datatables_scrollX'] ) && ! isset( $table_options['datatables_scrollx'] ) ) {
				$table_options['datatables_scrollx'] = $table_options['datatables_scrollX'];
			}

			$this->_update_table_options( $post_id, $table_options );
		}
	}

	/**
	 * Invalidate all table output caches, e.g. after a plugin update
	 * For TablePress 0.9-RC and onwards.
	 *
	 * @since 0.9-RC
	 */
	public function invalidate_table_output_caches() {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// go through all tables
		foreach ( $table_post as $table_id => $post_id ) {
			$this->_invalidate_table_output_cache( $table_id );
		}
	}

	/**
	 * Invalidate all table output caches, e.g. after a plugin update
	 * For TablePress pre-0.9-RC updates
	 *
	 * @since 0.9-RC
	 */
	public function invalidate_table_output_caches_tp09() {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// go through all tables
		foreach ( $table_post as $table_id => $post_id ) {
			$caches_list_transient_name = 'tablepress_c_' . md5( $table_id );
			$caches_list = get_transient( $caches_list_transient_name );
			if ( is_array( $caches_list ) ) {
				foreach ( $caches_list as $cache_transient_name => $dummy_value ) {
					delete_transient( $cache_transient_name );
				}
			}
			delete_transient( $caches_list_transient_name );
		}
	}

	/**
	 * Delete the WP_Option of the model
	 *
	 * @since 1.0.0
	 */
	public function destroy() {
		$this->tables->delete();
	}

} // class TablePress_Table_Model
