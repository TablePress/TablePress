<?php
/**
 * Editor Button Thickbox List Table
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Editor Button Thickbox List Table Class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Editor_Button_Thickbox_List_Table extends WP_List_Table {

	/**
	 * Number of items of the initial data set (before sort, search, and pagination).
	 *
	 * @since 1.0.0
	 */
	protected int $items_count = 0;

	/**
	 * Initializes the List Table.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// @phpstan-ignore argument.type (WordPress Core's docblocks state wrong argument types in some places.)
		parent::__construct( array(
			'singular' => 'tablepress-table',              // Singular name of the listed records.
			'plural'   => 'tablepress-editor-button-list', // Plural name of the listed records.
			'ajax'     => false,                           // Does this list table support AJAX?
			'screen'   => get_current_screen(),            // WP_Screen object.
		) );
	}

	/**
	 * Sets the data items (here: tables) that are to be displayed by the List Tables, and their original count.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $items Tables to be displayed in the List Table.
	 */
	public function set_items( array $items ): void {
		$this->items = $items;
		$this->items_count = count( $items );
	}

	/**
	 * Checks whether the user has permissions for certain AJAX actions.
	 * (not used, but must be implemented in this child class)
	 *
	 * @since 1.0.0
	 *
	 * @return bool true (Default value).
	 */
	#[\Override]
	public function ajax_user_can(): bool {
		return true;
	}

	/**
	 * Gets a list of columns in this List Table.
	 *
	 * Format: 'internal-name' => 'Column Title'.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> List of columns in this List Table.
	 */
	#[\Override]
	public function get_columns(): array {
		$columns = array(
			// "name" is special in WP, which is why we prefix every entry here, to be safe!
			'table_id'          => __( 'ID', 'tablepress' ),
			'table_name'        => __( 'Table Name', 'tablepress' ),
			'table_description' => __( 'Description', 'tablepress' ),
			'table_action'      => __( 'Action', 'tablepress' ),
		);
		return $columns;
	}

	/**
	 * Gets a list of columns that are sortable.
	 *
	 * Format: 'internal-name' => array( $field for $item[ $field ], true for already sorted ).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{string, bool}> List of sortable columns in this List Table.
	 */
	#[\Override]
	protected function get_sortable_columns(): array {
		// No sorting on the Empty List placeholder.
		if ( ! $this->has_items() ) {
			return array();
		}

		$sortable_columns = array(
			'table_id'          => array( 'id', true ), // true means its already sorted.
			'table_name'        => array( 'name', false ),
			'table_description' => array( 'description', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 1.7.0
	 *
	 * @return string Name of the default primary column, in this case, the table name.
	 */
	#[\Override]
	protected function get_default_primary_column_name(): string {
		return 'table_name';
	}

	/**
	 * Renders a cell in the "table_id" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_id( array $item ): string {
		return esc_html( $item['id'] );
	}

	/**
	 * Renders a cell in the "table_name" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_name( array $item ): string {
		if ( '' === trim( $item['name'] ) ) {
			$item['name'] = __( '(no name)', 'tablepress' );
		}
		return esc_html( $item['name'] );
	}

	/**
	 * Renders a cell in the "table_description" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_description( array $item ): string {
		if ( '' === trim( $item['description'] ) ) {
			$item['description'] = __( '(no description)', 'tablepress' );
		}
		return esc_html( $item['description'] );
	}

	/**
	 * Renders a cell in the "table_action" column, i.e. the "Insert" link.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_action( array $item ): string {
		return '<input type="button" class="insert-shortcode button" title="' . esc_attr( '[' . TablePress::$shortcode . " id={$item['id']} /]" ) . '" value="' . esc_attr__( 'Insert Shortcode', 'tablepress' ) . '">';
	}

	/**
	 * Holds the message to be displayed when there are no items in the table.
	 *
	 * @since 1.0.0
	 */
	#[\Override]
	public function no_items(): void {
		_e( 'No tables found.', 'tablepress' );
		if ( 0 === $this->items_count ) {
			echo ' ' . __( 'You should add or import a table on the TablePress screens to get started!', 'tablepress' );
		}
	}

	/**
	 * Generates the elements above or below the table (like bulk actions and pagination).
	 *
	 * In comparison with parent class, this has modified HTML (no nonce field), and a check whether there are items.
	 *
	 * @since 1.0.0
	 *
	 * @param 'top'|'bottom' $which Location ("top" or "bottom").
	 */
	#[\Override]
	protected function display_tablenav( /* string */ $which ): void {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		if ( ! $this->has_items() ) {
			return;
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
		<?php
			$this->extra_tablenav( $which );

		add_filter( 'ngettext_default', array( $this, 'change_pagination_items_string' ), 10, 5 );
		$this->pagination( $which );
		remove_filter( 'ngettext_default', array( $this, 'change_pagination_items_string' ), 10 );
		?>
			<br class="clear">
		</div>
		<?php
	}

	/**
	 * Replaces the "%s item/%s items" string in the pagination with "%s table/%s tables".
	 *
	 * @since 2.0.0
	 *
	 * @param string $translation The current translation of a singular or plural form.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param int    $number      The number to compare against to use either the singular or plural form.
	 * @param string $domain      Text domain. Defaults to 'default'.
	 * @return string The changed translation.
	 */
	public function change_pagination_items_string( string $translation, string $single, string $plural, int $number, string $domain ): string {
		if ( '%s item' === $single && '%s items' === $plural ) {
			/* translators: %s: Number of tables */
			$translation = _n( '%s table', '%s tables', $number, 'tablepress' );
		}
		return $translation;
	}

	/**
	 * Callback to determine whether the given $item contains the search term.
	 *
	 * @since 1.0.0
	 *
	 * @param string $item Table ID that shall be searched.
	 * @return bool Whether the search term was found or not.
	 */
	protected function _search_callback( string $item ): bool {
		static $term;
		static $json_encoded_term;
		if ( is_null( $term ) || is_null( $json_encoded_term ) ) {
			$term = wp_unslash( $_GET['s'] );
			$json_encoded_term = substr( wp_json_encode( $term, TABLEPRESS_JSON_OPTIONS ), 1, -1 ); // @phpstan-ignore argument.type
		}

		// Load table again, with table data, but without options and visibility settings.
		$item = TablePress::$model_table->load( $item, true, false );

		if ( is_wp_error( $item ) ) {
			return false;
		}

		// Don't search corrupted tables.
		if ( isset( $item['is_corrupted'] ) && $item['is_corrupted'] ) {
			return false;
		}

		$fn_stripos = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';

		// Search from easy to hard, so that "expensive" code maybe doesn't have to run.
		if ( false !== $fn_stripos( $item['id'], (string) $term )
		|| false !== $fn_stripos( $item['name'], (string) $term )
		|| false !== $fn_stripos( $item['description'], (string) $term )
		|| false !== $fn_stripos( TablePress::get_user_display_name( $item['author'] ), (string) $term )
		|| false !== $fn_stripos( TablePress::format_datetime( $item['last_modified'] ), (string) $term )
		|| false !== $fn_stripos( wp_json_encode( $item['data'], TABLEPRESS_JSON_OPTIONS ), (string) $json_encoded_term ) ) { // @phpstan-ignore argument.type
			return true;
		}

		return false;
	}

	/**
	 * Callback to for the array sort function.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $item_a First item that shall be compared to.
	 * @param array<string, mixed> $item_b The second item for the comparison.
	 * @return int (-1, 0, 1) depending on which item sorts "higher".
	 */
	protected function _order_callback( array $item_a, array $item_b ): int {
		global $orderby, $order;

		if ( $item_a[ $orderby ] === $item_b[ $orderby ] ) {
			return 0;
		}

		// Fields in this list table are all strings.
		$result = strnatcasecmp( $item_a[ $orderby ], $item_b[ $orderby ] );

		return ( 'asc' === $order ) ? $result : - $result;
	}

	/**
	 * Prepares the list of items for displaying, by maybe searching and sorting, and by doing pagination.
	 *
	 * @since 1.0.0
	 */
	#[\Override]
	public function prepare_items(): void {
		global $orderby, $order, $s;
		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		// Maybe search in the items.
		if ( $s ) {
			$this->items = array_filter( $this->items, array( $this, '_search_callback' ) );
		}

		// Load actual tables after search for less memory consumption.
		foreach ( $this->items as &$item ) {
			// Don't load data nor table options.
			$item = TablePress::$model_table->load( $item, false, false );
		}
		unset( $item ); // Unset use-by-reference parameter of foreach loop.

		// Maybe sort the items.
		$_sortable_columns = $this->get_sortable_columns();
		if ( $orderby && ! empty( $this->items ) && isset( $_sortable_columns[ "table_{$orderby}" ] ) ) {
			usort( $this->items, array( $this, '_order_callback' ) );
		}

		// Number of records to show per page.
		$per_page = 20; // Hard-coded, as there's no possibility to change this in the Thickbox.
		// Page number the user is currently viewing.
		$current_page = $this->get_pagenum();
		// Number of records in the array.
		$total_items = count( $this->items );

		// Slice items array to hold only items for the current page.
		$this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );

		// Register pagination options and calculation results.
		$this->set_pagination_args( array(
			'total_items' => $total_items, // Total number of records/items.
			'per_page'    => $per_page, // Number of items per page.
			'total_pages' => (int) ceil( $total_items / $per_page ), // Total number of pages.
		) );
	}

} // class TablePress_Editor_Button_Thickbox_List_Table
