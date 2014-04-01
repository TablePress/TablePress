<?php
/**
 * TablePress Rendering Class
 *
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Rendering Class
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Render {

	/**
	 * Table data that is rendered
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $table;

	/**
	 * Table options that influence the output result
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $render_options = array();

	/**
	 * Rendered HTML of the table
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * Instance of EvalMath class
	 *
	 * @since 1.0.0
	 *
	 * @var EvalMath
	 */
	protected $evalmath;

	/**
	 * Trigger words for colspan, rowspan, or the combination of both
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $span_trigger = array(
		'colspan' => '#colspan#',
		'rowspan' => '#rowspan#',
		'span' => '#span#',
	);

	/**
	 * Buffer to store the counts of rowspan per column, initialized in _render_table()
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $rowspan = array();

	/**
	 * Buffer to store the counts of colspan per row, initialized in _render_table()
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $colspan = array();

	/**
	 * Index of the last row of the visible data in the table, set in _render_table()
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $last_row_idx;

	/**
	 * Index of the last column of the visible data in the table, set in _render_table()
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $last_column_idx;

	/**
	 * Initialize the Rendering class, include the EvalMath class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->evalmath = TablePress::load_class( 'EvalMath', 'evalmath.class.php', 'libraries', true ); // true for some default constants
		$this->evalmath->suppress_errors = true; // don't raise PHP warnings
	}

	/**
	 * Set the table (data, options, visibility, ...) that is to be rendered
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table to be rendered
	 * @param array $render_options Options for rendering, from both "Edit" screen and Shortcode
	 */
	public function set_input( array $table, array $render_options ) {
		$this->table = $table;
		$this->render_options = $render_options;
		/**
		 * Filter the table before the render process.
		 *
		 * @since 1.0.0
		 *
		 * @param array $table          The table.
		 * @param array $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_raw_render_data', $this->table, $this->render_options );
	}

	/**
	 * Process the table rendering and return the HTML output
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML of the rendered table (or an error message)
	 */
	public function get_output() {
		// evaluate math expressions/formulas
		$this->_evaluate_table_data();
		// remove hidden rows and columns
		$this->_prepare_render_data();
		// generate HTML output
		$this->_render_table();
		return $this->output;
	}

	/**
	 * Remove all cells from the data set that shall not be rendered, because they are hidden
	 *
	 * @since 1.0.0
	 */
	protected function _prepare_render_data() {
		$orig_table = $this->table;

		$num_rows = count( $this->table['data'] );
		$num_columns = ( $num_rows > 0 ) ? count( $this->table['data'][0] ) : 0;

		// evaluate show/hide_rows/columns parameters
		$actions = array( 'show', 'hide' );
		$elements = array( 'rows', 'columns' );
		foreach ( $actions as $action ) {
			foreach ( $elements as $element ) {
				if ( empty( $this->render_options["{$action}_{$element}"] ) ) {
					$this->render_options["{$action}_{$element}"] = array();
					continue;
				}

				// add all rows/columns to array if "all" value set for one of the four parameters
				if ( 'all' == $this->render_options["{$action}_{$element}"] ) {
					$this->render_options["{$action}_{$element}"] = range( 0, ${'num_' . $element} - 1 );
					continue;
				}

				// we have a list of rows/columns (possibly with ranges in it)
				$this->render_options["{$action}_{$element}"] = explode( ',', $this->render_options["{$action}_{$element}"] );
				// support for ranges like 3-6 or A-BA
				$range_cells = array();
				foreach ( $this->render_options["{$action}_{$element}"] as $key => $value ) {
					$range_dash = strpos( $value, '-' );
					if ( false !== $range_dash ) {
						unset( $this->render_options["{$action}_{$element}"][ $key ] );
						$start = substr( $value, 0, $range_dash );
						if ( ! is_numeric( $start ) ) {
							$start = TablePress::letter_to_number( $start );
						}
						$end = substr( $value, $range_dash + 1 );
						if ( ! is_numeric( $end ) ) {
							$end = TablePress::letter_to_number( $end );
						}
						$current_range = range( $start, $end );
						$range_cells = array_merge( $range_cells, $current_range );
					}
				}
				$this->render_options["{$action}_{$element}"] = array_merge( $this->render_options["{$action}_{$element}"], $range_cells );
				// parse single letters and
				// change from regular numbering to zero-based numbering,
				// as rows/columns are indexed from 0 internally, but from 1 externally
				foreach ( $this->render_options["{$action}_{$element}"] as $key => $value ) {
					if ( ! is_numeric( $value ) ) {
						$value = TablePress::letter_to_number( $value );
					}
					$this->render_options["{$action}_{$element}"][ $key ] = (int) $value - 1;
				}

				// remove duplicate entries and sort the array
				$this->render_options["{$action}_{$element}"] = array_unique( $this->render_options["{$action}_{$element}"] );
				sort( $this->render_options["{$action}_{$element}"], SORT_NUMERIC );
			}
		}

		// load information about hidden rows and columns
		$hidden_rows = array_keys( $this->table['visibility']['rows'], 0 ); // get indexes of hidden rows (array value of 0)
		$hidden_rows = array_merge( $hidden_rows, $this->render_options['hide_rows'] );
		$hidden_rows = array_diff( $hidden_rows, $this->render_options['show_rows'] );
		$hidden_columns = array_keys( $this->table['visibility']['columns'], 0 ); // get indexes of hidden columns (array value of 0)
		$hidden_columns = array_merge( $hidden_columns, $this->render_options['hide_columns'] );
		$hidden_columns = array_merge( array_diff( $hidden_columns, $this->render_options['show_columns'] ) );

		// remove hidden rows and re-index
		foreach ( $hidden_rows as $row_idx ) {
			unset( $this->table['data'][ $row_idx ] );
		}
		$this->table['data'] = array_merge( $this->table['data'] );
		// remove hidden columns and re-index
		foreach ( $this->table['data'] as $row_idx => $row ) {
			foreach ( $hidden_columns as $col_idx ) {
				unset( $row[ $col_idx ] );
			}
			$this->table['data'][ $row_idx ] = array_merge( $row );
		}

		/**
		 * Filter the table after processing the table visibility information.
		 *
		 * @since 1.0.0
		 *
		 * @param array $table          The processed table.
		 * @param array $orig_table     The unprocessed table.
		 * @param array $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_render_data', $this->table, $orig_table, $this->render_options );
	}

	/**
	 * Loop through the table to evaluate math expressions/formulas
	 *
	 * @since 1.0.0
	 */
	protected function _evaluate_table_data() {
		$orig_table = $this->table;

		$rows = count( $this->table['data'] );
		$columns = count( $this->table['data'][0] );
		for ( $row_idx = 0; $row_idx < $rows; $row_idx++ ) {
			for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
				$this->table['data'][ $row_idx ][ $col_idx ] = $this->_evaluate_cell( $this->table['data'][ $row_idx ][ $col_idx ] );
			}
		}

		/**
		 * Filter the table after evaluating formulas in the table.
		 *
		 * @since 1.0.0
		 *
		 * @param array $table          The table with evaluated formulas.
		 * @param array $orig_table     The table with unevaluated formulas.
		 * @param array $render_options The render options for the table.
		 */
		$this->table = apply_filters( 'tablepress_table_evaluate_data', $this->table, $orig_table, $this->render_options );
	}

	/**
	 * Parse and evaluate the content of a cell
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Content of a cell
	 * @param array $parents List of cells that depend on this cell (to prevent circle references)
	 * @return string Result of the parsing/evaluation
	 */
	protected function _evaluate_cell( $content, array $parents = array() ) {
		if ( '' == $content || '=' == $content || '=' != $content[0] ) {
			return $content;
		}

		$content = substr( $content, 1 );

		// Support putting formulas in strings, like =Total: {A3+A4}
		$expressions = array();
		if ( preg_match_all( '#{(.+?)}#', $content, $expressions, PREG_SET_ORDER ) ) {
			$formula_in_string = true;
		} else {
			$formula_in_string = false;
			$expressions[] = array( $content, $content ); // fill array so that it has the same structure as if it came from preg_match_all()
		}

		foreach ( $expressions as $expression ) {
			$orig_expression = $expression[0];
			$expression = $expression[1];

			$replaced_references = $replaced_ranges = array();

			// remove all whitespace characters
			$expression = preg_replace( '#[\r\n\t ]#', '', $expression );

			// expand cell ranges (like A3:A6) to a list of single cells (like A3,A4,A5,A6)
			if ( preg_match_all( '#([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)#', $expression, $referenced_cell_ranges, PREG_SET_ORDER ) ) {
				foreach ( $referenced_cell_ranges as $cell_range ) {
					if ( in_array( $cell_range[0], $replaced_ranges, true ) ) {
						continue;
					}

					$replaced_ranges[] = $cell_range[0];

					if ( isset( $this->known_ranges[ $cell_range[0] ] ) ) {
						$expression = preg_replace( '#(?<![A-Z])' . preg_quote( $cell_range[0], '#' ) . '(?![0-9])#', $this->known_ranges[ $cell_range[0] ], $expression );
						continue;
					}

					// no -1 necessary for this transformation, as we don't actually access the table
					$first_col = TablePress::letter_to_number( $cell_range[1] );
					$first_row = $cell_range[2];
					$last_col = TablePress::letter_to_number( $cell_range[3] );
					$last_row = $cell_range[4];

					$col_start = min( $first_col, $last_col );
					$col_end = max( $first_col, $last_col ) + 1; // +1 for loop below
					$row_start = min( $first_row, $last_row );
					$row_end = max( $first_row, $last_row ) + 1; // +1 for loop below

					$cell_list = array();
					for ( $col = $col_start; $col < $col_end; $col++ ) {
						for ( $row = $row_start; $row < $row_end; $row++ ) {
							$column = TablePress::number_to_letter( $col );
							$cell_list[] = "{$column}{$row}";
						}
					}
					$cell_list = implode( ',', $cell_list );

					$expression = preg_replace( '#(?<![A-Z])' . preg_quote( $cell_range[0], '#' ) . '(?![0-9])#', $cell_list, $expression );
					$this->known_ranges[ $cell_range[0] ] = $cell_list;
				}
			}

			// parse and evaluate single cell references (like A3 or XY312), while prohibiting circle references
			if ( preg_match_all( '#([A-Z]+)([0-9]+)#', $expression, $referenced_cells, PREG_SET_ORDER ) ) {
				foreach ( $referenced_cells as $cell_reference ) {
					if ( in_array( $cell_reference[0], $parents, true ) ) {
						return '!ERROR! Circle Reference';
					}

					if ( in_array( $cell_reference[0], $replaced_references, true ) ) {
						continue;
					}

					$replaced_references[] = $cell_reference[0];

					$ref_col = TablePress::letter_to_number( $cell_reference[1] ) - 1;
					$ref_row = $cell_reference[2] - 1;

					if ( ! isset( $this->table['data'][ $ref_row ] ) || ! isset( $this->table['data'][ $ref_row ][ $ref_col ] ) ) {
						return "!ERROR! Cell {$cell_reference[0]} does not exist";
					}

					$ref_parents = $parents;
					$ref_parents[] = $cell_reference[0];

					$result = $this->table['data'][ $ref_row ][ $ref_col ] = $this->_evaluate_cell( $this->table['data'][ $ref_row ][ $ref_col ], $ref_parents );
					// Bail if there was an error already
					if ( false !== strpos( $result, '!ERROR!' ) ) {
						return $result;
					}
					// remove all whitespace characters
					$result = preg_replace( '#[\r\n\t ]#', '', $result );
					// Treat empty cells as 0
					if ( '' == $result ) {
						$result = 0;
					}
					// Bail if the cell does not result in a number (meaning it was a number or expression before being evaluated)
					if ( ! is_numeric( $result ) ) {
						return "!ERROR! {$cell_reference[0]} does not contain a number or expression";
					}

					$expression = preg_replace( '#(?<![A-Z])' . $cell_reference[0] . '(?![0-9])#', $result, $expression );
				}
			}

			$result = $this->_evaluate_math_expression( $expression );
			// Support putting formulas in strings, like =Total: {A3+A4}
			if ( $formula_in_string ) {
				$content = str_replace( $orig_expression, $result, $content );
			} else {
				$content = $result;
			}
		}

		return $content;
	}

	/**
	 * Evaluate a math expression
	 *
	 * @since 1.0.0
	 *
	 * @param string $expression without leading = sign
	 * @return string Result of the evaluation
	 */
	protected function _evaluate_math_expression( $expression ) {
		// straight up evaluation, without parsing of variable or function assignments (which is why we only need one instance of the object)
		$result = $this->evalmath->pfx( $this->evalmath->nfx( $expression ) );
		if ( false === $result ) {
			return '!ERROR! ' . $this->evalmath->last_error;
		} else {
			return $result;
		}
	}

	/**
	 * Generate the HTML output of the table
	 *
	 * @since 1.0.0
	 */
	protected function _render_table() {
		$num_rows = count( $this->table['data'] );
		$num_columns = ( $num_rows > 0 ) ? count( $this->table['data'][0] ) : 0;

		// check if there are rows and columns in the table (might not be the case after removing hidden rows/columns!)
		if ( 0 === $num_rows || 0 === $num_columns ) {
			$this->output = sprintf( __( '<!-- The table with the ID %s is empty! -->', 'tablepress' ), $this->table['id'] );
			return;
		}

		// counters for spans of rows and columns, init to 1 for each row and column (as that means no span)
		$this->rowspan = array_fill( 0, $num_columns, 1 );
		$this->colspan = array_fill( 0, $num_rows, 1 );

		/**
		 * Filter the trigger keywords for "colspan" and "rowspan"
		 *
		 * @since 1.0.0
		 *
		 * @param array  $span_trigger The trigger keywords for combining table cells.
		 * @param string $table_id     The current table ID.
		 */
		$this->span_trigger = apply_filters( 'tablepress_span_trigger_keywords', $this->span_trigger, $this->table['id'] );

		// explode from string to array
		$this->render_options['column_widths'] = ( ! empty( $this->render_options['column_widths'] ) ) ? explode( '|', $this->render_options['column_widths'] ) : array();
		// make array $this->render_options['column_widths'] have $columns entries
		$this->render_options['column_widths'] = array_pad( $this->render_options['column_widths'], $num_columns, '' );

		$output = '';

		if ( $this->render_options['print_name'] ) {
			/**
			 * Filter the HTML tag that wraps the printed table name.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tag      The HTML tag around the table name. Default h2.
			 * @param string $table_id The current table ID.
			 */
			$print_name_html_tag = apply_filters( 'tablepress_print_name_html_tag', 'h2', $this->table['id'] );
			/**
			 * Filter the class attribute for the printed table name.
			 *
			 * @since 1.0.0
			 *
			 * @param string $class    The class attribute for the table name that can be used in CSS code.
			 * @param string $table_id The current table ID.
			 */
			$print_name_css_class = apply_filters( 'tablepress_print_name_css_class', "tablepress-table-name tablepress-table-name-id-{$this->table['id']}", $this->table['id'] );
			$print_name_html = "<{$print_name_html_tag} class=\"{$print_name_css_class}\">" . $this->safe_output( $this->table['name'] ) . "</{$print_name_html_tag}>\n";
		}
		if ( $this->render_options['print_description'] ) {
			/**
			 * Filter the HTML tag that wraps the printed table description.
			 *
			 * @since 1.0.0
			 *
			 * @param string $tag      The HTML tag around the table description. Default span.
			 * @param string $table_id The current table ID.
			 */
			$print_description_html_tag = apply_filters( 'tablepress_print_description_html_tag', 'span', $this->table['id'] );
			/**
			 * Filter the class attribute for the printed table description.
			 *
			 * @since 1.0.0
			 *
			 * @param string $class    The class attribute for the table description that can be used in CSS code.
			 * @param string $table_id The current table ID.
			 */
			$print_description_css_class = apply_filters( 'tablepress_print_description_css_class', "tablepress-table-description tablepress-table-description-id-{$this->table['id']}", $this->table['id'] );
			$print_description_html = "<{$print_description_html_tag} class=\"{$print_description_css_class}\">" . $this->safe_output( $this->table['description'] ) . "</{$print_description_html_tag}>\n";
		}

		if ( $this->render_options['print_name'] && 'above' == $this->render_options['print_name_position'] ) {
			$output .= $print_name_html;
		}
		if ( $this->render_options['print_description'] && 'above' == $this->render_options['print_description_position'] ) {
			$output .= $print_description_html;
		}

		// Deactivate nl2br() for this render process, if "convert_line_breaks" Shortcode parameter is set to false
		if ( ! $this->render_options['convert_line_breaks'] ) {
			add_filter( 'tablepress_apply_nl2br', '__return_false', 9 ); // priority 9, so that this filter can easily be overwritten at the default priority
		}

		$thead = '';
		$tfoot = '';
		$tbody = array();

		$this->last_row_idx = $num_rows - 1;
		$this->last_column_idx = $num_columns - 1;
		// loop through rows in reversed order, to search for rowspan trigger keyword
		for ( $row_idx = $this->last_row_idx; $row_idx >= 0; $row_idx-- ) {
			// last row, need to check for footer (but only if at least two rows)
			if ( $this->last_row_idx == $row_idx && $this->render_options['table_foot'] && $num_rows > 1 ) {
				$tfoot = $this->_render_row( $row_idx, 'th' );
				continue;
			}
			// first row, need to check for head (but only if at least two rows)
			if ( 0 == $row_idx && $this->render_options['table_head'] && $num_rows > 1 ) {
				$thead = $this->_render_row( $row_idx, 'th' );
				continue;
			}
			// neither first nor last row (with respective head/foot enabled), so render as body row
			$tbody[] = $this->_render_row( $row_idx, 'td' );
		}

		// Re-instate nl2br() behavior after this render process, if "convert_line_breaks" Shortcode parameter is set to false
		if ( ! $this->render_options['convert_line_breaks'] ) {
			remove_filter( 'tablepress_apply_nl2br', '__return_false', 9 ); // priority 9, so that this filter can easily be overwritten at the default priority
		}

		// <caption> tag
		/**
		 * Filter the content for the HTML caption element of the table.
		 *
		 * If the "Edit" link for a table is shown, it is also added to the caption element.
		 *
		 * @since 1.0.0
		 *
		 * @param string $caption The content for the HTML caption element of the table. Default empty.
		 * @param array  $table   The current table.
		 */
		$caption = apply_filters( 'tablepress_print_caption_text', '', $this->table );
		$caption_style = $caption_class = '';
		if ( ! empty( $caption ) ) {
			/**
			 * Filter the class attribute for the HTML caption element of the table.
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
			$caption .= "<a href=\"{$this->render_options['edit_table_url']}\">" . __( 'Edit', 'default' ) . '</a>';
		}
		if ( ! empty( $caption ) ) {
			$caption = "<caption{$caption_class}{$caption_style}>{$caption}</caption>\n";
		}

		// <colgroup> tag
		$colgroup = '';
		/**
		 * Filter whether the HTML colgroup tag shall be added to the table output.
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
				 * Filter the attributes of the HTML col tags in the HTML colgroup tag.
				 *
				 * @since 1.0.0
				 *
				 * @param string $attributes The attributes in the col element.
				 * @param string $table_id   The current table ID.
				 * @param int    $col_idx    The number of the column.
				 */
				$attributes = apply_filters( 'tablepress_colgroup_tag_attributes', $attributes, $this->table['id'], $col_idx + 1 );
				$colgroup .= "\t<col{$attributes}/>\n";
			}
		}
		if ( ! empty( $colgroup ) ) {
			$colgroup = "<colgroup>\n{$colgroup}</colgroup>\n";
		}

		// <thead>, <tfoot>, and <tbody> tags
		if ( ! empty( $thead ) ) {
			$thead = "<thead>\n{$thead}</thead>\n";
		}
		if ( ! empty( $tfoot ) ) {
			$tfoot = "<tfoot>\n{$tfoot}</tfoot>\n";
		}
		$tbody_class = ( $this->render_options['row_hover'] ) ? ' class="row-hover"' : '';
		$tbody = array_reverse( $tbody ); // because we looped through the rows in reverse order
		$tbody = "<tbody{$tbody_class}>\n" . implode( '', $tbody ) . "</tbody>\n";

		// Attributes for the table (HTML table element)
		$table_attributes = array();

		// "id" attribute
		if ( ! empty( $this->render_options['html_id'] ) ) {
			$table_attributes['id'] = $this->render_options['html_id'];
		}

		// "class" attribute
		$css_classes = array( 'tablepress', "tablepress-id-{$this->table['id']}", $this->render_options['extra_css_classes'] );
		/**
		 * Filter the CSS classes that are given to the HTML table element.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $css_classes The CSS classes for the table element.
		 * @param string $table_id    The current table ID.
		 */
		$css_classes = apply_filters( 'tablepress_table_css_classes', $css_classes, $this->table['id'] );
		$css_classes = explode( ' ', implode( ' ', $css_classes ) ); // $css_classes might contain several classes in one array entry
		$css_classes = array_map( 'sanitize_html_class', $css_classes );
		$css_classes = array_unique( $css_classes );
		$css_classes = trim( implode( ' ', $css_classes ) );
		if ( ! empty( $css_classes ) ) {
			$table_attributes['class'] = $css_classes;
		}

		// "summary" attribute
		$summary = '';
		/**
		 * Filter the content for the summary attribute of the HTML table element.
		 *
		 * The attribute is only added if it is not empty.
		 *
		 * @since 1.0.0
		 *
		 * @param string $summary The content for the summary attribute of the table. Default empty.
		 * @param array  $table   The current table.
		 */
		$summary = apply_filters( 'tablepress_print_summary_attr', $summary, $this->table );
		if ( ! empty( $summary ) ) {
			$table_attributes['summary'] = esc_attr( $summary );
		}

		// Legacy support for attributes that are not encouraged in HTML5
		foreach ( array( 'cellspacing', 'cellpadding', 'border' ) as $attribute ) {
			if ( false !== $this->render_options[ $attribute ] ) {
				$table_attributes[ $attribute ] = intval( $this->render_options[ $attribute ] );
			}
		}

		/**
		 * Filter the attributes for the table (HTML table element).
		 *
		 * @since 1.4.0
		 *
		 * @param array $table_attributes The attributes for the table element.
		 * @param array $table            The current table.
		 * @param array $render_options   The render options for the table.
		 */
		$table_attributes = apply_filters( 'tablepress_table_tag_attributes', $table_attributes, $this->table, $this->render_options );
		$table_attributes = $this->_attributes_array_to_string( $table_attributes );

		$output .= "\n<table{$table_attributes}>\n";
		$output .= $caption . $colgroup . $thead . $tfoot . $tbody;
		$output .= "</table>\n";

		// name/description below table (HTML already generated above)
		if ( $this->render_options['print_name'] && 'below' == $this->render_options['print_name_position'] ) {
			$output .= $print_name_html;
		}
		if ( $this->render_options['print_description'] && 'below' == $this->render_options['print_description_position'] ) {
			$output .= $print_description_html;
		}

		/**
		 * Filter the generated HTML code for table.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output         The generated HTML for the table.
		 * @param array  $table          The current table.
		 * @param array  $render_options The render options for the table.
		 */
		$this->output = apply_filters( 'tablepress_table_output', $output, $this->table, $this->render_options );
	}

	/**
	 * Generate the HTML of a row
	 *
	 * @since 1.0.0
	 *
	 * @param int $row_idx Index of the row to be rendered
	 * @param string $tag HTML tag to use for the cells (td or th)
	 * @return string HTML for the row
	 */
	protected function _render_row( $row_idx, $tag ) {
		$row_cells = array();
		// loop through cells in reversed order, to search for colspan or rowspan trigger words
		for ( $col_idx = $this->last_column_idx; $col_idx >= 0; $col_idx-- ) {
			$cell_content = $this->table['data'][ $row_idx ][ $col_idx ];

			// print formulas that are escaped with '= (like in Excel) as text:
			if ( "'=" == substr( $cell_content, 0, 2 ) ) {
				$cell_content = substr( $cell_content, 1 );
			}
			$cell_content = do_shortcode( $this->safe_output( $cell_content ) );
			/**
			 * Filter the content of a single cell.
			 *
			 * Filter the content of a single cell, after formulas have been evaluated, the output has been sanitized, and Shortcodes have been evaluated.
			 *
			 * @since 1.0.0
			 *
			 * @param string $cell_content The cell content.
			 * @param string $table_id     The current table ID.
			 * @param int    $row_idx      The row number of the cell.
			 * @param int    $col_idx      The column number of the cell.
			 */
			$cell_content = apply_filters( 'tablepress_cell_content', $cell_content, $this->table['id'], $row_idx + 1, $col_idx + 1 );

			if ( $this->span_trigger['rowspan'] == $cell_content ) { // there will be a rowspan
				// check for #rowspan# in first row, which doesn't make sense
				if ( ( $row_idx > 1 && $row_idx < $this->last_row_idx )
				|| ( 1 == $row_idx && ! $this->render_options['table_head'] ) // no rowspan into table_head
				|| ( $this->last_row_idx == $row_idx && ! $this->render_options['table_foot'] ) ) { // no rowspan out of table_foot
					$this->rowspan[ $col_idx ]++; // increase counter for rowspan in this column
					$this->colspan[ $row_idx ] = 1; // reset counter for colspan in this row, combined col- and rowspan might be happening
					continue;
				}
				// invalid rowspan, so we set cell content from #rowspan# to empty
				$cell_content = '';
			} elseif ( $this->span_trigger['colspan'] == $cell_content ) { // there will be a colspan
				// check for #colspan# in first column, which doesn't make sense
				if ( $col_idx > 1
				|| ( 1 == $col_idx && ! $this->render_options['first_column_th'] ) ) { // no colspan into first column head
					$this->colspan[ $row_idx ]++; // increase counter for colspan in this row
					$this->rowspan[ $col_idx ] = 1; // reset counter for rowspan in this column, combined col- and rowspan might be happening
					continue;
				}
				// invalid colspan, so we set cell content from #colspan# to empty
				$cell_content = '';
			} elseif ( $this->span_trigger['span'] == $cell_content ) { // there will be a combined col- and rowspan
				// check for #span# in first column or first or last row, which is not always possible
				if ( ( $row_idx > 1 && $row_idx < $this->last_row_idx && $col_idx > 1 )
				// we are in first, second, or last row or in the first or second column, so more checks are necessary
				|| ( ( 1 == $row_idx && ! $this->render_options['table_head'] ) // no rowspan into table_head
					&& ( $col_idx > 1 || ( 1 == $col_idx && ! $this->render_options['first_column_th'] ) ) ) // and no colspan into first column head
				|| ( ( $this->last_row_idx == $row_idx && ! $this->render_options['table_foot'] ) // no rowspan out of table_foot
					&& ( $col_idx > 1 || ( 1 == $col_idx && ! $this->render_options['first_column_th'] ) ) ) ) // and no colspan into first column head
					continue;
				// invalid span, so we set cell content from #span# to empty
				$cell_content = '';
			} elseif ( '' == $cell_content && 0 == $row_idx && $this->render_options['table_head'] ) {
				$cell_content = '&nbsp;'; // make empty cells have a space in the table head, to give sorting arrows the correct position in IE9
			}

			if ( 0 == $row_idx && $this->render_options['table_head'] ) {
				$cell_content = '<div>' . $cell_content . '</div>';
			}

			// Attributes for the table cell (HTML td or th element)
			$tag_attributes = array();

			// "colspan" and "rowspan" attributes
			if ( $this->colspan[ $row_idx ] > 1 ) { // we have colspaned cells
				$tag_attributes['colspan'] = $this->colspan[ $row_idx ];
			}
			if ( $this->rowspan[ $col_idx ] > 1 ) { // we have rowspaned cells
				$tag_attributes['rowspan'] = $this->rowspan[ $col_idx ];
			}

			// "class" attribute
			$cell_class = 'column-' . ( $col_idx + 1 );
			/**
			 * Filter the CSS classes that are given to a single cell (HTML td element) of a table.
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

			// "style" attribute
			if ( ( 0 == $row_idx ) && ! empty( $this->render_options['column_widths'][ $col_idx ] ) ) {
				$tag_attributes['style'] = 'width:' . preg_replace( '#[^0-9a-z.%]#', '', $this->render_options['column_widths'][ $col_idx ] ) . ';';
			}

			/**
			 * Filter the attributes for the table cell (HTML td or th element).
			 *
			 * @since 1.4.0
			 *
			 * @param array  $tag_attributes The attributes for the td or th element.
			 * @param string $table_id       The current table ID.
			 * @param string $cell_content   The cell content.
			 * @param int    $row_idx        The row number of the cell.
			 * @param int    $col_idx        The column number of the cell.
			 * @param int    $colspan_row    The number of combined columns for this cell.
			 * @param int    $rowspan_col    The number of combined rows for this cell.
			 */
			$tag_attributes = apply_filters( 'tablepress_cell_tag_attributes', $tag_attributes, $this->table['id'], $cell_content, $row_idx + 1, $col_idx + 1, $this->colspan[ $row_idx ], $this->rowspan[ $col_idx ] );
			$tag_attributes = $this->_attributes_array_to_string( $tag_attributes );

			if ( $this->render_options['first_column_th'] && 0 == $col_idx ) {
				$tag = 'th';
			}

			$row_cells[] = "<{$tag}{$tag_attributes}>{$cell_content}</{$tag}>";
			$this->colspan[ $row_idx ] = 1; // reset
			$this->rowspan[ $col_idx ] = 1; // reset
		}

		// Attributes for the table row (HTML tr element)
		$tr_attributes = array();

		// "class" attribute
		$row_classes = 'row-' . ( $row_idx + 1 ) ;
		if ( $this->render_options['alternating_row_colors'] ) {
			$row_classes .= ( 1 == ( $row_idx % 2 ) ) ? ' even' : ' odd';
		}
		/**
		 * Filter the CSS classes that are given to a row (HTML tr element) of a table.
		 *
		 * @since 1.0.0
		 *
		 * @param string $row_classes The CSS classes for the row.
		 * @param string $table_id    The current table ID.
		 * @param array  $row_cells   The HTML code for the cells of the row.
		 * @param int    $row_idx     The row number.
		 * @param array  $row_data    The content of the cells of the row.
		 */
		$row_classes = apply_filters( 'tablepress_row_css_class', $row_classes, $this->table['id'], $row_cells, $row_idx + 1, $this->table['data'][ $row_idx ] );
		if ( ! empty( $row_classes ) ) {
			$tr_attributes['class'] = $row_classes;
		}

		/**
		 * Filter the attributes for the table row (HTML tr element).
		 *
		 * @since 1.4.0
		 *
		 * @param array  $tr_attributes The attributes for the tr element.
		 * @param string $table_id      The current table ID.
		 * @param int    $row_idx       The row number.
		 * @param array  $row_data      The content of the cells of the row.
		 */
		$tr_attributes = apply_filters( 'tablepress_row_tag_attributes', $tr_attributes, $this->table['id'], $row_idx + 1, $this->table['data'][ $row_idx ] );
		$tr_attributes = $this->_attributes_array_to_string( $tr_attributes );

		$row_cells = array_reverse( $row_cells ); // because we looped through the cells in reverse order
		return "<tr{$tr_attributes}>\n\t" . implode( '', $row_cells ) . "\n</tr>\n";
	}

	/**
	 * Convert an array of HTML tag attributes to a string.
	 *
	 * @since 1.4.0
	 *
	 * @param array $attributes Attributes for the HTML tag in the array keys, and their values in the array values.
	 * @return string The attributes as a string for usage in a HTML element.
	 */
	protected function _attributes_array_to_string( array $attributes ) {
		$attributes_string = '';
		foreach ( $attributes as $attribute => $value ) {
			$attributes_string .= " {$attribute}=\"{$value}\"";
		}
		return $attributes_string;
	}

	/**
	 * Possibly replace certain HTML entities and replace line breaks with HTML
	 *
	 * @TODO: Find a better solution than this function, e.g. something like wpautop()
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to process
	 * @return string Processed string for output
	 */
	protected function safe_output( $string ) {
		// replace any & with &amp; that is not already an encoded entity (from function htmlentities2 in WP 2.8)
		// complete htmlentities2() or htmlspecialchars() would encode <HTML> tags, which we don't want
		$string = preg_replace( '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/', '&amp;', $string );
		/**
		 * Filter whether line breaks in the cell content shall be replaced with HTML br tags.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $replace  Whether to replace line breaks with HTML br tags. Default true.
		 * @param string $table_id The current table ID.
		 */
		if ( apply_filters( 'tablepress_apply_nl2br', true, $this->table['id'] ) ) {
			$string = nl2br( $string );
		}
		return $string;
	}

	/**
	 * Get the default render options, null means: Use option from "Edit" screen
	 *
	 * @since 1.0.0
	 *
	 * @return array Default render options
	 */
	public function get_default_render_options() {
		// Attention: Array keys have to be lowercase, otherwise they won't match the Shortcode attributes, which will be passed in lowercase by WP
		return array(
			'id' => '',
			'column_widths' => '',
			'alternating_row_colors' => null,
			'row_hover' => null,
			'table_head' => null,
			'table_foot' => null,
			'first_column_th' => false,
			'print_name' => null,
			'print_name_position' => null,
			'print_description' => null,
			'print_description_position' => null,
			'cache_table_output' => true,
			'convert_line_breaks' => true,
			'extra_css_classes' => null,
			'use_datatables' => null,
			'datatables_sort' => null,
			'datatables_paginate' => null,
			'datatables_paginate_entries' => null,
			'datatables_lengthchange' => null,
			'datatables_filter' => null,
			'datatables_info' => null,
			'datatables_scrollx' => null,
			'datatables_scrolly' => false,
			'datatables_custom_commands' => null,
			'datatables_locale' => get_locale(),
			'show_rows' => '',
			'show_columns' => '',
			'hide_rows' => '',
			'hide_columns' => '',
			'cellspacing' => false,
			'cellpadding' => false,
			'border' => false,
			'shortcode_debug' => false,
		);
	}

	/**
	 * Get the CSS code for the Preview iframe
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS for the Preview iframe
	 */
	public function get_preview_css() {
		if ( is_rtl() ) {
			$rtl = "\ndirection: rtl;";
			$rtl_align = 'right';
		} else {
			$rtl = '';
			$rtl_align = 'left';
		}
		return <<<CSS
<style type="text/css">
/* iframe */
body {
	margin: 10px;
	font-family: sans-serif;{$rtl}
}
/* Inline Shortcodes, in texts */
.table-shortcode-inline {
	background: transparent;
	border: none;
	color: #333333;
	width: 110px;
	margin: 0;
	padding: 0;
	-webkit-box-shadow: none;
	box-shadow: none;
	text-align: center;
	font-weight: bold;
	font-size: 100%;
}
.table-shortcode {
	cursor: text;
}
/* Default table styling */
.tablepress {
	border-collapse: collapse;
	border-spacing: 0;
	width: 100%;
	margin-bottom: 1em;
	border: none;
}
.tablepress td,
.tablepress th {
	padding: 8px;
	border: none;
	background: none;
	text-align: {$rtl_align};
}
.tablepress tbody td {
	vertical-align: top;
}
.tablepress tbody tr td,
.tablepress tfoot tr th {
	border-top: 1px solid #dddddd;
}
.tablepress tbody tr:first-child td {
	border-top: 0;
}
.tablepress thead tr th {
	border-bottom: 1px solid #dddddd;
}
.tablepress thead th,
.tablepress tfoot th {
	background-color: #d9edf7;
	font-weight: bold;
	vertical-align: middle;
}
.tablepress tbody tr.odd td {
	background-color: #f9f9f9;
}
.tablepress tbody tr.even td {
	background-color: #ffffff;
}
.tablepress .row-hover tr:hover td {
	background-color: #f3f3f3;
}
.tablepress img {
	margin: 0;
	padding: 0;
	border: none;
	max-width: none;
}
</style>
CSS;
	}

} // class TablePress_Render
