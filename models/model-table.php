<?php
/**
 * Table Model
 *
 * @package TablePress
 * @subpackage Table Model
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Table Model class
 *
 * @since 1.0.0
 */
class TablePress_Table_Model extends TablePress_Model {

	/**
	 * @var object Instance of the Post Type Model
	 *
	 * @since 1.0.0
	 */
	protected $model_post;

	/**
	 * @var string Name of the Post Meta Field for table options
	 *
	 * @since 1.0.0
	 */
	protected $table_options_field_name = '_tablepress_table_options';

	/**
	 * @var array Default set of tables
	 *
	 * @since 1.0.0
	 */
	protected $default_tables = array(
		'last_id' => 0,
		'table_post' => array()
	);

	/**
	 * @var object Instance of WP_Option class for the list of tables
	 *
	 * @since 1.0.0
	 */
	protected $tables;

	/**
	 *
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
	 * Debug loading/storing
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return array Current set of tables
	 */
	public function _debug_get_tables() {
		return $this->tables->get();
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @var array $tables New set of tables
	 */
	public function _debug_update_tables( $tables ) {
		$this->tables->update( $tables );
	}

	/**
	 * Table <-> Post conversion
	 */

	/**
	 *
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
	 *
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
			'data' => json_decode( $post['post_content'], true ),
		);

		return $table;
	}

	/**
	 * Table Handling
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return array Table
	 */
	public function load( $table_id ) {
		$post_id = $this->_get_post_id( $table_id );
		$post = $this->model_post->get( $post_id );
		$table = $this->_post_to_table( $post, $table_id );
		$table['options'] = $this->_get_table_options( $post_id );
		return $table;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of Tables
	 */
	public function load_all() {
		$tables = array();

		// hacky approach, might consider function that returns array of table IDs to loop through with _get_post_id() in loop

		$table_post = $this->tables->get( 'table_post' );
		foreach ( $table_post as $table_id => $post_id ) {
			if ( 0 == $table_id )
				continue;
			$tables[ $table_id ] = $this->load( $table_id );
		}
		return $tables;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table (needs to include $table['id'])
	 * @return mixed False on error, int Post ID on success
	 */
	public function save( $table ) {
		$post_id = $this->_get_post_id( $table['id'] );
		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->update( $post );

		$return = false;
		if ( $post_id == $new_post_id ) {
			$options_saved = $this->_update_table_options( $post_id, $table['options'] );
			if ( $options_saved ) {
				// $this->_update_post_id( $table['id'], $new_post_id ); // unnecessary now, maybe useful for revisioning/draft feature later
				$return = $table['id'];
			}
		}

		return $return;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table (without $table['id'])
	 * @return mixed False on error, int Post ID on success
	 */
	public function add( $table ) { // no table['id']
		$post_id = false; // to insert table
		$post = $this->_table_to_post( $table, $post_id );
		$new_post_id = $this->model_post->insert( $post );
		$options_saved = $this->_add_table_options( $new_post_id, $table['options'] );

		if ( 0 == $new_post_id || is_wp_error( $new_post_id ) )
			return false;

		// check $options_saved?!?!

		// at this point, post was successfully added
		$table_id = $this->_get_new_table_id();
		$this->_update_post_id( $table_id, $new_post_id );
		return $table_id;
	}
	
	/**
	 *
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
		$deleted = $this->model_post->delete( $post_id );
		// Post Meta fields will be deleted automatically by that function
		if ( $deleted ) {
			$this->_remove_post_id( $table_id );
			return true;
		}
		
		return false;
	}

	/**
	 * Helpers
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return bool Whether the table exists
	 */
	public function table_exists( $table_id ) {
		$table_post = $this->tables->get( 'table_post' );
		return isset( $table_post[ $table_id ] );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param bool $single_value (optional) Whether to return just the number of tables from the list, or also count in the DB
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
	 * ID handling
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @return int Post ID
	 */
	protected function _get_post_id( $table_id ) {
		$post_id = 0;
		$table_post = $this->tables->get( 'table_post' );
		if ( isset( $table_post[ $table_id ] ) )
			$post_id = $table_post[ $table_id ];
		return $post_id;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param string $table_id Table ID
	 * @param int $post_id Post ID
	 */
	protected function _update_post_id( $table_id, $post_id ) {
		$tables = $this->tables->get();

		$tables['table_post'][ $table_id ] = $post_id;

		ksort( $tables['table_post'] );
		$this->tables->update( $tables );
	}
	
	/**
	 *
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
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return string Unused ID (e.g. for a new table)
	 */
	protected function _get_new_table_id() {
		$tables = $this->tables->get();
		// need to check new ID candidate, because a higher one might be in use, if a table ID was manually changed
		do {
			$tables['last_id'] ++;
		} while ( $this->table_exists( $tables['last_id'] ) );
		$this->tables->update( $tables );
		return (string) $tables['last_id'];
	}

	/**
	 * Table Options functions
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $options Array of Table Options
	 * @return bool Whether this was successful
	 */
	protected function _add_table_options( $post_id, $options ) {
		$options = json_encode( $options );
		$success = $this->model_post->add_meta_field( $post_id, $this->table_options_field_name, $options );
		return $success;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param array $options Array of Table Options
	 * @return bool Whether this was successful
	 */
	protected function _update_table_options( $post_id, $options ) {
		$options = json_encode( $options );
		// this is stupid, but necessary:
		$prev_options = json_encode( $this->_get_table_options( $post_id ) );
		$success = $this->model_post->update_meta_field( $post_id, $this->table_options_field_name, $options, $prev_options );
		return $success;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @return array Array of Table Options
	 */
	protected function _get_table_options( $post_id ) {
		$options = $this->model_post->get_meta_field( $post_id, $this->table_options_field_name );
		$options = json_decode( $options, true );
		return $options;
	}

} // class TablePress_Table_Model