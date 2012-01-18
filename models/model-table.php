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
	 * @var object
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
		'table_post' => array()
	);

	/**
	 * Instance of WP_Option class for the list of tables
	 *
	 * @since 1.0.0
	 *
	 * @var object
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
			'default_value' => $this->default_tables
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
	public function _debug_update_tables( $tables ) {
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
	protected function _table_to_post( $table, $post_id ) {
		if ( empty( $table['name'] ) )
			$table['name'] = '';
		if ( empty( $table['description'] ) )
			$table['description'] = '';
		if ( empty( $table['data'] ) )
			$table['data'] = array( array( '' ) );

		$post = array(
			'ID' => $post_id,
			'post_title' => $table['name'],
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
	 * @param array $post Post
	 * @param string $table_id Table ID
	 * @return array Table
	 */
	protected function _post_to_table( $post, $table_id ) {
		$table = array(
			'id' => $table_id,
			'name' => $post['post_title'],
			'description' => $post['post_excerpt'],
			'author' => $post['post_author'],
			'data' => json_decode( $post['post_content'], true ),
		);

		return $table;
	}

	/**
	 * Load a table
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return array|bool Table as an array on success, false on error
	 */
	public function load( $table_id ) {
		if ( empty( $table_id ) )
			return false;

		$post_id = $this->_get_post_id( $table_id );
		if ( 0 === $post_id )
			return false;

		$post = $this->model_post->get( $post_id );
		if ( false === $post )
			return false;

		$table = $this->_post_to_table( $post, $table_id );
		$table['options'] = $this->_get_table_options( $post_id );
		$table['visibility'] = $this->_get_table_visibility( $post_id );
		return $table;
	}

	/**
	 * Load all tables
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of Tables
	 */
	public function load_all() {
		$tables = array();
		$table_post = $this->tables->get( 'table_post' );
		if ( empty( $table_post ) )
			return array();

		$table_ids = array_keys( $table_post );
		$post_ids = array_values( $table_post );

		// get all table posts with one query, @see get_post() in WordPress, to prime the cache
		global $wpdb;
		$post_ids_list = implode( ',', $post_ids );
		$all_posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$post_ids_list})" );
		foreach ( $all_posts as $single_post ) {
			_get_post_ancestors($single_post);
			$single_post = sanitize_post( $single_post, 'raw' );
			wp_cache_add( $single_post->ID, $single_post, 'posts' );
		}
		// get all post meta data for all table posts, @see get_post_meta()
		update_meta_cache( 'post', $post_ids );

		// this loop now uses the WP cache
		foreach ( $table_ids as $table_id ) {
			$tables[ $table_id ] = $this->load( $table_id );
		}
		return $tables;
	}

	/**
	 * Save a table
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table (needs to include $table['id'])
	 * @return mixed False on error, int table ID on success
	 */
	public function save( $table ) {
		if ( empty( $table['id'] ) )
			return false;

		$post_id = $this->_get_post_id( $table['id'] );
		if ( 0 === $post_id )
			return false;

		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->update( $post );

		if ( 0 === $new_post_id || $post_id !== $new_post_id )
			return false;

		if ( ! isset( $table['options'] ) )
			$table['options'] = array();
		$options_saved = $this->_update_table_options( $new_post_id, $table['options'] );
		if ( ! $options_saved )
			return false;

		if ( ! isset( $table['visibility'] ) )
			$table['visibility'] = array();
		$visibility_saved = $this->_update_table_visibility( $new_post_id, $table['visibility'] );
		if ( ! $visibility_saved )
			return false;

		// at this point, post was successfully added
		return $table['id'];
	}

	/**
	 * Add a new table
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table (without $table['id'], otherwise it gets removed)
	 * @return mixed False on error, int table ID of the new table on success
	 */
	public function add( $table ) {
		if ( isset( $table['id'] ) )
			unset( $table['id'] );
		$post_id = false; // to insert table
		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->insert( $post );

		if ( 0 === $new_post_id )
			return false;

		if ( ! isset( $table['options'] ) )
			$table['options'] = array();
		$options_saved = $this->_add_table_options( $new_post_id, $table['options'] );
		if ( ! $options_saved )
			return false;

		if ( ! isset( $table['visibility'] ) )
			$table['visibility'] = array();
		$visibility_saved = $this->_add_table_visibility( $new_post_id, $table['visibility'] );
		if ( ! $visibility_saved )
			return false;

		// at this point, post was successfully added, now get an unused table ID
		$table_id = $this->_get_new_table_id();
		$this->_update_post_id( $table_id, $new_post_id );
		return $table_id;
	}

	/**
	 * Delete a table (and its options)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return bool False on error, true on success
	 */
	public function delete( $table_id ) {
		if ( ! $this->table_exists( $table_id ) )
			return false;

		$post_id = $this->_get_post_id( $table_id );
		$deleted = $this->model_post->delete( $post_id ); // Post Meta fields will be deleted automatically by that function

		if ( false === $deleted )
			return false;

		// if post was deleted successfully, remove the table ID from the list of tables
		$this->_remove_post_id( $table_id );
		return true;
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
		if ( $single_value )
			return $count_list;

		$count_db = $this->model_post->count_posts();
		return array( 'list' => $count_list, 'db' => $count_db );
	}

	/**
	 * Get the post ID of a given table ID (if the table ID exists)
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return int Post ID on success, int 0 on error
	 */
	protected function _get_post_id( $table_id ) {
		$post_id = 0;
		$table_post = $this->tables->get( 'table_post' );
		if ( isset( $table_post[ $table_id ] ) )
			$post_id = $table_post[ $table_id ];
		return $post_id;
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

		if ( isset( $tables['table_post'][ $table_id ] ) )
			unset( $tables['table_post'][ $table_id ] );

		$this->tables->update( $tables );
	}

	/**
	 * Change the table ID of a table
	 *
	 * @since 1.0.0
	 *
	 * @param int $old_id Old table ID
	 * @param int $new_id New table ID
	 * @return bool True on success, false on error
	 */
	public function change_table_id( $old_id, $new_id ) {
		$post_id = $this->_get_post_id( $old_id );
		if ( 0 === $post_id )
			return false;

		if ( $this->table_exists( $new_id ) )
			return false;

		$this->_update_post_id( $new_id, $post_id );
		$this->_remove_post_id( $old_id );
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
	 * Save the table options of a table (in a post meta field of the table's post)
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $options Table options
	 * @return bool True on success, false on error
	 */
	protected function _add_table_options( $post_id, $options ) {
		$options = json_encode( $options );
		$success = $this->model_post->add_meta_field( $post_id, $this->table_options_field_name, $options );
		return $success;
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
	protected function _update_table_options( $post_id, $options ) {
		$options = json_encode( $options );
		// we need to pass the previous value to make sure that an update takes place, to really get a successful (true) return result from the WP API
		$prev_options = json_encode( $this->_get_table_options( $post_id ) );
		$success = $this->model_post->update_meta_field( $post_id, $this->table_options_field_name, $options, $prev_options );
		return $success;
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
		if ( empty( $options ) )
			return array();
		$options = json_decode( $options, true );
		return $options;
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
	protected function _add_table_visibility( $post_id, $visibility ) {
		$visibility = json_encode( $visibility );
		$success = $this->model_post->add_meta_field( $post_id, $this->table_visibility_field_name, $visibility );
		return $success;
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
	protected function _update_table_visibility( $post_id, $visibility ) {
		$visibility = json_encode( $visibility );
		// we need to pass the previous value to make sure that an update takes place, to really get a successful (true) return result from the WP API
		$prev_visibility = json_encode( $this->_get_table_visibility( $post_id ) );
		$success = $this->model_post->update_meta_field( $post_id, $this->table_visibility_field_name, $visibility, $prev_visibility );
		return $success;
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
		if ( empty( $visibility ) )
			return array();
		$visibility = json_decode( $visibility, true );
		return $visibility;
	}

} // class TablePress_Table_Model