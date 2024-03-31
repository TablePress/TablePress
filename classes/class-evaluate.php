<?php
/**
 * TablePress Formula Evaluation Class
 *
 * @package TablePress
 * @subpackage Formulas
 * @author Tobias Bäthge
 * @since 1.5.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Formula Evaluation Class
 *
 * Before TablePress 1.5, this was part of the TablePress_Render class.
 *
 * @package TablePress
 * @subpackage Formulas
 * @author Tobias Bäthge
 * @since 1.5.0
 */
class TablePress_Evaluate {

	/**
	 * Checks whether the requirements for the PHPSpreadsheet evaluate class are fulfilled or if the legacy evaluate class should be used.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Whether the legacy evaluate class should be used.
	 */
	protected function _should_use_legacy_evaluate_class(): bool {
		/**
		 * Filters whether the Legacy Table Evaluate class shall be used.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $use_legacy_class Whether to use the legacy table evaluate class. Default false.
		 */
		if ( apply_filters( 'tablepress_use_legacy_table_evaluate_class', false ) ) {
			return true;
		}

		// Use the legacy evaluate class, if the requirements for PHPSpreadsheet Calculations are not fulfilled.
		$phpspreadsheet_requirements_fulfilled = extension_loaded( 'mbstring' );
		if ( ! $phpspreadsheet_requirements_fulfilled ) {
			return true;
		}

		return false;
	}

	/**
	 * Evaluate formulas in the passed table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<int, string>> $table_data Table data in which formulas shall be evaluated.
	 * @param string                         $table_id   ID of the passed table.
	 * @return array<int, array<int, string>> Table data with evaluated formulas.
	 */
	public function evaluate_table_data( array $table_data, string $table_id ): array {
		// Choose the Table Evaluate library based on the PHP version and the filter hook value.
		if ( $this->_should_use_legacy_evaluate_class() ) {
			$evaluate_class = TablePress::load_class( 'TablePress_Evaluate_Legacy', 'class-evaluate-legacy.php', 'classes' );
		} else {
			$evaluate_class = TablePress::load_class( 'TablePress_Evaluate_PHPSpreadsheet', 'class-evaluate-phpspreadsheet.php', 'classes' );
		}

		return $evaluate_class->evaluate_table_data( $table_data, $table_id );
	}

} // class TablePress_Evaluate
