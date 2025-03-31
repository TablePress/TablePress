<?php
/**
 * TablePress Elementor Widget
 *
 * @package TablePress
 * @subpackage Elementor Widget
 * @author Tobias Bäthge
 * @since 3.1.0
 */

namespace TablePress\Elementor;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Elementor Widget Class
 *
 * @package TablePress
 * @subpackage Elementor Widget
 * @author Tobias Bäthge
 * @since 3.1.0
 */
class TablePressTableWidget extends \Elementor\Widget_Base {

	/**
	 * Gets the widget name.
	 *
	 * @since 3.1.0
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'tablepress-table';
	}

	/**
	 * Gets the widget title.
	 *
	 * @since 3.1.0
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'TablePress table', 'tablepress' );
	}

	/**
	 * Gets the widget icon.
	 *
	 * @since 3.1.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon(): string {
		return 'tablepress-elementor-icon';
	}

	/**
	 * Gets the widget categories.
	 *
	 * @since 3.1.0
	 *
	 * @return string[] Widget categories.
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Gets the widget keywords.
	 *
	 * @since 3.1.0
	 *
	 * @return string[] Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'table', 'spreadsheet', 'csv', 'excel', 'data' );
	}

	/**
	 * Gets the custom help URL.
	 *
	 * @since 3.1.0
	 *
	 * @return string Widget help URL.
	 */
	public function get_custom_help_url(): string {
		return 'https://tablepress.org/support/';
	}

	/**
	 * Gets the widget stack and hides the "Advanced" tab.
	 *
	 * @since 3.1.0
	 *
	 * @param bool $with_common_controls Whether to include common controls.
	 * @return array<string, array<string, string>> Widget stack.
	 */
	public function get_stack( $with_common_controls = true ) {
		$stack = parent::get_stack( $with_common_controls ); // @phpstan-ignore staticMethod.notFound (Elementor methods are not in the stubs.)
		unset( $stack['tabs']['advanced'] );
		return $stack;
	}

	/**
	 * Gets the widget upsale data.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, string|bool>  Widget upsale data.
	 */
	protected function get_upsale_data(): array {
		return array(
			'condition'    => tb_tp_fs()->is_free_plan(),
			'image'        => plugins_url( 'admin/img/tablepress.svg', TABLEPRESS__FILE__ ),
			'image_alt'    => esc_attr__( 'Upgrade to TablePress Pro!', 'tablepress' ),
			'title'        => esc_html__( 'Upgrade to TablePress Pro!', 'tablepress' ),
			'description'  => esc_html__( 'Check out the TablePress premium versions and give your tables super powers!', 'tablepress' ),
			'upgrade_url'  => 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=button&utm_content=elementor-widget',
			'upgrade_text' => esc_html__( 'Upgrade Now', 'tablepress' ),
		);
	}

	/**
	 * Gets whether the widget requires an inner wrapper.
	 *
	 * This is used to determine whether to optimize the DOM size.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether to optimize the DOM size.
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Gets whether the element returns dynamic content.
	 *
	 * This is used to determine whether to cache the element output or not.
	 *
	 * @since 3.1.0
	 *
	 * @return bool Whether to cache the element output.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Registers the widget controls.
	 *
	 * Adds input fields to allow the user to customize the widget settings.
	 *
	 * @since 3.1.0
	 */
	protected function register_controls(): void {
		$this->start_controls_section( // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)
			'table',
			array(
				'label' => esc_html__( 'Table', 'tablepress' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT, // @phpstan-ignore classConstant.notFound (Elementor constants are not in the stubs.)
			),
		);

		$tables = array();
		// Load all table IDs without priming the post meta cache, as table options/visibility are not needed.
		$table_ids = \TablePress::$model_table->load_all( false );
		foreach ( $table_ids as $table_id ) {
			// Load table, without table data, options, and visibility settings.
			$table = \TablePress::$model_table->load( $table_id, false, false );

			// Skip tables that could not be loaded.
			if ( is_wp_error( $table ) ) {
				continue;
			}

			if ( '' === trim( $table['name'] ) ) {
				$table['name'] = __( '(no name)', 'tablepress' );
			}
			$tables[ $table_id ] = esc_html( sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), $table_id, $table['name'] ) );
		}

