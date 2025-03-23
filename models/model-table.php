<?php
/**
 * Table Model
 *
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Table Model class
 *
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Table_Model extends TablePress_Model {

	/**
	 * Instance of the Post Type Model.
	 *
	 * @since 1.0.0
	 */
	protected \TablePress_Post_Model $model_post;

	/**
	 * Name of the Post Meta Field for table options.
	 *
	 * @since 1.0.0
	 */
	protected string $table_options_field_name = '_tablepress_table_options';

	/**
	 * Name of the Post Meta Field for table visibility.
	 *
	 * @since 1.0.0
	 */
	protected string $table_visibility_field_name = '_tablepress_table_visibility';

	/**
	 * Default set of tables.
	 *
	 * @since 1.0.0
	 * @var array{last_id: int, table_post: array<string, int>} {
	 *     @type int                $last_id    Last table ID that was given to a new table.
	 *     @type array<string, int> $table_post Connections between table ID and post ID (key: table ID, value: post ID).
	 * }
	 */
	protected array $default_tables = array(
		'last_id'    => 0,
		'table_post' => array(),
	);

	/**
	 * Instance of WP_Option class for the list of tables.
	 *
	 * @since 1.0.0
	 */
	protected \TablePress_WP_Option $tables;

	/**
	 * Init the Table model by instantiating a Post model and loading the list of tables option.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->model_post = TablePress::load_model( 'post' );

		$params = array(
			'option_name'   => 'tablepress_tables',
			'default_value' => $this->default_tables,
		);
		$this->tables = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );
	}

	/**
	 * Get the tables option, which holds the connection between table ID and post ID.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed[] Current set of tables.
	 */
	public function _debug_get_tables(): array {
		return $this->tables->get();
	}

	/**
	 * Update the tables option, which holds the connection between table ID and post ID.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $tables New set of tables.
	 */
	public function _debug_update_tables( array $tables ): void {
		$this->tables->update( $tables );
	}

	/**
	 * Convert a table to a post, which can be stored in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table   Table.
	 * @param int                  $post_id Post ID of an existing table, or -1 for a new table.
	 * @return array<string, mixed> Post.
	 */
	protected function _table_to_post( array $table, int $post_id ): array {
		// Sanitize each cell, table name, and table description, if the user is not allowed to work with unfiltered HTML.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$table = $this->sanitize( $table );
		}

		// New posts have a post ID of false in WordPress.
		if ( -1 === $post_id ) {
			$post_id = false;
		}

		$post = array(
			'ID'             => $post_id,
			'post_title'     => $table['name'],
			'post_excerpt'   => $table['description'],
			'post_content'   => wp_json_encode( $table['data'], TABLEPRESS_JSON_OPTIONS ),
			'post_mime_type' => 'application/json',
		);

		return $post;
	}

	/**
	 * Convert a post (from the database) to a table.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post      Post.
	 * @param string  $table_id  Table ID.
	 * @param bool    $load_data Whether the table data shall be loaded.
	 * @return array<string, mixed> Table.
	 */
	protected function _post_to_table( WP_Post $post, string $table_id, bool $load_data ): array {
		$table = array(
			'id'            => $table_id,
			'name'          => $post->post_title,
			'description'   => $post->post_excerpt,
			'author'        => $post->post_author,
			// 'created' => $post->post_date,
			'last_modified' => $post->post_modified,
		);

		if ( ! $load_data ) {
			return $table;
		}

		$table['data'] = json_decode( $post->post_content, true );

		// Check if JSON could be decoded.
		if ( is_null( $table['data'] ) ) {
			$table['data'] = array( array( "The internal data of table {$table_id} is corrupted." ) ); // Set a single cell as the cell content.
			$table['is_corrupted'] = true;
			$table['json_error'] = json_last_error_msg();
			$table['description'] = "[ERROR] TABLE IS CORRUPTED (JSON error: {$table['json_error']})!  DO NOT EDIT THIS TABLE NOW!\nInstead, please see https://tablepress.org/faq/corrupted-tables/ for instructions.\n-\n{$table['description']}";
		} else {
			// Specifically cast to an array again.
			$table['data'] = (array) $table['data'];
		}

		return $table;
	}

	/**
	 * Load a table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id                Table ID.
	 * @param bool   $load_data               Whether the table data shall be loaded.
	 * @param bool   $load_options_visibility Whether the table options and table visibility shall be loaded.
	 * @return array<string, mixed>|WP_Error Table as an array on success, WP_Error on error.
	 */
	public function load( string $table_id, bool $load_data = true, bool $load_options_visibility = true ) /* : array|WP_Error */ {
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
	 * @param bool $run_filter       Optional. Whether to run a filter on the list of table IDs.
	 * @return string[] Array of table IDs.
	 */
	public function load_all( bool $prime_meta_cache = true, bool $run_filter = true ): array {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return array();
		}

		// Load all table posts with one query, to prime the cache.
		$this->model_post->load_posts( array_values( $table_post ), $prime_meta_cache );

		// This loop now uses the WP cache.
		$table_ids = array();
		foreach ( $table_post as $table_id => $post_id ) {
			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.

			// Load table without data and options to save memory.
			$table = $this->load( $table_id, false, false );

			// Skip tables that could not be loaded properly.
			if ( ! is_wp_error( $table ) ) {
				$table_ids[] = $table_id;
			}
		}

		if ( $run_filter ) {
			/**
			 * Filters all table IDs that are loaded.
			 *
			 * @since 1.4.0
			 *
			 * @param string[] $table_ids The table IDs that are loaded.
			 */
			$table_ids = apply_filters( 'tablepress_load_all_tables', $table_ids );
		}

		return $table_ids;
	}

	/**
	 * Sanitize the table to remove undesired HTML code using KSES.
	 *
	 * @since 1.8.0
	 *
	 * @param array<string, mixed> $table Table.
	 * @return array<string, mixed> Sanitized table.
	 */
	public function sanitize( array $table ): array {
		// Sanitize the table name and description.
		$fields = array( 'name', 'description' );
		foreach ( $fields as $field ) {
			$table[ $field ] = wp_kses_post( $table[ $field ] );
		}

		// Sanitize each cell.
		foreach ( $table['data'] as $row_idx => $row ) {
			foreach ( $row as $column_idx => $cell_content ) {
				$table['data'][ $row_idx ][ $column_idx ] = wp_kses_post( $cell_content ); // Equals wp_filter_post_kses(), but without the unnecessary slashes handling.
			}
		}

		return $table;
	}

	/**
	 * Save a table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table Table (needs to have $table['id']!).
	 * @return string|WP_Error WP_Error on error, string table ID on success.
	 */
	public function save( array $table ) /* : string|WP_Error */ {
		if ( empty( $table['id'] ) ) {
			return new WP_Error( 'table_save_empty_table_id' );
		}

		/**
		 * Fires before a table is saved.
		 *
		 * @since 3.1.0
		 *
		 * @param string $table_id ID of the table to be saved.
		 */
		do_action( 'tablepress_event_pre_save_table', $table['id'] );

		$post_id = $this->_get_post_id( $table['id'] );
		if ( false === $post_id ) {
			return new WP_Error( 'table_save_no_post_id_for_table_id', '', $table['id'] );
		}

		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->update( $post );
		if ( is_wp_error( $new_post_id ) ) {
			$error = new WP_Error( 'table_save_post_update', '', $post_id );
			$error->merge_from( $new_post_id );
			return $error;
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

		// At this point, post was successfully added.

		// Invalidate table output caches that belong to this table.
		$this->invalidate_table_output_cache( $table['id'] );
		// Flush caching plugins' caches.
		$this->_flush_caching_plugins_caches();

		/**
		 * Fires after a table has been saved.
		 *
		 * @since 1.5.0
		 *
		 * @param string $table_id ID of the saved table.
		 */
		do_action( 'tablepress_event_saved_table', $table['id'] );

		return $table['id'];
	}

	/**
	 * Add a new table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table       Table ($table['id'] is not necessary).
	 * @param string               $copy_or_add Optional. 'copy' if the table is copied, 'add' if it is a new table. Default 'add'.
	 * @return string|WP_Error WP_Error on error, string table ID of the new table on success.
	 */
	public function add( array $table, string $copy_or_add = 'add' ) /* : string|WP_Error */ {
		$post_id = -1; // To insert table.
		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->insert( $post );
		if ( is_wp_error( $new_post_id ) ) {
			$error = new WP_Error( 'table_add_post_insert', '' );
			$error->merge_from( $new_post_id );
			return $error;
		}

		$options_saved = $this->_add_table_options( $new_post_id, $table['options'] );
		if ( ! $options_saved ) {
			return new WP_Error( 'table_add_update_table_options_failed', '', $new_post_id );
		}

		$visibility_saved = $this->_add_table_visibility( $new_post_id, $table['visibility'] );
		if ( ! $visibility_saved ) {
			return new WP_Error( 'table_add_update_table_visibility_failed', '', $new_post_id );
		}

		// At this point, post was successfully added, now get an unused table ID.
		$table_id = $this->_get_new_table_id();
		$this->_update_post_id( $table_id, $new_post_id );

		if ( 'add' === $copy_or_add ) {
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
	 * Create a copy of a table and add it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id ID of the table to be copied.
	 * @return string|WP_Error WP_Error on error, string table ID of the new table on success.
	 */
	public function copy( string $table_id ) /* : string|WP_Error */ {
		$table = $this->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			$error = new WP_Error( 'table_copy_table_load', '', $table_id );
			$error->merge_from( $table );
			return $error;
		}

		// Adjust name of copied table.
		if ( '' === trim( $table['name'] ) ) {
			$table['name'] = __( '(no name)', 'tablepress' );
		}
		$table['name'] = sprintf( __( 'Copy of %s', 'tablepress' ), $table['name'] );

		// Merge this data into an empty table template.
		$table = $this->prepare_table( $this->get_table_template(), $table, false );
		if ( is_wp_error( $table ) ) {
			$error = new WP_Error( 'table_copy_table_prepare', '', $table_id );
			$error->merge_from( $table );
			return $error;
		}

		// Add the copied table.
		$new_table_id = $this->add( $table, 'copy' );
		if ( is_wp_error( $new_table_id ) ) {
			$error = new WP_Error( 'table_copy_table_add', '', $table_id );
			$error->merge_from( $new_table_id );
			return $error;
		}

		/**
		 * Fires after an existing table has been copied.
		 *
		 * @since 1.1.0
		 *
		 * @param string $new_table_id ID of the copy of the table.
		 * @param string $table_id     ID of the existing table that is copied.
		 */
		do_action( 'tablepress_event_copied_table', $new_table_id, $table_id );

		return $new_table_id;
	}

	/**
	 * Delete a table (and its options).
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id ID of the table to be deleted.
	 * @return bool|WP_Error WP_Error on error, true on success.
	 */
	public function delete( string $table_id ) /* : true|WP_Error */ {
		if ( ! $this->table_exists( $table_id ) ) {
			return new WP_Error( 'table_delete_table_does_not_exist', '', $table_id );
		}

		/**
		 * Fires before a table is deleted.
		 *
		 * @since 3.1.0
		 *
		 * @param string $table_id ID of the table to be deleted.
		 */
		do_action( 'tablepress_event_pre_delete_table', $table_id );

		$post_id = $this->_get_post_id( $table_id ); // No ! false check necessary, as this is covered by table_exists() check above.

		// @phpstan-ignore argument.type
		$deleted = $this->model_post->delete( $post_id ); // Post Meta fields will be deleted automatically by that function.
		if ( false === $deleted ) {
			return new WP_Error( 'table_delete_post_could_not_be_deleted', '', $post_id );
		}

		// If post was deleted successfully, remove the table ID from the list of tables.
		$this->_remove_post_id( $table_id );

		// Invalidate table output caches that belong to this table.
		$this->invalidate_table_output_cache( $table_id );
		// Flush caching plugins' caches.
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
	 * Delete all tables.
	 *
	 * @since 1.0.0
	 */
	public function delete_all(): void {
		$tables = $this->tables->get();
		if ( empty( $tables['table_post'] ) ) {
			return;
		}

		foreach ( $tables['table_post'] as $table_id => $post_id ) {
			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.

			$this->model_post->delete( $post_id ); // Post Meta fields will be deleted automatically by that function.
			unset( $tables['table_post'][ $table_id ] );

			// Invalidate table output caches that belong to this table.
			$this->invalidate_table_output_cache( $table_id );
		}

		$this->tables->update( $tables );
		// Flush caching plugins' caches.
		$this->_flush_caching_plugins_caches();

		/**
		 * Fires after all tables have been deleted.
		 *
		 * @since 1.1.0
		 */
		do_action( 'tablepress_event_deleted_all_tables' );
	}

	/**
	 * Check if a table ID exists in the list of tables (this does not guarantee that the post with the table data exists!).
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID.
	 * @return bool Whether the table ID exists.
	 */
	public function table_exists( string $table_id ): bool {
		$table_post = $this->tables->get( 'table_post' );
		return isset( $table_post[ $table_id ] );
	}

	/**
	 * Count the number of tables from either just the list, or by also counting the posts in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $single_value Optional. Whether to return just the number of tables from the list, or also count in the database.
	 * @return int|array{list: int, db: int} Number of Tables (if $single_value), or array of Numbers from list/DB (if ! $single_value).
	 */
	public function count_tables( bool $single_value = true ) /* : int|array */ {
		$count_list = count( $this->tables->get( 'table_post' ) );
		if ( $single_value ) {
			return $count_list;
		}

		$count_db = $this->model_post->count_posts();
		return array(
			'list' => $count_list,
			'db'   => $count_db,
		);
	}

	/**
	 * Delete all transients used for output caching of a table (e.g. when the table is updated or deleted).
	 *
	 * @since 1.0.0
	 * @since 1.8.0 Renamed from _invalidate_table_output_cache to invalidate_table_output_cache and made public.
	 *
	 * @param string $table_id Table ID.
	 */
	public function invalidate_table_output_cache( string $table_id ): void {
		$caches_list_transient_name = 'tablepress_c_' . md5( $table_id );
		$caches_list = get_transient( $caches_list_transient_name );
		if ( false !== $caches_list ) {
			$caches_list = (array) json_decode( $caches_list, true );
			foreach ( $caches_list as $cache_transient_name ) {
				delete_transient( $cache_transient_name );
			}
		}
		delete_transient( $caches_list_transient_name );
	}

	/**
	 * Flush the caches of common caching plugins.
	 *
	 * @since 1.0.0
	 */
	public function _flush_caching_plugins_caches(): void {
		/**
		 * Filters whether the caches of common caching plugins shall be flushed.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $flush Whether caches of caching plugins shall be flushed. Default true.
		 */
		if ( ! apply_filters( 'tablepress_flush_caching_plugins_caches', true ) ) {
			return;
		}

		// Common cache flush callback.
		$cache_flush_callbacks = array(
			array( 'Breeze_PurgeCache', 'breeze_cache_flush' ), // Breeze.
			array( 'comet_cache', 'clear' ), // Comet Cache.
			'pantheon_wp_clear_edge_all', // Pantheon.
			'sg_cachepress_purge_cache', // SG Optimizer.
			array( 'Swift_Performance_Cache', 'clear_all_cache' ), // Swift Performance.
			'w3tc_pgcache_flush', // W3 Total Cache.
			array( 'WpeCommon', 'purge_memcached' ), // WP Engine.
			array( 'WpeCommon', 'clear_maxcdn_cache' ), // WP Engine.
			array( 'WpeCommon', 'purge_varnish_cache' ), // WP Engine.
			'wpfc_clear_all_cache', // WP Fastest Cache.
			'rocket_clean_domain', // WP Rocket.
			'wp_cache_clear_cache', // WP Super Cache.
			array( 'zencache', 'clear' ), // Zen Cache.
		);
		foreach ( $cache_flush_callbacks as $cache_flush_callback ) {
			if ( is_callable( $cache_flush_callback ) ) {
				call_user_func( $cache_flush_callback );
			}
		}

		// Common cache flush hooks.
		$cache_flush_hooks = array(
			'ce_clear_cache', // Cache Enabler.
			'cachify_flush_cache', // Cachify.
			'autoptimize_action_cachepurged', // Hyper Cache.
		);
		foreach ( $cache_flush_hooks as $cache_flush_hook ) {
			do_action( $cache_flush_hook );
		}

		// Kinsta.
		if ( isset( $GLOBALS['kinsta_cache'] ) && ! empty( $GLOBALS['kinsta_cache']->kinsta_cache_purge ) && is_callable( array( $GLOBALS['kinsta_cache']->kinsta_cache_purge, 'purge_complete_caches' ) ) ) {
			$GLOBALS['kinsta_cache']->kinsta_cache_purge->purge_complete_caches(); // @phpstan-ignore method.nonObject
		}
		// LiteSpeed Cache.
		if ( is_callable( array( 'LiteSpeed_Cache_Tags', 'add_purge_tag' ) ) ) {
			LiteSpeed_Cache_Tags::add_purge_tag( '*' ); // @phpstan-ignore class.notFound
		}
		// Pagely.
		if ( class_exists( 'PagelyCachePurge' ) ) {
			$_pagely = new PagelyCachePurge();
			if ( is_callable( array( $_pagely, 'purgeAll' ) ) ) {
				$_pagely->purgeAll();
			}
		}
		// Pressidum.
		if ( is_callable( array( 'Ninukis_Plugin', 'get_instance' ) ) ) {
			$_pressidum = Ninukis_Plugin::get_instance(); // @phpstan-ignore class.notFound
			if ( is_callable( array( $_pressidum, 'purgeAllCaches' ) ) ) {
				$_pressidum->purgeAllCaches(); // @phpstan-ignore method.nonObject
			}
		}
		// Savvii.
		if ( defined( '\Savvii\CacheFlusherPlugin::NAME_DOMAINFLUSH_NOW' ) ) {
			$_savvii = new \Savvii\CacheFlusherPlugin(); // @phpstan-ignore class.notFound
			if ( is_callable( array( $_savvii, 'domainflush' ) ) ) {
				$_savvii->domainflush(); // @phpstan-ignore class.notFound
			}
		}
		// WP Fastest Cache.
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && is_callable( array( $GLOBALS['wp_fastest_cache'], 'deleteCache' ) ) ) {
			$GLOBALS['wp_fastest_cache']->deleteCache( true ); // @phpstan-ignore method.nonObject
		}
		// WP-Optimize.
		if ( function_exists( 'WP_Optimize' ) ) {
			WP_Optimize()->get_page_cache()->purge();
		}
	}

	/**
	 * Get the post ID of a given table ID (if the table ID exists).
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID.
	 * @return int|false Post ID on success, false on error.
	 */
	protected function _get_post_id( string $table_id ) /* : int|false */ {
		$table_post = $this->tables->get( 'table_post' );
		if ( ! isset( $table_post[ $table_id ] ) ) {
			return false;
		}
		return $table_post[ $table_id ];
	}

	/**
	 * Update/Add a post ID for a given table ID, and sort the list of tables by their key in natural sort order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID.
	 * @param int    $post_id Post ID.
	 */
	protected function _update_post_id( string $table_id, int $post_id ): void {
		$tables = $this->tables->get();
		$tables['table_post'][ $table_id ] = $post_id;
		uksort( $tables['table_post'], 'strnatcasecmp' );
		$this->tables->update( $tables );
	}

	/**
	 * Remove a table ID/post ID connection from the list of tables.
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID.
	 */
	protected function _remove_post_id( string $table_id ): void {
		$tables = $this->tables->get();
		unset( $tables['table_post'][ $table_id ] );
		$this->tables->update( $tables );
	}

	/**
	 * Change the table ID of a table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $old_id Old table ID.
	 * @param string $new_id New table ID.
	 * @return bool|WP_Error True on success, WP_Error on error.
	 */
	public function change_table_id( string $old_id, string $new_id ) /* : true|WP_Error */ {
		$post_id = $this->_get_post_id( $old_id );
		if ( false === $post_id ) {
			return new WP_Error( 'table_change_id_no_post_id_for_table_id', '', $old_id );
		}

		// Check new ID for correct format (string from letters, numbers, -, and _ only, except the '0' string).
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
	 * Get an unused table ID (e.g. for a new table).
	 *
	 * @since 1.0.0
	 *
	 * @return string Unused table ID (e.g. for a new table).
	 */
	protected function _get_new_table_id(): string {
		$tables = $this->tables->get();
		// Need to check new ID candidate in a loop, because a higher ID might already be in use, if a table ID was changed manually.
		do {
			++$tables['last_id'];
			$last_id_string = (string) $tables['last_id'];
		} while ( $this->table_exists( $last_id_string ) );
		$this->tables->update( $tables );
		return $last_id_string;
	}

	/**
	 * Get the template for an empty table.
	 *
	 * Important: This scheme is versioned via TablePress::table_scheme_version; changes likely need a version update!
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Empty table.
	 */
	public function get_table_template(): array {
		// Attention: Array keys have to be lowercase, to make it possible to match them with Shortcode attributes!
		$table = array(
			'id'            => false,
			'name'          => '',
			'description'   => '',
			'data'          => array( array( '' ) ), // One empty cell.
			'last_modified' => wp_date( 'Y-m-d H:i:s' ),
			'author'        => get_current_user_id(),
			'options'       => array(
				'last_editor'                 => get_current_user_id(),
				'table_head'                  => 1,
				'table_foot'                  => 0,
				'alternating_row_colors'      => true,
				'row_hover'                   => true,
				'print_name'                  => false,
				'print_name_position'         => 'above',
				'print_description'           => false,
				'print_description_position'  => 'below',
				'extra_css_classes'           => '',
				// DataTables JavaScript library.
				'use_datatables'              => true,
				'datatables_sort'             => true,
				'datatables_filter'           => true,
				'datatables_paginate'         => true,
				'datatables_lengthchange'     => true,
				'datatables_paginate_entries' => 10,
				'datatables_info'             => true,
				'datatables_scrollx'          => false,
				'datatables_custom_commands'  => '',
			),
			'visibility'    => array(
				'rows'    => array( 1 ), // One visbile row.
				'columns' => array( 1 ), // One visible column.
			),
		);
		/**
		 * Filters the default template/structure of an empty table.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $table Default template/structure of an empty table.
		 */
		return apply_filters( 'tablepress_table_template', $table );
	}

	/**
	 * Combine two tables (e.g. an existing one with the updated data, or an empty one with new data).
	 *
	 * Performs consistency checks on data and visibility settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table            Table to merge into.
	 * @param array<string, mixed> $new_table        Table to merge.
	 * @param bool                 $table_size_check Optional. Whether to check the number of rows and columns (e.g. not necessary for added or copied tables).
	 * @return array<string, mixed>|WP_Error Merged table on success, WP_Error on error.
	 */
	public function prepare_table( array $table, array $new_table, bool $table_size_check = true ) /* : array|WP_Error */ {
		// Table ID must be the same (if there was an ID already).
		if ( false !== $table['id'] && $table['id'] !== $new_table['id'] ) {
			return new WP_Error( 'table_prepare_no_id_match', '', $new_table['id'] );
		}

		// Name, description, and data array need to exist, data must not be empty, the others could be ''.
		if ( ! isset( $new_table['name'], $new_table['description'] )
		|| empty( $new_table['data'] ) || empty( $new_table['data'][0] ) ) {
			return new WP_Error( 'table_prepare_name_description_or_data_not_set' );
		}

		// Visibility needs to exist.
		if ( ! isset( $new_table['visibility']['rows'], $new_table['visibility']['columns'] ) ) {
			return new WP_Error( 'table_prepare_visibility_not_set' );
		}
		$new_table['visibility']['rows'] = array_map( 'intval', $new_table['visibility']['rows'] );
		$new_table['visibility']['columns'] = array_map( 'intval', $new_table['visibility']['columns'] );

		// Check dimensions of table data array (not done for newly added, copied, or imported tables).
		if ( $table_size_check ) {
			if ( ! isset( $new_table['number']['rows'], $new_table['number']['columns'] ) ) {
				return new WP_Error( 'table_prepare_size_check_numbers_not_set' );
			}
			// Table data needs to be ok, and have the correct number of rows and columns.
			$new_table['number']['rows'] = (int) $new_table['number']['rows'];
			$new_table['number']['columns'] = (int) $new_table['number']['columns'];
			if ( 0 === $new_table['number']['rows']
			|| 0 === $new_table['number']['columns']
			|| count( $new_table['data'] ) !== $new_table['number']['rows']
			|| count( $new_table['data'][0] ) !== $new_table['number']['columns'] ) {
				return new WP_Error( 'table_prepare_size_check_numbers_dont_match' );
			}
			// Visibility also needs to have correct dimensions.
			if ( count( $new_table['visibility']['rows'] ) !== $new_table['number']['rows']
			|| count( $new_table['visibility']['columns'] ) !== $new_table['number']['columns'] ) {
				return new WP_Error( 'table_prepare_size_check_visibility_doesnt_match' );
			}
		}

		// All checks were successful, replace original values with new ones.

		// $table['id'] is either false (and remains false) or already equal to $new_table['id'].
		$table['new_id'] = $new_table['new_id'] ?? $table['id'];
		$table['name'] = $new_table['name'];
		$table['description'] = $new_table['description'];
		$table['data'] = $new_table['data'];
		// Make sure that cells are stored as strings.
		array_walk_recursive(
			$table['data'],
			static function ( /* string|int|float|bool|null */ &$cell_content, int $col_idx ): void {
				$cell_content = (string) $cell_content;
			},
		);
		// Table Options.
		if ( isset( $new_table['options'] ) ) { // Options are for example not set for newly added tables.
			// Specials check for certain options.
			if ( isset( $new_table['options']['extra_css_classes'] ) ) {
				$new_table['options']['extra_css_classes'] = explode( ' ', $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = array_map( array( 'TablePress', 'sanitize_css_class' ), $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = array_unique( $new_table['options']['extra_css_classes'] );
				$new_table['options']['extra_css_classes'] = trim( implode( ' ', $new_table['options']['extra_css_classes'] ) );
			}
			if ( isset( $new_table['options']['datatables_paginate_entries'] ) ) {
				$new_table['options']['datatables_paginate_entries'] = (int) $new_table['options']['datatables_paginate_entries'];
				if ( $new_table['options']['datatables_paginate_entries'] < 1 ) {
					$new_table['options']['datatables_paginate_entries'] = 10; // Default value.
				}
			}

			// Backward compatibility: Convert boolean or numeric string "table_head" and "table_foot" options to integer.
			if ( isset( $new_table['options']['table_head'] ) ) {
				$new_table['options']['table_head'] = absint( $new_table['options']['table_head'] );
			}
			if ( isset( $new_table['options']['table_foot'] ) ) {
				$new_table['options']['table_foot'] = absint( $new_table['options']['table_foot'] );
			}

			// Merge new options.
			$default_table = $this->get_table_template();
			$table['options'] = array_intersect_key( $table['options'], $default_table['options'] );
			$new_table['options'] = array_intersect_key( $new_table['options'], $default_table['options'] );
			$table['options'] = array_merge( $table['options'], $new_table['options'] );
		}
		// Table Visibility.
		$table['visibility']['rows'] = $new_table['visibility']['rows'];
		$table['visibility']['columns'] = $new_table['visibility']['columns'];

		// $table['author'] = get_current_user_id(); // We don't want this, as it would override the original author.
		// $table['created'] = wp_date( 'Y-m-d H:i:s' ); // We don't want this, as it would override the original datetime.
		$table['last_modified'] = wp_date( 'Y-m-d H:i:s' );
		$table['options']['last_editor'] = get_current_user_id();

		// Prevent issues if the "Custom Commands" field is not set, e.g. when non-admins have previously edited the table.
		if ( ! isset( $table['options']['datatables_custom_commands'] ) ) {
			$table['options']['datatables_custom_commands'] = '';
		}

		// Convert CSS classes and some DataTables 1.x parameters to the DataTables 2 variants.
		if ( '' !== $table['options']['datatables_custom_commands'] ) {
			$table['options']['datatables_custom_commands'] = TablePress::convert_datatables_api_data( $table['options']['datatables_custom_commands'] );
		}

		return $table;
	}

	/**
	 * Save the table options of a table (in a post meta field of the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $options Table options.
	 * @return bool True on success, false on error.
	 */
	protected function _add_table_options( int $post_id, array $options ): bool {
		$options = wp_json_encode( $options, TABLEPRESS_JSON_OPTIONS );
		return $this->model_post->add_meta_field( $post_id, $this->table_options_field_name, $options ); // @phpstan-ignore argument.type
	}

	/**
	 * Update the table options of a table (in a post meta field in the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $options Table options.
	 * @return bool True on success, false on error.
	 */
	protected function _update_table_options( int $post_id, array $options ): bool {
		$options = wp_json_encode( $options, TABLEPRESS_JSON_OPTIONS );
		return $this->model_post->update_meta_field( $post_id, $this->table_options_field_name, $options ); // @phpstan-ignore argument.type
	}

	/**
	 * Get the table options of a table (from a post meta field of the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed> Table options on success, empty array on error.
	 */
	protected function _get_table_options( int $post_id ): array {
		$options = $this->model_post->get_meta_field( $post_id, $this->table_options_field_name );
		if ( empty( $options ) ) {
			return array();
		}
		$options = (array) json_decode( $options, true );

		// Backward compatibility: Convert boolean "table_head" and "table_foot" options to integer.
		$options['table_head'] = absint( $options['table_head'] );
		$options['table_foot'] = absint( $options['table_foot'] );

		return $options;
	}

	/**
	 * Save the table visibility of a table (in a post meta field of the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int                                $post_id    Post ID.
	 * @param array{rows: int[], columns: int[]} $visibility Table visibility.
	 * @return bool True on success, false on error.
	 */
	protected function _add_table_visibility( int $post_id, array $visibility ): bool {
		$visibility = wp_json_encode( $visibility, TABLEPRESS_JSON_OPTIONS );
		return $this->model_post->add_meta_field( $post_id, $this->table_visibility_field_name, $visibility ); // @phpstan-ignore argument.type
	}

	/**
	 * Update the table visibility of a table (in a post meta field in the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int                                $post_id    Post ID.
	 * @param array{rows: int[], columns: int[]} $visibility Table visibility.
	 * @return bool True on success, false on error.
	 */
	protected function _update_table_visibility( int $post_id, array $visibility ): bool {
		$visibility = wp_json_encode( $visibility, TABLEPRESS_JSON_OPTIONS );
		return $this->model_post->update_meta_field( $post_id, $this->table_visibility_field_name, $visibility ); // @phpstan-ignore argument.type
	}

	/**
	 * Get the table visibility of a table (from a post meta field of the table's post).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array{rows: int[], columns: int[]} Table visibility on success, empty array on error.
	 */
	protected function _get_table_visibility( int $post_id ): array {
		$visibility = $this->model_post->get_meta_field( $post_id, $this->table_visibility_field_name );
		if ( empty( $visibility ) ) {
			return array(
				'rows'    => array(),
				'columns' => array(),
			);
		}
		return json_decode( $visibility, true );
	}

	/**
	 * Merge existing Table Options with default Table Options,
	 * remove (no longer) existing options, after a table scheme change,
	 * for all tables.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Add optional $remove_old_options parameter.
	 *
	 * @param bool $remove_old_options Optional. Whether old table options should be removed from the database. Default true.
	 */
	public function merge_table_options_defaults( bool $remove_old_options = true ): void {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// Prime the meta cache with the table options of all tables.
		update_meta_cache( 'post', array_values( $table_post ) );

		// Get default Table with default Table Options.
		$default_table = $this->get_table_template();

		// Go through all tables (this loop now uses the WP cache).
		foreach ( $table_post as $post_id ) {
			$table_options = $this->_get_table_options( $post_id );

			/**
			 * Filters the Table Options before they are merged with the default Table Options.
			 *
			 * @since 3.0.0
			 *
			 * @param array<string, mixed> $table_options         Table Options.
			 * @param array<string, mixed> $default_table_options Default Table Options.
			 * @param bool                 $remove_old_options    Whether old table options should be removed from the database.
			 * @param int                  $post_id               Post ID of the table.
			 */
			$table_options = apply_filters( 'tablepress_table_options_before_merge', $table_options, $default_table['options'], $remove_old_options, $post_id );

			if ( $remove_old_options ) {
				// Remove old (i.e. no longer existing) Table Options.
				$table_options = array_intersect_key( $table_options, $default_table['options'] );
			}
			// Merge current into new Table Options.
			$table_options = array_merge( $default_table['options'], $table_options );
			$this->_update_table_options( $post_id, $table_options );
		}
	}

	/**
	 * Updates all tables' "Custom Commands" to use DataTables 2 variants instead of old DataTables 1.x CSS classes and parameters
	 *
	 * @since 3.0.0
	 */
	public function update_custom_commands_datatables_tp30(): void {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		// Prime the meta cache with the table options of all tables.
		update_meta_cache( 'post', array_values( $table_post ) );

		foreach ( $table_post as $table_id => $post_id ) {
			$table_options = $this->_get_table_options( $post_id );

			// Fix tables where the "Custom Commands" entry is missing entirely.
			if ( ! isset( $table_options['datatables_custom_commands'] ) ) {
				$table_options['datatables_custom_commands'] = '';
				$this->_update_table_options( $post_id, $table_options );
				continue;
			}

			// Nothing to do if there are no "Custom Commands".
			if ( '' === $table_options['datatables_custom_commands'] ) {
				continue;
			}
			// Run search/replace.
			$old_custom_commands = $table_options['datatables_custom_commands'];
			$table_options['datatables_custom_commands'] = TablePress::convert_datatables_api_data( $table_options['datatables_custom_commands'] );
			// No need to save (which runs a DB query) if nothing was replaced in the "Custom Commands".
			if ( $old_custom_commands === $table_options['datatables_custom_commands'] ) {
				continue;
			}

			$this->_update_table_options( $post_id, $table_options );
		}
	}

	/**
	 * Invalidate all table output caches, e.g. after a plugin update.
	 *
	 * @since 1.0.0
	 */
	public function invalidate_table_output_caches(): void {
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) ) {
			return;
		}

		foreach ( $table_post as $table_id => $post_id ) {
			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.
			$this->invalidate_table_output_cache( $table_id );
		}
	}

	/**
	 * Add mime type field to existing posts with the TablePress Custom Post Type,
	 * so that other plugins know that they are not dealing with plain text.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function add_mime_type_to_posts(): void {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_mime_type' => 'application/json' ), array( 'post_type' => $this->model_post->get_post_type() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Add a table ID for a post (table) that was imported through the WP WXR importer to the table ID to post ID map.
	 *
	 * @since 1.5.0
	 *
	 * @param int|WP_Error         $post_id          Post ID of the imported post on success. 0 or WP_Error on failure.
	 * @param int                  $original_post_id Original post ID that the post had on the site where it was exported from.
	 * @param array<string, mixed> $postdata         Post data that was imported into the database.
	 * @param array<string, mixed> $post             Original post data as it was exported.
	 */
	public function add_table_id_on_wp_import( /* int|WP_Error */ $post_id, int $original_post_id, array $postdata, array $post ): void {
		// Bail if the post could not be imported or if the post is not a TablePress table.
		if ( is_wp_error( $post_id ) || $this->model_post->get_post_type() !== $postdata['post_type'] ) {
			return;
		}

		// Extract the table IDs from the `_tablepress_export_table_id` post meta field.
		$table_ids = array();
		if ( isset( $post['postmeta'] ) && is_array( $post['postmeta'] ) ) {
			foreach ( $post['postmeta'] as $postmeta ) {
				if ( '_tablepress_export_table_id' === $postmeta['key'] ) {
					$table_ids = $postmeta['value'];
					$table_ids = explode( ',', $table_ids );
					break;
				}
			}
		}

		// Save the post ID for each of the table IDs.
		$post_id_saved = false;
		foreach ( $table_ids as $table_id ) {
			$table_id = (string) preg_replace( '/[^a-zA-Z0-9_-]/', '', $table_id );
			if ( '' === $table_id || $this->table_exists( $table_id ) ) {
				continue;
			}
			$this->_update_post_id( $table_id, $post_id );
			$post_id_saved = true;
		}

		// Save the post ID for a new table ID if it could not be saved for any of the imported table IDs.
		if ( ! $post_id_saved ) {
			$table_id = $this->_get_new_table_id();
			$this->_update_post_id( $table_id, $post_id );
		}
	}

	/**
	 * Remove the `_tablepress_export_table_id` post meta field from the fields that are imported during a WP WXR import.
	 *
	 * @since 1.5.0
	 *
	 * @param array<string, mixed> $postmeta Post meta fields for the post.
	 * @param int                  $post_id  Post ID.
	 * @param array<string, mixed> $post     Post.
	 * @return array<string, mixed> Modified post meta fields.
	 */
	public function prevent_table_id_post_meta_import_on_wp_import( array $postmeta, int $post_id, array $post ): array {
		// Bail if the post is not a TablePress table.
		if ( $this->model_post->get_post_type() !== $post['post_type'] ) {
			return $postmeta;
		}

		// Remove the `_tablepress_export_table_id` post meta field from the post meta fields.
		foreach ( $postmeta as $index => $meta ) {
			if ( '_tablepress_export_table_id' === $meta['key'] ) {
				unset( $postmeta[ $index ] );
			}
		}

		return $postmeta;
	}

	/**
	 * Add the table IDs for an exported post (table) to the WP WXR export file.
	 *
	 * The table IDs for a table are exported in a faked post meta field.
	 * As there's no action for adding extra data to the WXR export file, we hijack the `wxr_export_skip_postmeta` filter hook.
	 *
	 * @since 1.5.0
	 *
	 * @param bool     $skip     Whether to skip the current post meta. Default false.
	 * @param string   $meta_key Current meta key.
	 * @param stdClass $meta     Current meta object.
	 * @return bool Whether to skip the current post meta (unchanged $skip parameter).
	 */
	public function add_table_id_to_wp_export( bool $skip, string $meta_key, stdClass $meta ): bool {
		// Bail if the exporter doesn't process a TablePress table right now.
		if ( $this->table_options_field_name !== $meta_key ) {
			return $skip;
		}

		// Find all table IDs that map to the post ID of the table that is currently being exported.
		$table_post = $this->tables->get( 'table_post' );
		$table_ids = array_keys( $table_post, (int) $meta->post_id, true );

		// Bail if no table IDs are mapped to this post ID.
		if ( empty( $table_ids ) ) {
			return $skip;
		}

		// Pretend that there is a `_tablepress_export_table_id` post meta field with the list of table IDs.
		$key = '_tablepress_export_table_id';

		/**
		 * Load WP export functions.
		 */
		require_once ABSPATH . 'wp-admin/includes/export.php'; // @phpstan-ignore requireOnce.fileNotFound (This is a WordPress core file that always exists.)
		$value = wxr_cdata( implode( ',', $table_ids ) );

		// Hijack the filter and print extra XML code for our faked post meta field.
		// phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
		echo <<<WXR
					<wp:postmeta>
						<wp:meta_key>{$key}</wp:meta_key>
						<wp:meta_value>{$value}</wp:meta_value>
					</wp:postmeta>\n
			WXR;
		// phpcs:enable

		return $skip;
	}

	/**
	 * Delete the WP_Option of the model.
	 *
	 * @since 1.0.0
	 */
	public function destroy(): void {
		$this->tables->delete();
	}

} // class TablePress_Table_Model
