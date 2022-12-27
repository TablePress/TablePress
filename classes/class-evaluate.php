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
	protected function _should_use_legacy_evaluate_class() {
		/**
		 * Filters whether the Legacy Table Evaluate class shall be used.
		 *
		 * @since 2.0.0
		 *
		 * @param bool Whether to use the legacy table evaluate class. Default false.
		 */
		if ( apply_filters( 'tablepress_use_legacy_table_evaluate_class', false ) ) {
			return true;
		}

		// Use the legacy evaluate class, if the requirements for PHPSpreadsheet Calculations are not fulfilled.
		$phpspreadsheet_requirements_fulfilled = PHP_VERSION_ID >= 70200
			&& extension_loaded( 'mbstring' );
		if ( ! $phpspreadsheet_requirements_fulfilled ) {
			return true;
		}

		// Use the legacy evaluate class, if the PHPSpreadsheet files do not exist (e.g. because `composer install` was not run).
		if ( ! file_exists( TABLEPRESS_ABSPATH . 'libraries/autoload.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Evaluate formulas in the passed table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $table_data Table data in which formulas shall be evaluated.
	 * @param string $table_id   ID of the passed table.
	 * @return array Table data with evaluated formulas.
	 */
	public function evaluate_table_data( array $table_data, $table_id ) {
		$use_legacy_evaluate_class = $this->_should_use_legacy_evaluate_class();

		// Choose the Table Evaluate library based on the PHP version and the filter hook value.
		if ( $use_legacy_evaluate_class ) {
			$evaluate_class = TablePress::load_class( 'TablePress_Evaluate_Legacy', 'class-evaluate-legacy.php', 'classes' );
		} else {
			$evaluate_class = TablePress::load_class( 'TablePress_Evaluate_PHPSpreadsheet', 'class-evaluate-phpspreadsheet.php', 'classes' );
		}

		return $evaluate_class->evaluate_table_data( $table_data, $table_id );
	}

} // class TablePress_Evaluate
