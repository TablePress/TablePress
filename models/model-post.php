<?php
/**
 * Post Model
 *
 * @package TablePress
 * @subpackage Post Model
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Post Model class
 *
 * @since 1.0.0
 */
class TablePress_Post_Model extends TablePress_Model {

	/**
	 * @var string Name of the "Custom Post Type" for the tables
	 *
	 * @since 1.0.0
	 */
	protected $post_type = 'tablepress_table';

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->_register_post_type(); // we are on WP "init" hook already
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function _register_post_type() {
		$this->post_type = apply_filters( 'tablepress_post_type', $this->post_type );
		$post_type_args = array(
			'labels' => array(
				'name' => 'TP Tables',
				'singular_name' => 'TP Table',
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
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param array $post Post
	 * @return int Post ID of the new post
	 */
	public function insert( $post = array() ) {
		$default_post = array(
			'ID' => false, // false on new insert, post ID on update
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

		$post_id = wp_insert_post( $post );
		return $post_id;
	}
	
	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param array $post Post
	 * @return int Post ID of the updated post
	 */
	public function update( $post ) {
		$default_post = array(
			'ID' => false, // false on new insert, post ID on update
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
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int Post ID
	 * @return array Post
	 */
	public function get( $post_id ) {
		$post = get_post( $post_id, ARRAY_A ); // wp_get_single_post( $post_id, ARRAY_A );
		return $post;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int Post ID
	 * @return array Post
	 */
	public function delete( $post_id ) {
		$post = wp_delete_post( $post_id, true ); // force delete, although for CPT this is automatic in this function
		return $post;
	}

	/**
	 *
	 * (yet unused)
	 *
	 * @since 1.0.0
	 *
	 * @param int Post ID
	 * @return array Post
	 */
	public function trash( $post_id ) {
		$post = wp_trash_post( $post_id );
		return $post;
	}

	/**
	 *
	 * (yet unused)
	 *
	 * @since 1.0.0
	 *
	 * @param int Post ID
	 * @return array Post
	 */
	public function untrash( $post_id ) {
		$post = wp_untrash_post( $post_id );
		return $post;
	}

	/**
	 *
	 * (currently for debug only)
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of posts
	 */
	public function count_posts() {
		$count = array_sum( (array)wp_count_posts( $this->post_type ) ); // original return value is object with the counts for each post_status
		return $count;
	}

	/**
	 * Post Meta fields
	 */

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param string $field
	 * @param string $value
	 * @return bool
	 */
	public function add_meta_field( $post_id, $field, $value ) {
		$success = add_post_meta( $post_id, $field, $value, true ); // true means unique
		return $success;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param string $field
	 * @param string $value
	 * @return bool
	 */
	public function update_meta_field( $post_id, $field, $value, $prev_value = '' ) {
		$success = update_post_meta( $post_id, $field, $value, $prev_value );
		return $success;
	}

	/**
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param string $field
	 * @return string Value
	 */
	public function get_meta_field( $post_id, $field ) {
		$value = get_post_meta( $post_id, $field, true ); // true means single value
		return $value;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param string $field
	 * @return bool
	 */
	public function delete_meta_field( $post_id, $field ) {
		$success = delete_post_meta( $post_id, $field, true ); // true means single value
		return $success;
	}

} // class TablePress_Post_Model