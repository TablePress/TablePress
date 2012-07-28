<?php
/**
 * Frontend Controller for TablePress with functionality for the frontend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Frontend Controller class, extends Base Controller Class
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Frontend_Controller extends TablePress_Controller {

	/**
	 * List of tables that are shown for the current request
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $shown_tables = array();

	/**
	 * Initiate Frontend functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// enqueue CSS files
		// if ( $this->options['use_default_css'] || $this->options['use_custom_css'] )
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_css' ) );

		// add DataTables invocation calls
		add_action( 'wp_print_footer_scripts', array( &$this, 'add_datatables_calls' ), 11 ); // after inclusion of files

		// shortcode "table-info" needs to be declared before "table"! Otherwise it will not be recognized!
		// add_shortcode( 'table-info', array( &$this, 'shortcode_table_info' ) );
		add_shortcode( TablePress::$shortcode, array( &$this, 'shortcode_table' ) );
		// make Shortcodes work in text widgets
		// add_filter( 'widget_text', array( &$this, 'widget_text_filter' ) );

		// load Template Tag functions
		//require_once ( TABLEPRESS_ABSPATH . 'libraries/template-tag-functions.php' );
	}

	/**
	 * Enqueue CSS files for default CSS and "Custom CSS" (if desired)
	 *
	 * @since 1.0.0
	 */
	public function enqueue_css() {
		// @TODO: Add check for whether default CSS is desired at all
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.dev' : '';
		$default_css_url = plugins_url( "css/default{$suffix}.css", TABLEPRESS__FILE__ );
		$default_css_url = apply_filters( 'tablepress_default_css_url', $default_css_url );
		wp_enqueue_style( 'tablepress-default', $default_css_url, array(), TablePress::version );

		// @TODO: Add check for whether "Custom CSS" is desired at all
		$use_custom_css_from_option = true;
		if ( $this->model_options->get( 'use_custom_css_file' ) ) {
			// fall back to "Custom CSS" in options, if it could not be retrieved from file
			$custom_css_file_contents = $this->model_options->load_custom_css_from_file();
			if ( ! empty( $custom_css_file_contents ) ) {
				$use_custom_css_from_option = false;
				$custom_css_url = content_url( 'tablepress-custom.css' );
				$custom_css_url = apply_filters( 'tablepress_custom_css_url', $custom_css_url );
				$custom_css_dependencies = array( 'tablepress-default' ); // if default CSS is desired, but also handled internally
				$custom_css_version = $this->model_options->get( 'custom_css_version' );
				$custom_css_version = apply_filters( 'tablepress_custom_css_version', $custom_css_version );
				wp_enqueue_style( 'tablepress-custom', $custom_css_url, $custom_css_dependencies, $custom_css_version );
			}
		}

		if ( $use_custom_css_from_option ) {
			// get "Custom CSS" from options
			$custom_css = trim( $this->model_options->get( 'custom_css' ) );
			if ( ! empty( $custom_css ) )
				wp_add_inline_style( 'tablepress-default', $custom_css ); // handle of the file to which the <style> shall be appended
		}
	}

	/**
	 * Enqueue the DataTables JavaScript library (and jQuery)
	 *
	 * @since 1.0.0
	 */
	public function enqueue_datatables() {
		$js_file = 'js/jquery.datatables.min.js';
		$js_url = plugins_url( $js_file, TABLEPRESS__FILE__ );
		$js_url = apply_filters( 'tablepress_datatables_js_url', $js_url, $js_file );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tablepress-datatables', $js_url, array( 'jquery' ), TablePress::version, true );
	}

	/**
	 * Add JS code for invocation of DataTables JS library
	 *
	 * @since 1.0.0
	 */
	public function add_datatables_calls() {
		if ( empty( $this->shown_tables ) )
			return; // there are no tables with activated DataTables

		// generate the specific JS commands, depending on chosen features on the "Edit" screen and the Shortcode parameters
		$commands = array();

		foreach ( $this->shown_tables as $table_id => $table_store ) {
			if ( empty( $table_store['instances'] ) )
				continue;
			foreach( $table_store['instances'] as $html_id => $js_options ) {
				$parameters = array();

				if ( $js_options['datatables_tabletools'] )
					$parameters['sDom'] = '"sDom": \'T<"clear">lfrtip\'';
				$datatables_locale = apply_filters( 'tablepress_datatables_locale', get_locale() );
				$language_file = "i18n/datatables/lang-{$datatables_locale}.txt";
				$language_file = ( file_exists( TABLEPRESS_ABSPATH . $language_file ) ) ? '/' . $language_file : '/i18n/datatables/lang-default.txt';
				$language_file_url = plugins_url( $language_file, TABLEPRESS__FILE__ );
				$root_relative_language_file_url = parse_url( $language_file_url, PHP_URL_PATH );
				$root_relative_language_file_url = apply_filters( 'tablepress_datatables_language_file_url', $root_relative_language_file_url, $language_file_url );
				if ( ! empty( $root_relative_language_file_url ) )
					$parameters['oLanguage'] = '"oLanguage":{"sUrl": "' . $root_relative_language_file_url . '"}'; // root relative language file URL
				// these parameters need to be added for performance gain or to overwrite unwanted default behavior
				$parameters['aaSorting'] = '"aaSorting":[]'; // no initial sort
				$parameters['bSortClasses'] = '"bSortClasses":false'; // don't add additional classes, to speed up sorting
				$parameters['asStripeClasses'] = '"asStripeClasses":' . ( ( $js_options['alternating_row_colors'] ) ? "['even','odd']" : '[]' ); // alternating row colors is default, so remove them if not wanted with []
				// the following options are activated by default, so we only need to "false" them if we don't want them, but don't need to "true" them if we do
				if ( ! $js_options['datatables_sort'] )
					$parameters['bSort'] = '"bSort":false';
				if ( ! $js_options['datatables_paginate'] )
					$parameters['bPaginate'] = '"bPaginate":false';
				if ( $js_options['datatables_paginate'] && ! empty( $js_options['datatables_paginate_entries'] ) && 10 != $js_options['datatables_paginate_entries'] )
					$parameters['iDisplayLength'] = '"iDisplayLength":'. $js_options['datatables_paginate_entries'];
				if ( ! $js_options['datatables_lengthchange'] )
					$parameters['bLengthChange'] = '"bLengthChange":false';
				if ( ! $js_options['datatables_filter'] )
					$parameters['bFilter'] = '"bFilter":false';
				if ( ! $js_options['datatables_info'] )
					$parameters['bInfo'] = '"bInfo":false';
				if ( $js_options['datatables_scrollX'] ) {
					$parameters['sScrollX'] = '"sScrollX":"100%"';
					$parameters['bScrollCollapse'] = '"bScrollCollapse":true';
				}
				if ( ! empty( $js_options['datatables_custom_commands'] ) )
					$parameters['custom_commands'] = $js_options['datatables_custom_commands'];

				$parameters = apply_filters( 'tablepress_datatables_parameters', $parameters, $table_id, $html_id, $js_options );
				$parameters = implode( ', ', $parameters );
				$parameters = ( ! empty( $parameters ) ) ? '{' . $parameters . '}' : '';

				$command = "$('#{$html_id}').dataTable({$parameters});";
				$command = apply_filters( 'tablepress_datatables_command', $command, $html_id, $parameters, $table_id, $js_options );
				if ( ! empty( $command ) )
					$commands[] = "\t{$command}";
			}
		}

		$commands = implode( "\n", $commands );
		$commands = apply_filters( 'tablepress_all_datatables_commands', $commands );
		if ( ! empty( $commands ) )
			echo <<<JS
<script type="text/javascript">
jQuery(document).ready(function($){
{$commands}
});
</script>
JS;
	}

	/**
	 * Handle Shortcode [table id=<ID> /] in the_content()
	 *
	 * @since 1.0.0
	 *
	 * @param array $shortcode_atts List of attributes that where included in the Shortcode
	 * @return string Resulting HTML code for the table with the ID <ID>
	 */
	public function shortcode_table( $shortcode_atts ) {
		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );

		$default_shortcode_atts = $_render->get_default_render_options();
		$default_shortcode_atts = apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_shortcode_atts );
		// parse Shortcode attributes, only allow those that are specified
		$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts );
		$shortcode_atts = apply_filters( 'tablepress_shortcode_table_shortcode_atts', $shortcode_atts );

		// check, if a table with the given ID exists
		$table_id = $shortcode_atts['id'];
		if ( ! $this->model_table->table_exists( $table_id ) ) {
			$message = "[table &quot;{$table_id}&quot; not found /]<br />\n";
			$message = apply_filters( 'tablepress_table_not_found_message', $message, $table_id );
			return $message;
		}

		// load the table
		$table = $this->model_table->load( $table_id );
		if ( false === $table ) {
			$message = "[table &quot;{$table_id}&quot; could not be loaded /]<br />\n";
			$message = apply_filters( 'tablepress_table_load_error_message', $message, $table_id );
			return $message;
		}

		// determine options to use (if set in Shortcode, use those, otherwise use stored options, i.e. "Edit Table" screen)
		$render_options = array();
		foreach ( $shortcode_atts as $key => $value ) {
			// have to check this, because strings 'true' or 'false' are not recognized as boolean!
			if ( 'true' == strtolower( $value ) )
				$render_options[$key] = true;
			elseif ( 'false' == strtolower( $value ) )
				$render_options[$key] = false;
			elseif ( is_null( $value ) && isset( $table['options'][$key] ) )
				$render_options[$key] = $table['options'][$key];
			else
				$render_options[$key] = $value;
		}

		// generate unique HTML ID, depending on how often this table has already been shown on this page
		if ( ! isset( $this->shown_tables[$table_id] ) ) {
			$this->shown_tables[$table_id] = array(
				'count' => 0,
				'instances' => array()
			);
		}
		$this->shown_tables[$table_id]['count']++;
		$count = $this->shown_tables[$table_id]['count'];
		$render_options['html_id'] = "tablepress-{$table_id}";
		if( $count > 1 )
			$render_options['html_id'] .= "-no-{$count}";
		$render_options['html_id'] = apply_filters( 'tablepress_html_id', $render_options['html_id'], $table_id, $count );

		// eventually add this table to list of tables which have a JS library enabled and thus are to be included in the script's call in the footer
		if ( $render_options['use_datatables'] && $render_options['table_head'] && count( $table['data'] ) > 1 ) {
			// get options for the DataTables JavaScript library from the table's options
			$js_options = array (
				'alternating_row_colors' => $render_options['alternating_row_colors'],
				'datatables_sort' => $render_options['datatables_sort'],
				'datatables_paginate' => $render_options['datatables_paginate'],
				'datatables_paginate_entries' => $render_options['datatables_paginate_entries'],
				'datatables_lengthchange' => $render_options['datatables_lengthchange'],
				'datatables_filter' => $render_options['datatables_filter'],
				'datatables_info' => $render_options['datatables_info'],
				'datatables_scrollX' => $render_options['datatables_scrollX'],
				'datatables_tabletools' => $render_options['datatables_tabletools'],
				'datatables_custom_commands' => $render_options['datatables_custom_commands']
			);
			$js_options = apply_filters( 'tablepress_table_js_options', $js_options, $table_id, $render_options );
			$this->shown_tables[$table_id]['instances'][ $render_options['html_id'] ] = $js_options;
			$this->enqueue_datatables();
		}

		// generate "Edit Table" link
		$render_options['edit_table_url'] = '';
		/*
		if ( is_user_logged_in() && $this->model_options->get( 'frontend_edit_table_link' ) {
			$user_group = $this->model_options->get( 'user_access_plugin' );
			$capabilities = array(
				'admin' => 'manage_options',
				'editor' => 'publish_pages',
				'author' => 'publish_posts',
				'contributor' => 'edit_posts'
			);
			$min_capability = isset( $capabilities[ $user_group ] ) ? $capabilities[ $user_group ] : 'manage_options';
			$min_capability = apply_filters( 'tablepress_min_needed_capability', $min_capability );

			if ( current_user_can( $min_capability ) )
				$render_options['edit_table_url'] = TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) );
		}
		*/
		// @TODO: temporary for above:
		if ( is_user_logged_in() )
			$render_options['edit_table_url'] = TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) );

		$render_options = apply_filters( 'tablepress_table_render_options', $render_options, $table );

		// check if table output shall and can be loaded from the transient cache, otherwise generate the output
		$cache_name = "tablepress_table_output_{$table_id}"; // @TODO: use some sort of hash of the Shortcode here?
		if ( ! $render_options['cache_table_output'] || is_user_logged_in() ) {
			$output = get_transient( $cache_name );
			if ( false === $output ) {
				// render/generate the table HTML
				$_render->set_input( $table, $render_options );
				$output = $_render->get_output();

				if ( $render_options['cache_table_output'] && ! is_user_logged_in() )
					set_transient( $cache_name, $output, 60*60*24 ); // store $output in a transient, set cache timeout to 24 hours
			}
		}

		return $output;
	}

} // class TablePress_Frontend_Controller