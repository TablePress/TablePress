<?php
/**
 * Post Model
 *
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Post Model class
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Post_Model extends TablePress_Model {

	/**
	 * Name of the "Custom Post Type" for the tables
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $post_type = 'tablepress_table';

	/**
	 * Init the model by registering the Custom Post Type
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->_register_post_type(); // we are on WP "init" hook already
	}

	/**
	 * Register the Custom Post Type which the tables use
	 *
	 * @since 1.0.0
	 * @uses register_post_type()
	 */
	protected function _register_post_type() {
		$this->post_type = apply_filters( 'tablepress_post_type', $this->post_type );
		$post_type_args = array(
			'labels' => array(
				'name' => 'TP Tables' // 'TablePress Tables' is too long for the admin menu
			),
			'public' => false,
			'show_ui' => false,
			'query_var' => false,
			'rewrite' => false,
			'supports' => array( 'title', 'editor', 'excerpt', 'revisions' ),
			'can_export' => false
		);
		$post_type_args = apply_filters( 'tablepress_post_type_args', $post_type_args );
		register_post_type( $this->post_type, $post_type_args );
	}

	/**
	 * Insert a post with the correct Custom Post Type and default values in the the wp_posts table in the database
	 *
	 * @since 1.0.0
	 * @uses wp_insert_post()
	 *
	 * @param array $post Post to insert
	 * @return int Post ID of the inserted post on success, int 0 on error
	 */
	public function insert( $post ) {
		$default_post = array(
			'ID' => false, // false on new insert, but existing post ID on update
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => '',
			'post_category' => false,
			'post_content' => '',
			'post_date' => '',
			'post_date_gmt' => '',
			'post_excerpt' => '',
			'post_name' => '',
			'post_parent' => 0,
			'post_password' => '',
			'post_status' => 'publish',
			'post_title' => '',
			'post_type' => $this->post_type,
			'to_ping' => ''
		);
		$post = array_merge( $default_post, $post );
		$post_id = wp_insert_post( $post, false ); // false means: no WP_Error object on error, but int 0
		return $post_id;
	}
	
	/**
	 * Update an existing post with the correct Custom Post Type and default values in the the wp_posts table in the database
	 *
	 * @since 1.0.0
	 * @uses wp_update_post()
	 *
	 * @param array $post Post
	 * @return int Post ID of the updated post on success, int 0 on error
	 */
	public function update( $post ) {
		$default_post = array(
			'ID' => false, // false on new insert, but existing post ID on update
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => '',
			'post_category' => false,
			'post_content' => '',
			'post_date' => '',
			'post_date_gmt' => '',
			'post_excerpt' => '',
			'post_name' => '',
			'post_parent' => 0,
			'post_password' => '',
			'post_status' => 'publish',
			'post_title' => '',
			'post_type' => $this->post_type,
			'tags_input' => '',
			'to_ping' => ''
		);
		$post = array_merge( $default_post, $post );
		$post_id = wp_update_post( $post );
		return $post_id;
	}
	
	/**
	 * Get a post from the wp_posts table in the database
	 *
	 * @since 1.0.0
	 * @uses get_post()
	 *
	 * @param int Post ID
	 * @return array|bool Post on success, false on error
	 */
	public function get( $post_id ) {
		$post = get_post( $post_id, ARRAY_A, 'raw' );
		// $post = wp_get_single_post( $post_id, ARRAY_A ); would also give categories/tags in the $post array
		if ( is_null( $post ) )
			return false;
		return $post;
	}

	/**
	 * Delete a post (and all revisions) from the wp_posts table in the database
	 *
	 * @since 1.0.0
	 * @uses wp_delete_post()
	 *
	 * @param int Post ID
	 * @return array|bool Post on success, false on error
	 */
	public function delete( $post_id ) {
		$post = wp_delete_post( $post_id, true ); // force delete, although for CPTs this is automatic in this function
		return $post;
	}

	/**
	 * Move a post to the trash (if trash is globally enabled), instead of directly deleting the post
	 * (yet unused)
	 *
	 * @since 1.0.0
	 * @uses wp_trash_post()
	 *
	 * @param int Post ID
	 * @return array|bool Post on success, false on error
	 */
	public function trash( $post_id ) {
		$post = wp_trash_post( $post_id );
		return $post;
	}

	/**
	 * Restore a post from the trash
	 * (yet unused)
	 *
	 * @since 1.0.0
	 * @uses wp_untrash_post()
	 *
	 * @param int Post ID
	 * @return array|bool Post on success, false on error
	 */
	public function untrash( $post_id ) {
		$post = wp_untrash_post( $post_id );
		return $post;
	}

	/**
	 * Count the number of posts with the model's CPT in the wp_posts table in the database
	 * (currently for debug only)
	 *
	 * @since 1.0.0
	 * @uses wp_count_posts()
	 *
	 * @return int Number of posts
	 */
	public function count_posts() {
		$count = array_sum( (array)wp_count_posts( $this->post_type ) ); // original return value is object with the counts for each post_status
		return $count;
	}

	/**
	 * Add a post meta field to a post
	 *
	 * @since 1.0.0
	 * @uses add_post_meta()
	 *
	 * @param int $post_id ID of the post for which the field shall be added
	 * @param string $field Name of the post meta field
	 * @param string $value Value of the post meta field
	 * @return bool True on success, false on error
	 */
	public function add_meta_field( $post_id, $field, $value ) {
		$success = add_post_meta( $post_id, $field, $value, true ); // true means unique
		return $success;
	}

	/**
	 * Update the value of a post meta field of a post
	 * If the field does not yet exist, it is added.
	 *
	 * @since 1.0.0
	 * @uses update_post_meta()
	 *
	 * @param int $post_id ID of the post for which the field shall be updated
	 * @param string $field Name of the post meta field
	 * @param string $value Value of the post meta field
	 * @param string $prev_value (optional) Previous value of the post meta field
	 * @return bool True on success, false on error
	 */
	public function update_meta_field( $post_id, $field, $value, $prev_value = '' ) {
		$success = update_post_meta( $post_id, $field, $value, $prev_value );
		return $success;
	}

	/**
	 * Get the value of a post meta field of a post
	 *
	 * @since 1.0.0
	 * @uses get_post_meta()
	 *
	 * @param int $post_id ID of the post for which the field shall be retrieved
	 * @param string $field Name of the post meta field
	 * @return string Value of the meta field
	 */
	public function get_meta_field( $post_id, $field ) {
		$value = get_post_meta( $post_id, $field, true ); // true means single value
		return $value;
	}

	/**
	 * Delete a post meta field of a post
	 *
	 * @since 1.0.0
	 * @uses delete_post_meta()
	 *
	 * @param int $post_id ID of the post of which the field shall be deleted
	 * @param string $field Name of the post meta field
	 * @return bool True on success, false on error
	 */
	public function delete_meta_field( $post_id, $field ) {
		$success = delete_post_meta( $post_id, $field, true ); // true means single value
		return $success;
	}

} // class TablePress_Post_Model