		/**
		 * Filters the list of table IDs and names that is passed to the block editor, and is then used in the dropdown of the TablePress table block.
		 *
		 * @since 2.0.0
		 *
		 * @param array<string, string> $tables List of table names, the table ID is the array key.
		 */
		$tables = apply_filters( 'tablepress_block_editor_tables_list', $tables );

		$this->add_control( // @phpstan-ignore method.notFound
			'table_id',
			array(
				'label'       => esc_html__( 'Table:', 'tablepress' ),
				'show_label'  => false,
				'label_block' => true,
				'description' => esc_html__( 'Select the TablePress table that you want to embed.', 'tablepress' )
								. ( current_user_can( 'tablepress_list_tables' ) ? sprintf( ' <a href="%1$s" target="_blank">%2$s</a>', esc_url( \TablePress::url( array( 'action' => 'list' ) ) ), esc_html__( 'Manage your tables.', 'tablepress' ) ) : '' ),
				'type'        => \Elementor\Controls_Manager::SELECT2, // @phpstan-ignore classConstant.notFound (Elementor constants are not in the stubs.)
				'ai'          => array( 'active' => false ),
				'options'     => $tables,
			),
		);

		$this->end_controls_section(); // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)

		$this->start_controls_section( // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)
			'advanced',
			array(
				'label'     => esc_html__( 'Advanced', 'tablepress' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT, // @phpstan-ignore classConstant.notFound (Elementor constants are not in the stubs.)
				'condition' => array(
					'id!' => '',
				),
			),
		);

		$this->add_control( // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)
			'parameters',
			array(
				'label'       => esc_html__( 'Configuration parameters:', 'tablepress' ),
				'label_block' => true,
				'description' => esc_html( __( 'These additional parameters can be used to modify specific table features.', 'tablepress' ) . ' ' . __( 'See the TablePress Documentation for more information.', 'tablepress' ) ),
				'type'        => \Elementor\Controls_Manager::TEXT, // @phpstan-ignore classConstant.notFound (Elementor constants are not in the stubs.)
				'input_type'  => 'text',
				'placeholder' => '',
				'ai'          => array( 'active' => false ),
			),
		);

		$this->end_controls_section(); // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)
	}

	/**
	 * Render oEmbed widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 3.1.0
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display(); // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)

		// Don't return anything if no table was selected.
		if ( empty( $settings['table_id'] ) ) {
			/*
			 * In TablePress 3.1 (before 3.1.1), the widget control was named "id" instead of "table_id", which however caused problems.
			 * To ensure that tables will continue to be shown, if the widget was created with 3.1, the "table_id" is set to the "id" value, if only that exists.
			 */
			if ( empty( $settings['id'] ) ) {
				return;
			} else {
				$settings['table_id'] = $settings['id'];
			}
		}

		if ( '' !== trim( $settings['parameters'] ) ) {
			$render_attributes = shortcode_parse_atts( $settings['parameters'] );
		} else {
			$render_attributes = array();
		}
		$render_attributes['id'] = $settings['table_id'];

		/*
		 * It would be nice to print only the Shortcode, for better data portability, e.g. if a site switches away from Elementor.
		 * However, the editor will then only render the Shortcode itself, which is not very helpful.
		 * Due to this, the table HTML code is rendered.
		 * echo '[' . \TablePress::$shortcode . " id={$settings['table_id']} {$settings['parameters']} /]";
		 */

		echo \TablePress::$controller->shortcode_table( $render_attributes );
	}

} // class TablePressTableWidget
