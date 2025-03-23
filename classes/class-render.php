<?php
/**
 * TablePress Rendering Class
 *
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Rendering Class
 *
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Render {

	/**
	 * Table data that is rendered.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	protected array $table = array();

	/**
	 * Table options that influence the output result.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	protected array $render_options = array();

	/**
	 * Rendered HTML code of the table or PHP array.
	 *
	 * @since 1.0.0
	 * @var string|array<int, array<int, string>>
	 */
	protected $output;

	/**
	 * Trigger words for colspan, rowspan, or the combination of both.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	protected array $span_trigger = array(
		'colspan' => '#colspan#',
		'rowspan' => '#rowspan#',
		'span'    => '#span#',
	);

	/**
	 * Buffer to store the counts of rowspan per column, initialized in _render_table().
	 *
	 * @since 1.0.0
	 * @var int[]
	 */
	protected array $rowspan = array();

	/**
	 * Buffer to store the counts of colspan per row, initialized in _render_table().
	 *
	 * @since 1.0.0
	 * @var int[]
	 */
	protected array $colspan = array();

	/**
	 * Whether the table has connected cells (colspan or rowspan), set in _render_table().
	 *
	 * @since 3.0.0
	 */
	protected bool $tbody_has_connected_cells = false;

	/**
	 * Index of the last row of the visible data in the table, set in _render_table().
	 *
	 * @since 1.0.0
	 */
	protected int $last_row_idx;

	/**
	 * Index of the last column of the visible data in the table, set in _render_table().
	 *
	 * @since 1.0.0
	 */
	protected int $last_column_idx;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Unused.
	}

	/**
	 * Set the table (data, options, visibility, ...) that is to be rendered.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table          Table to be rendered.
	 * @param array<string, mixed> $render_options Options for rendering, from both "Edit" screen and Shortcode.
	 */
	public function set_input( array $table, array $render_options ): void {
		$this->table = $table;
		$this->render_options = $render_options;
		/**
		 * Filters the table before the render process.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $table          The table.
		 * @param array<string, mixed> $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_raw_render_data', $this->table, $this->render_options );
	}

	/**
	 * Process the table rendering and return the HTML output.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Add the $format parameter.
	 *
	 * @param string $format Optional. Output format, 'html' (default) or 'array'.
	 * @return string|array<int, array<int, string>> HTML code of the rendered table, or a PHP array, or an error message.
	 */
	public function get_output( string $format = 'html' ) /* : string|array */ {
		// Evaluate math expressions/formulas.
		$this->_evaluate_table_data();
		// Remove hidden rows and columns.
		$this->_prepare_render_data();

		if ( 'html' !== $format ) {
			add_filter( 'tablepress_cell_content', 'wptexturize' );
		}

		// Evaluate Shortcodes and escape cell content.
		$this->_process_render_data();

		if ( 'html' !== $format ) {
			remove_filter( 'tablepress_cell_content', 'wptexturize' );
		}

		switch ( $format ) {
			case 'html':
				$this->_render_table();
				break;
			case 'array':
				$this->output = $this->table['data'];
				break;
		}

		return $this->output;
	}

	/**
	 * Loop through the table to evaluate math expressions/formulas.
	 *
	 * @since 1.0.0
	 */
	protected function _evaluate_table_data(): void {
		$orig_table = $this->table;

		if ( $this->render_options['evaluate_formulas'] ) {
			$formula_evaluator = TablePress::load_class( 'TablePress_Evaluate', 'class-evaluate.php', 'classes' );
			$this->table['data'] = $formula_evaluator->evaluate_table_data( $this->table['data'], $this->table['id'] );
		}

		/**
		 * Filters the table after evaluating formulas in the table.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $table          The table with evaluated formulas.
		 * @param array<string, mixed> $orig_table     The table with unevaluated formulas.
		 * @param array<string, mixed> $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_evaluate_data', $this->table, $orig_table, $this->render_options );
	}

	/**
	 * Remove all cells from the data set that shall not be rendered, because they are hidden.
	 *
	 * @since 1.0.0
	 */
	protected function _prepare_render_data(): void {
		$orig_table = $this->table;

		$num_rows = count( $this->table['data'] );
		$num_columns = ( $num_rows > 0 ) ? count( $this->table['data'][0] ) : 0;

		// Evaluate show/hide_rows/columns parameters.
		$actions = array( 'show', 'hide' );
		$elements = array( 'rows', 'columns' );
		foreach ( $actions as $action ) {
			foreach ( $elements as $element ) {
				if ( empty( $this->render_options[ "{$action}_{$element}" ] ) ) {
					$this->render_options[ "{$action}_{$element}" ] = array();
					continue;
				}

				// Add all rows/columns to array if "all" value set for one of the four parameters.
				if ( 'all' === $this->render_options[ "{$action}_{$element}" ] ) {
					$this->render_options[ "{$action}_{$element}" ] = range( 0, ${'num_' . $element} - 1 );
					continue;
				}

				// We have a list of rows/columns (possibly with ranges in it).
				$this->render_options[ "{$action}_{$element}" ] = explode( ',', $this->render_options[ "{$action}_{$element}" ] );
				// Support for ranges like 3-6 or A-BA.
				$range_cells = array();
				foreach ( $this->render_options[ "{$action}_{$element}" ] as $key => $value ) {
					$range_dash = strpos( $value, '-' );
					if ( false !== $range_dash ) {
						unset( $this->render_options[ "{$action}_{$element}" ][ $key ] );
						$start = trim( substr( $value, 0, $range_dash ) );
						if ( ! is_numeric( $start ) ) {
							$start = TablePress::letter_to_number( $start );
						}
						$end = trim( substr( $value, $range_dash + 1 ) );
						if ( ! is_numeric( $end ) ) {
							$end = TablePress::letter_to_number( $end );
						}
						$current_range = range( $start, $end );
						$range_cells = array_merge( $range_cells, $current_range );
					}
				}
				$this->render_options[ "{$action}_{$element}" ] = array_merge( $this->render_options[ "{$action}_{$element}" ], $range_cells );

				/*
				 * Parse single letters and change from regular numbering to zero-based numbering,
				 * as rows/columns are indexed from 0 internally, but from 1 externally.
				 */
				foreach ( $this->render_options[ "{$action}_{$element}" ] as $key => $value ) {
					$value = trim( $value );
					if ( ! is_numeric( $value ) ) {
						$value = TablePress::letter_to_number( $value );
					}
					$this->render_options[ "{$action}_{$element}" ][ $key ] = (int) $value - 1;
				}

				// Remove duplicate entries and sort the array.
				$this->render_options[ "{$action}_{$element}" ] = array_unique( $this->render_options[ "{$action}_{$element}" ] );
				sort( $this->render_options[ "{$action}_{$element}" ], SORT_NUMERIC );
			}
		}

		// Load information about hidden rows and columns.
		// Get indexes of hidden rows (array value of 0).
		$hidden_rows = array_keys( $this->table['visibility']['rows'], 0, true );
		$hidden_rows = array_merge( $hidden_rows, $this->render_options['hide_rows'] );
		$hidden_rows = array_diff( $hidden_rows, $this->render_options['show_rows'] );
		// Get indexes of hidden columns (array value of 0).
		$hidden_columns = array_keys( $this->table['visibility']['columns'], 0, true );
		$hidden_columns = array_merge( $hidden_columns, $this->render_options['hide_columns'] );
		$hidden_columns = array_merge( array_diff( $hidden_columns, $this->render_options['show_columns'] ) );

		// Remove hidden rows and re-index.
		foreach ( $hidden_rows as $row_idx ) {
			unset( $this->table['data'][ $row_idx ] );
		}
		$this->table['data'] = array_merge( $this->table['data'] );
		// Remove hidden columns and re-index.
		foreach ( $this->table['data'] as $row_idx => $row ) {
			foreach ( $hidden_columns as $col_idx ) {
				unset( $row[ $col_idx ] );
			}
			$this->table['data'][ $row_idx ] = array_merge( $row );
		}

		/**
		 * Filters the table after processing the table visibility information.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $table          The processed table.
		 * @param array<string, mixed> $orig_table     The unprocessed table.
		 * @param array<string, mixed> $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_render_data', $this->table, $orig_table, $this->render_options );
	}

	/**
	 * Generate the data that is to be rendered.
	 *
	 * @since 2.0.0
	 */
	protected function _process_render_data(): void {
		$orig_table = $this->table;

		// Deactivate nl2br() for this render process, if "convert_line_breaks" Shortcode parameter is set to false.
		if ( ! $this->render_options['convert_line_breaks'] ) {
			add_filter( 'tablepress_apply_nl2br', '__return_false', 9 ); // Priority 9, so that this filter can easily be overwritten at the default priority.
		}

		foreach ( $this->table['data'] as $row_idx => $row ) {
			foreach ( $row as $col_idx => $cell_content ) {
				// Print formulas that are escaped with '= (like in Excel) as text.
				if ( str_starts_with( $cell_content, "'=" ) ) {
					$cell_content = substr( $cell_content, 1 );
				}
				$cell_content = $this->safe_output( $cell_content );
				if ( str_contains( $cell_content, '[' ) ) {
					$cell_content = do_shortcode( $cell_content );
				}
				/**
				 * Filters the content of a single cell, after formulas have been evaluated, the output has been sanitized, and Shortcodes have been evaluated.
				 *
				 * @since 1.0.0
				 *
				 * @param string $cell_content The cell content.
				 * @param string $table_id     The current table ID.
				 * @param int    $row_idx      The row number of the cell.
				 * @param int    $col_idx      The column number of the cell.
				 */
				$cell_content = apply_filters( 'tablepress_cell_content', $cell_content, $this->table['id'], $row_idx + 1, $col_idx + 1 );
				$this->table['data'][ $row_idx ][ $col_idx ] = $cell_content;
			}
		}

		// Re-instate nl2br() behavior after this render process, if "convert_line_breaks" Shortcode parameter is set to false.
		if ( ! $this->render_options['convert_line_breaks'] ) {
			remove_filter( 'tablepress_apply_nl2br', '__return_false', 9 ); // Priority 9, so that this filter can easily be overwritten at the default priority.
		}

		/**
		 * Filters the table after processing the table content handling.
		 *
		 * @since 2.0.0
		 *
		 * @param array<string, mixed> $table          The processed table.
		 * @param array<string, mixed> $orig_table     The unprocessed table.
		 * @param array<string, mixed> $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_content_render_data', $this->table, $orig_table, $this->render_options );
	}

	/**
	 * Generate the HTML output of the table.
	 *
	 * @since 1.0.0
	 */
	protected function _render_table(): void {
		$num_rows = count( $this->table['data'] );
		$num_columns = ( $num_rows > 0 ) ? count( $this->table['data'][0] ) : 0;

		// Check if there are rows and columns in the table (might not be the case after removing hidden rows/columns!).
		if ( 0 === $num_rows || 0 === $num_columns ) {
			$this->output = sprintf( __( '<!-- The table with the ID %s is empty! -->', 'tablepress' ), $this->table['id'] );
			return;
		}

		// Counters for spans of rows and columns, init to 1 for each row and column (as that means no span).
		$this->rowspan = array_fill( 0, $num_columns, 1 );
		$this->colspan = array_fill( 0, $num_rows, 1 );

		/**
		 * Filters the trigger keywords for "colspan" and "rowspan"
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string>  $span_trigger The trigger keywords for combining table cells.
		 * @param string                 $table_id     The current table ID.
		 */
		$this->span_trigger = apply_filters( 'tablepress_span_trigger_keywords', $this->span_trigger, $this->table['id'] );

		// Explode from string to array.
		$this->render_options['column_widths'] = ( ! empty( $this->render_options['column_widths'] ) ) ? explode( '|', $this->render_options['column_widths'] ) : array();
		// Make array $this->render_options['column_widths'] have $columns entries.
		$this->render_options['column_widths'] = array_pad( $this->render_options['column_widths'], $num_columns, '' );

		$output = '';

		if ( $this->render_options['print_name'] ) {
			/**
			 * Filters the HTML tag that wraps the printed table name.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tag      The HTML tag around the table name. Default h2.
			 * @param string $table_id The current table ID.
			 */
			$name_html_tag = apply_filters( 'tablepress_print_name_html_tag', 'h2', $this->table['id'] );

			$name_attributes = array();
			if ( ! empty( $this->render_options['html_id'] ) ) {
				$name_attributes['id'] = "{$this->render_options['html_id']}-name";
			}
			/**
			 * Filters the class attribute for the printed table name.
			 *
			 * @since 1.0.0
			 * @deprecated 1.13.0 Use {@see 'tablepress_table_name_tag_attributes'} instead.
			 *
			 * @param string $class    The class attribute for the table name that can be used in CSS code.
			 * @param string $table_id The current table ID.
			 */
			$name_attributes['class'] = apply_filters_deprecated( 'tablepress_print_name_css_class', array( "tablepress-table-name tablepress-table-name-id-{$this->table['id']}", $this->table['id'] ), 'TablePress 1.13.0', 'tablepress_table_name_tag_attributes' );
			/**
			 * Filters the attributes for the table name (HTML h2 element, by default).
			 *
			 * @since 1.13.0
			 *
			 * @param array<string, string> $name_attributes The attributes for the table name element.
			 * @param array<string, mixed>  $table           The current table.
			 * @param array<string, mixed>  $render_options  The render options for the table.
			 */
			$name_attributes = apply_filters( 'tablepress_table_name_tag_attributes', $name_attributes, $this->table, $this->render_options );
			$name_attributes = $this->_attributes_array_to_string( $name_attributes );

			$print_name_html = "<{$name_html_tag}{$name_attributes}>" . $this->safe_output( $this->table['name'] ) . "</{$name_html_tag}>\n";
		}
		if ( $this->render_options['print_description'] ) {
			/**
			 * Filters the HTML tag that wraps the printed table description.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tag      The HTML tag around the table description. Default span.
			 * @param string $table_id The current table ID.
			 */
			$description_html_tag = apply_filters( 'tablepress_print_description_html_tag', 'span', $this->table['id'] );

			$description_attributes = array();
			if ( ! empty( $this->render_options['html_id'] ) ) {
				$description_attributes['id'] = "{$this->render_options['html_id']}-description";
			}
			/**
			 * Filters the class attribute for the printed table description.
			 *
			 * @since 1.0.0
			 * @deprecated 1.13.0 Use {@see 'tablepress_table_description_tag_attributes'} instead.
			 *
			 * @param string $class    The class attribute for the table description that can be used in CSS code.
			 * @param string $table_id The current table ID.
			 */
			$description_attributes['class'] = apply_filters_deprecated( 'tablepress_print_description_css_class', array( "tablepress-table-description tablepress-table-description-id-{$this->table['id']}", $this->table['id'] ), 'TablePress 1.13.0', 'tablepress_table_description_tag_attributes' );
			/**
			 * Filters the attributes for the table description (HTML span element, by default).
			 *
			 * @since 1.13.0
			 *
			 * @param array<string, string> $description_attributes The attributes for the table description element.
			 * @param array<string, mixed> $table                   The current table.
			 * @param array<string, mixed> $render_options          The render options for the table.
			 */
			$description_attributes = apply_filters( 'tablepress_table_description_tag_attributes', $description_attributes, $this->table, $this->render_options );
			$description_attributes = $this->_attributes_array_to_string( $description_attributes );

			$print_description_html = "<{$description_html_tag}{$description_attributes}>" . $this->safe_output( $this->table['description'] ) . "</{$description_html_tag}>\n";
		}

		if ( $this->render_options['print_name'] && 'above' === $this->render_options['print_name_position'] ) {
			$output .= $print_name_html;
		}
		if ( $this->render_options['print_description'] && 'above' === $this->render_options['print_description_position'] ) {
			$output .= $print_description_html;
		}

		$thead = array();
		$tfoot = array();
		$tbody = array();

		$this->last_row_idx = $num_rows - 1;
		$this->last_column_idx = $num_columns - 1;

		// Loop through rows in reversed order, to search for rowspan trigger keyword.
		$row_idx = $this->last_row_idx;

		// Render the table footer rows, if there is at least one extra row.
		if ( $this->render_options['table_foot'] > 0 && $num_rows >= $this->render_options['table_head'] + $this->render_options['table_foot'] ) { // @phpstan-ignore greaterOrEqual.invalid (`table_head` and `table_foot` are integers.)
			$last_tbody_idx = $this->last_row_idx - $this->render_options['table_foot'];
			while ( $row_idx > $last_tbody_idx ) {
				$tfoot[] = $this->_render_row( $row_idx, 'th' );
				--$row_idx;
			}
			// Reverse rows because we looped through the rows in reverse order.
			$tfoot = array_reverse( $tfoot );
		}

		// Render the table body rows.
		$last_thead_idx = $this->render_options['table_head'] - 1;
		while ( $row_idx > $last_thead_idx ) {
			$tbody[] = $this->_render_row( $row_idx, 'td' );
			--$row_idx;
		}
		// Reverse rows because we looped through the rows in reverse order.
		$tbody = array_reverse( $tbody );

		// Render the table header rows, if rows are left.
		while ( $row_idx > -1 ) {
			$thead[] = $this->_render_row( $row_idx, 'th' );
			--$row_idx;
		}
		// Reverse rows because we looped through the rows in reverse order.
		$thead = array_reverse( $thead );

		// <caption> tag.
		/**
		 * Filters the content for the HTML caption element of the table.
		 *
		 * If the "Edit" link for a table is shown, it is also added to the caption element.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $caption The content for the HTML caption element of the table. Default empty.
		 * @param array<string, mixed> $table   The current table.
		 */
		$caption = apply_filters( 'tablepress_print_caption_text', '', $this->table );
		$caption_style = '';
		$caption_class = '';
		if ( ! empty( $caption ) ) {
			/**
			 * Filters the class attribute for the HTML caption element of the table.
			 *
			 * @since 1.0.0
			 *
			 * @param string $class    The class attribute for the HTML caption element of the table.
			 * @param string $table_id The current table ID.
			 */
			$caption_class = apply_filters( 'tablepress_print_caption_class', "tablepress-table-caption tablepress-table-caption-id-{$this->table['id']}", $this->table['id'] );
			$caption_class = ' class="' . $caption_class . '"';
		}
		if ( ! empty( $this->render_options['edit_table_url'] ) ) {
			if ( empty( $caption ) ) {
				$caption_style = ' style="caption-side:bottom;text-align:left;border:none;background:none;margin:0;padding:0;"';
			} else {
				$caption .= '<br />';
			}
			$caption .= '<a href="' . esc_url( $this->render_options['edit_table_url'] ) . '" rel="nofollow">' . __( 'Edit', 'default' ) . '</a>';
		}
		if ( ! empty( $caption ) ) {
			$caption = "<caption{$caption_class}{$caption_style}>{$caption}</caption>\n";
		}

		// <colgroup> tag.
		$colgroup = '';
		/**
		 * Filters whether the HTML colgroup tag shall be added to the table output.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $print    Whether the colgroup element shall be printed.
		 * @param string $table_id The current table ID.
		 */
		if ( apply_filters( 'tablepress_print_colgroup_tag', false, $this->table['id'] ) ) {
			for ( $col_idx = 0; $col_idx < $num_columns; $col_idx++ ) {
				$attributes = ' class="colgroup-column-' . ( $col_idx + 1 ) . ' "';
				/**
				 * Filters the attributes of the HTML col tags in the HTML colgroup tag.
				 *
				 * @since 1.0.0
				 *
				 * @param string $attributes The attributes in the col element.
				 * @param string $table_id   The current table ID.
				 * @param int    $col_idx    The number of the column.
				 */
				$attributes = apply_filters( 'tablepress_colgroup_tag_attributes', $attributes, $this->table['id'], $col_idx + 1 );
				$colgroup .= "\t<col{$attributes} />\n";
			}
		}
		if ( ! empty( $colgroup ) ) {
			$colgroup = "<colgroup>\n{$colgroup}</colgroup>\n";
		}

		/*
		 * <thead>, <tfoot>, and <tbody> tags.
		 */

		if ( ! empty( $thead ) ) {
			$thead = "<thead>\n" . implode( '', $thead ) . "</thead>\n";
		} else {
			$thead = '';
		}

		if ( ! empty( $tfoot ) ) {
			$tfoot = "<tfoot>\n" . implode( '', $tfoot ) . "</tfoot>\n";
		} else {
			$tfoot = '';
		}

		$tbody_classes = array();
		if ( $this->render_options['alternating_row_colors'] ) {
			$tbody_classes[] = 'row-striping';
		}
		if ( $this->render_options['row_hover'] ) {
			$tbody_classes[] = 'row-hover';
		}
		$tbody_class = implode( ' ', $tbody_classes );
		if ( '' !== $tbody_class ) {
			$tbody_class = ' class="' . esc_attr( $tbody_class ) . '"';
		}

		$tbody = "<tbody{$tbody_class}>\n" . implode( '', $tbody ) . "</tbody>\n";

		// Attributes for the table (HTML table element).
		$table_attributes = array();

		// "id" attribute.
		if ( ! empty( $this->render_options['html_id'] ) ) {
			$table_attributes['id'] = $this->render_options['html_id'];
		}

		// "class" attribute.
		$css_classes = array(
			'tablepress',
			"tablepress-id-{$this->table['id']}",
			$this->render_options['extra_css_classes'],
		);
		if ( $this->tbody_has_connected_cells ) {
			$css_classes[] = 'tbody-has-connected-cells';
		}
		/**
		 * Filters the CSS classes that are given to the HTML table element.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $css_classes The CSS classes for the table element.
		 * @param string   $table_id    The current table ID.
		 */
		$css_classes = apply_filters( 'tablepress_table_css_classes', $css_classes, $this->table['id'] );
		// $css_classes might contain several classes in one array entry.
		$css_classes = explode( ' ', implode( ' ', $css_classes ) );
		$css_classes = array_map( array( 'TablePress', 'sanitize_css_class' ), $css_classes );
		$css_classes = array_unique( $css_classes );
		$css_classes = array_filter( $css_classes ); // Remove empty entries.
		$css_classes = implode( ' ', $css_classes );
		if ( '' !== $css_classes ) {
			$table_attributes['class'] = $css_classes;
		}

		// ARIA label attributes.
		if ( $this->render_options['print_name'] && ! empty( $this->render_options['html_id'] ) ) {
			$table_attributes['aria-labelledby'] = "{$this->render_options['html_id']}-name";
		}
		if ( $this->render_options['print_description'] && ! empty( $this->render_options['html_id'] ) ) {
			$table_attributes['aria-describedby'] = "{$this->render_options['html_id']}-description";
		}

		// "summary" attribute.
		$summary = '';
		/**
		 * Filters the content for the summary attribute of the HTML table element.
		 *
		 * The attribute is only added if it is not empty.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $summary The content for the summary attribute of the table. Default empty.
		 * @param array<string, mixed> $table   The current table.
		 */
		$summary = apply_filters( 'tablepress_print_summary_attr', $summary, $this->table );
		if ( ! empty( $summary ) ) {
			$table_attributes['summary'] = esc_attr( $summary );
		}

		// Legacy support for attributes that are not encouraged in HTML5.
		foreach ( array( 'cellspacing', 'cellpadding', 'border' ) as $attribute ) {
			if ( false !== $this->render_options[ $attribute ] ) {
				$table_attributes[ $attribute ] = (int) $this->render_options[ $attribute ];
			}
		}

		/**
		 * Filters the attributes for the table (HTML table element).
		 *
		 * @since 1.4.0
		 *
		 * @param array<string, string> $table_attributes The attributes for the table element.
		 * @param array<string, mixed>  $table            The current table.
		 * @param array<string, mixed>  $render_options   The render options for the table.
		 */
		$table_attributes = apply_filters( 'tablepress_table_tag_attributes', $table_attributes, $this->table, $this->render_options );
		$table_attributes = $this->_attributes_array_to_string( $table_attributes );

		$table_html = "<table{$table_attributes}>\n";
		$table_html .= $caption . $colgroup . $thead . $tbody . $tfoot;
		$table_html .= '</table>';

		/**
		 * Filters the generated HTML code for the table, without HTML elements around it.
		 *
		 * @since 2.4.0
		 *
		 * @param string               $output         The generated HTML for the table, without HTML elements around it.
		 * @param array<string, mixed> $table          The current table.
		 * @param array<string, mixed> $render_options The render options for the table, without HTML elements around it.
		 */
		$table_html = apply_filters( 'tablepress_table_html', $table_html, $this->table, $this->render_options );

		$output .= "\n{$table_html}\n";
		unset( $table_html ); // Unset the potentially large variable to free up memory.

		// name/description below table (HTML already generated above).
		if ( $this->render_options['print_name'] && 'below' === $this->render_options['print_name_position'] ) {
			$output .= $print_name_html; // @phpstan-ignore variable.undefined (The variable is set above.)
		}
		if ( $this->render_options['print_description'] && 'below' === $this->render_options['print_description_position'] ) {
			$output .= $print_description_html; // @phpstan-ignore variable.undefined (The variable is set above.)
		}

		/**
		 * Filters the generated HTML code for the table and HTML elements around it.
		 *
		 * @since 1.0.0
		 *
		 * @param string               $output         The generated HTML for the table and HTML elements around it.
		 * @param array<string, mixed> $table          The current table.
		 * @param array<string, mixed> $render_options The render options for the table and HTML elements around it.
		 */
		$this->output = apply_filters( 'tablepress_table_output', $output, $this->table, $this->render_options );
	}

	/**
	 * Generate the HTML of a row.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $row_idx Index of the row to be rendered.
	 * @param string $tag     HTML tag to use for the cells (td or th).
	 * @return string HTML for the row.
	 */
	protected function _render_row( int $row_idx, string $tag ): string {
		$row_cells = array();
		// Loop through cells in reversed order, to search for colspan or rowspan trigger words.
		for ( $col_idx = $this->last_column_idx; $col_idx >= 0; $col_idx-- ) {
			$cell_content = $this->table['data'][ $row_idx ][ $col_idx ];

			if ( $this->span_trigger['rowspan'] === $cell_content ) { // There will be a rowspan.
				if ( ! (
					( 0 === $row_idx ) // No rowspan inside first row.
					|| ( $this->render_options['table_head'] === $row_idx ) // No rowspan into table head.
					|| ( $this->last_row_idx - $this->render_options['table_foot'] + 1 === $row_idx ) // No rowspan out of table foot.
				) ) {
					// Increase counter for rowspan in this column.
					++$this->rowspan[ $col_idx ];
					// Reset counter for colspan in this row, combined col- and rowspan might be happening.
					$this->colspan[ $row_idx ] = 1;
					continue;
				}
				// Invalid rowspan, so we set cell content from #rowspan# to empty.
				$cell_content = '';
			} elseif ( $this->span_trigger['colspan'] === $cell_content ) { // There will be a colspan.
				if ( ! (
					( 0 === $col_idx ) // No colspan inside first column.
					|| ( 1 === $col_idx && $this->render_options['first_column_th'] ) // No colspan into first column head.
				) ) {
					// Increase counter for colspan in this row.
					++$this->colspan[ $row_idx ];
					// Reset counter for rowspan in this column, combined col- and rowspan might be happening.
					$this->rowspan[ $col_idx ] = 1;
					continue;
				}
				// Invalid colspan, so we set cell content from #colspan# to empty.
				$cell_content = '';
			} elseif ( $this->span_trigger['span'] === $cell_content ) { // There will be a combined col- and rowspan.
				if ( ! (
					( 0 === $row_idx ) // No rowspan inside first row.
					|| ( $this->render_options['table_head'] === $row_idx ) // No rowspan into table head.
					|| ( $this->last_row_idx - $this->render_options['table_foot'] + 1 === $row_idx ) // No rowspan out of table foot.
				) && ! (
					( 0 === $col_idx ) // No colspan inside first column.
					|| ( 1 === $col_idx && $this->render_options['first_column_th'] ) // No colspan into first column head.
				) ) {
					continue;
				}
				// Invalid span, so we set cell content from #span# to empty.
				$cell_content = '';
			}

			// Attributes for the table cell (HTML td or th element).
			$tag_attributes = array();

			// "colspan" and "rowspan" attributes.
			if ( $this->colspan[ $row_idx ] > 1 ) { // We have colspaned cells.
				$tag_attributes['colspan'] = (string) $this->colspan[ $row_idx ];
				if ( ! $this->tbody_has_connected_cells && $row_idx > $this->render_options['table_head'] - 1 && $row_idx < $this->last_row_idx - $this->render_options['table_foot'] + 1 ) {
					// Set flag that there are connected cells in the tbody.
					$this->tbody_has_connected_cells = true;
				}
			}
			if ( $this->rowspan[ $col_idx ] > 1 ) { // We have rowspaned cells.
				$tag_attributes['rowspan'] = (string) $this->rowspan[ $col_idx ];
				if ( ! $this->tbody_has_connected_cells && $row_idx > $this->render_options['table_head'] - 1 && $row_idx < $this->last_row_idx - $this->render_options['table_foot'] + 1 ) {
					// Set flag that there are connected cells in the tbody.
					$this->tbody_has_connected_cells = true;
				}
			}

			// "class" attribute.
			$cell_class = 'column-' . ( $col_idx + 1 );
			/**
			 * Filters the CSS classes that are given to a single cell (HTML td element) of a table.
			 *
			 * @since 1.0.0
			 *
			 * @param string $cell_class   The CSS classes for the cell.
			 * @param string $table_id     The current table ID.
			 * @param string $cell_content The cell content.
			 * @param int    $row_idx      The row number of the cell.
			 * @param int    $col_idx      The column number of the cell.
			 * @param int    $colspan_row  The number of combined columns for this cell.
			 * @param int    $rowspan_col  The number of combined rows for this cell.
			 */
			$cell_class = apply_filters( 'tablepress_cell_css_class', $cell_class, $this->table['id'], $cell_content, $row_idx + 1, $col_idx + 1, $this->colspan[ $row_idx ], $this->rowspan[ $col_idx ] );
			if ( ! empty( $cell_class ) ) {
				$tag_attributes['class'] = $cell_class;
			}

			// "style" attribute.
			if ( ( 0 === $row_idx ) && ! empty( $this->render_options['column_widths'][ $col_idx ] ) ) {
				$tag_attributes['style'] = 'width:' . preg_replace( '#[^0-9a-z.%]#', '', $this->render_options['column_widths'][ $col_idx ] ) . ';';
			}

			/**
			 * Filters the attributes for the table cell (HTML td or th element).
			 *
			 * @since 1.4.0
			 *
			 * @param array<string, string> $tag_attributes The attributes for the td or th element.
			 * @param string                $table_id       The current table ID.
			 * @param string                $cell_content   The cell content.
			 * @param int                   $row_idx        The row number of the cell.
			 * @param int                   $col_idx        The column number of the cell.
			 * @param int                   $colspan_row    The number of combined columns for this cell.
			 * @param int                   $rowspan_col    The number of combined rows for this cell.
			 */
			$tag_attributes = apply_filters( 'tablepress_cell_tag_attributes', $tag_attributes, $this->table['id'], $cell_content, $row_idx + 1, $col_idx + 1, $this->colspan[ $row_idx ], $this->rowspan[ $col_idx ] );
			$tag_attributes = $this->_attributes_array_to_string( $tag_attributes );

			if ( '' === $cell_content ) {
				$cell_tag = 'td'; // For accessibility, empty cells should use `td` and not `th` tags.
			} elseif ( $this->render_options['first_column_th'] && 0 === $col_idx ) {
				$cell_tag = 'th'; // Non-empty cells in the first column should use `th` tags, if enabled.
			} else {
				$cell_tag = $tag; // Otherwise, use the tag that was passed in as the default for the row.
			}

			$row_cells[] = "<{$cell_tag}{$tag_attributes}>{$cell_content}</{$cell_tag}>";
			$this->colspan[ $row_idx ] = 1; // Reset.
			$this->rowspan[ $col_idx ] = 1; // Reset.
		}

		// Attributes for the table row (HTML tr element).
		$tr_attributes = array();

		// "class" attribute.
		$row_classes = 'row-' . ( $row_idx + 1 );
		/**
		 * Filters the CSS classes that are given to a row (HTML tr element) of a table.
		 *
		 * @since 1.0.0
		 *
		 * @param string   $row_classes The CSS classes for the row.
		 * @param string   $table_id    The current table ID.
		 * @param string[] $row_cells   The HTML code for the cells of the row.
		 * @param int      $row_idx     The row number.
		 * @param string[] $row_data    The content of the cells of the row.
		 */
		$row_classes = apply_filters( 'tablepress_row_css_class', $row_classes, $this->table['id'], $row_cells, $row_idx + 1, $this->table['data'][ $row_idx ] );
		if ( ! empty( $row_classes ) ) {
			$tr_attributes['class'] = $row_classes;
		}

		/**
		 * Filters the attributes for the table row (HTML tr element).
		 *
		 * @since 1.4.0
		 *
		 * @param array<string, mixed> $tr_attributes The attributes for the tr element.
		 * @param string               $table_id      The current table ID.
		 * @param int                  $row_idx       The row number.
		 * @param string[]             $row_data      The content of the cells of the row.
		 */
		$tr_attributes = apply_filters( 'tablepress_row_tag_attributes', $tr_attributes, $this->table['id'], $row_idx + 1, $this->table['data'][ $row_idx ] );
		$tr_attributes = $this->_attributes_array_to_string( $tr_attributes );

		// Reverse rows because we looped through the cells in reverse order.
		$row_cells = array_reverse( $row_cells );
		return "<tr{$tr_attributes}>\n\t" . implode( '', $row_cells ) . "\n</tr>\n";
	}

	/**
	 * Convert an array of HTML tag attributes to a string.
	 *
	 * @since 1.4.0
	 *
	 * @param array<string, string> $attributes Attributes for the HTML tag in the array keys, and their values in the array values.
	 * @return string The attributes as a string for usage in a HTML element.
	 */
	protected function _attributes_array_to_string( array $attributes ): string {
		$attributes_string = '';
		foreach ( $attributes as $attribute => $value ) {
			$attributes_string .= " {$attribute}=\"{$value}\"";
		}
		return $attributes_string;
	}

	/**
	 * Possibly replace certain HTML entities and replace line breaks with HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text The string to process.
	 * @return string Processed string for output.
	 */
	protected function safe_output( string $text ): string {
		/*
		 * Replace any & with &amp; that is not already an encoded entity (from function htmlentities2 in WP 2.8).
		 * A complete htmlentities2() or htmlspecialchars() would encode <HTML> tags, which we don't want.
		 */
		$text = (string) preg_replace( '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/', '&amp;', $text );
		/**
		 * Filters whether line breaks in the cell content shall be replaced with HTML br tags.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $replace  Whether to replace line breaks with HTML br tags. Default true.
		 * @param string $table_id The current table ID.
		 */
		if ( apply_filters( 'tablepress_apply_nl2br', true, $this->table['id'] ) ) {
			$text = nl2br( $text );
		}
		return $text;
	}

	/**
	 * Get the default render options, null means: Use option from "Edit" screen.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Default render options.
	 */
	public function get_default_render_options(): array {
		// Attention: Array keys have to be lowercase, otherwise they won't match the Shortcode attributes, which will be passed in lowercase by WP.
		return array(
			'alternating_row_colors'      => null,
			'block_preview'               => false,
			'border'                      => false,
			'cache_table_output'          => true,
			'cellpadding'                 => false,
			'cellspacing'                 => false,
			'column_widths'               => '',
			'convert_line_breaks'         => true,
			'datatables_custom_commands'  => null,
			'datatables_datetime'         => '',
			'datatables_filter'           => null,
			'datatables_info'             => null,
			'datatables_lengthchange'     => null,
			'datatables_locale'           => get_locale(),
			'datatables_paginate'         => null,
			'datatables_paginate_entries' => null,
			'datatables_scrollx'          => null,
			'datatables_scrolly'          => false,
			'datatables_sort'             => null,
			'evaluate_formulas'           => true,
			'extra_css_classes'           => null,
			'first_column_th'             => false,
			'hide_columns'                => '',
			'hide_rows'                   => '',
			'id'                          => '',
			'print_description'           => null,
			'print_description_position'  => null,
			'print_name'                  => null,
			'print_name_position'         => null,
			'row_hover'                   => null,
			'shortcode_debug'             => false,
			'show_columns'                => '',
			'show_rows'                   => '',
			'table_foot'                  => null,
			'table_head'                  => null,
			'use_datatables'              => null,
		);
	}

	/**
	 * Get the CSS code for the Preview iframe.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS for the Preview iframe.
	 */
	public function get_preview_css(): string {
		$is_rtl = is_rtl();
		$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );
		$default_css_minified = $tablepress_css->load_default_css_from_file( $is_rtl );
		if ( false === $default_css_minified ) {
			$default_css_minified = '';
		}

		$rtl_direction = $is_rtl ? "\ndirection: rtl;" : '';

		return <<<CSS
			<style>
			/* iframe */
			body {
				margin: 10px;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;{$rtl_direction}
			}
			p {
				font-size: 13px;
			}
			{$default_css_minified}
			</style>
			CSS;
	}

} // class TablePress_Render
