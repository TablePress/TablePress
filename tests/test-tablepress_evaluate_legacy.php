<?php
/**
 * Tests for the TablePress_Evaluate_Legacy class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.5.0
 */

/**
 * Tests for the TablePress_Evaluate_Legacy class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.5.0
 */
class TablePress_Test_TablePress_Evaluate_Legacy extends TablePress_TestCase {

	/**
	 * Instance of the TablePress_Evaluate_Legacy class.
	 *
	 * @since 1.5.0
	 * @var TablePress_Evaluate_Legacy
	 */
	protected $evaluate;

	/**
	 * Load the TablePress_Evaluate_Legacy class PHP file once for all tests.
	 *
	 * @since 1.5.0
	 */
	#[\Override]
	public static function set_up_before_class(): void {
		TablePress_TestCase::set_up_before_class();
		require_once TABLEPRESS_ABSPATH . 'classes/class-evaluate-legacy.php';
	}

	/**
	 * Set up an instance of the TablePress_Evaluate_Legacy class before every test.
	 *
	 * @since 1.5.0
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();
		$this->evaluate = new TablePress_Evaluate_Legacy();
	}

	/**
	 * Test an empty one-cell table.
	 *
	 * @since 1.5.0
	 */
	public function test_empty_one_cell_table(): void {
		$table_id = '123';
		$input_table = array( array( '' ) );
		$expected_table = $input_table;
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table without a formula.
	 *
	 * @since 1.5.0
	 */
	public function test_table_without_formulas(): void {
		$table_id = '123';
		$input_table = array(
			array( '', '=', "'=" ),
			array( 'foo', 'bar', 'baz' ),
			array( '123', '456', '789' ),
			array( '3.5', '6.5', '9.5' ),
			array( '3.50', '6.50', '9.50' ),
			array( '+123', '+456', '+789' ),
			array( '+3.5', '+6.5', '+9.5' ),
			array( '-123', '-456', '-789' ),
			array( '-3.5', '-6.5', '-9.5' ),
			array( 'abc', 'def', 'ghi' ),
		);
		$expected_table = $input_table;
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table with basic formulas, but no references.
	 *
	 * @since 1.5.0
	 */
	public function test_table_with_basic_formulas(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '=3*4', '=4.5-1.0', '=POWER(2,3)' ),
			array( '=SUM(1,2,3)', '=AVERAGE(1,2,3)', '=0.0' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '12', '3.5', '8' ),
			array( '6', '2', '0.0' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table with variables.
	 *
	 * @since 1.12.0
	 */
	public function test_table_with_variables(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '=TABLE_ID', '=NUM_ROWS', '=NUM_COLUMNS' ),
			array( '=CELL', '=ROW', '=COLUMN' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '123', '3', '3' ),
			array( 'A3', '3', '3' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table with text around math expressions.
	 *
	 * @since 1.12.0
	 */
	public function test_table_with_text_around_expressions(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '=Table ID: {TABLE_ID}', '={5*2}', '{5*2}' ),
			array( '=This is row {ROW}, column {COLUMN}.', '=Total: {SUM(1,2,B2)}', '=' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( 'Table ID: 123', '10', '{5*2}' ),
			array( 'This is row 3, column 1.', 'Total: 13', '=' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table with formulas and single cell references.
	 *
	 * @since 1.5.0
	 */
	public function test_table_with_formulas_and_references(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '=MOD(8,3)' ),
			array( '=SUM(A2,B2,C2)', '=B2*C2', '=3-A2' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '2' ),
			array( '7', '8', '2' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test a table with formulas and multiple cell references/ranges.
	 *
	 * @since 1.5.0
	 */
	public function test_table_with_formulas_and_reference_ranges(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '=MAX(8,3)' ),
			array( '=SUM(A2:C2)', '=MIN(A2:C2)', '=PRODUCT(A2:B3)' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '8' ),
			array( '13', '1', '52' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test error handling for a circle reference.
	 *
	 * @since 1.5.0
	 */
	public function test_table_with_formulas_and_circle_reference_error(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '=B3+A3' ),
			array( '=SUM(A2:C2)', '2', '=A2+B2' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '!ERROR! Circle Reference' ),
			array( '!ERROR! Circle Reference', '2', '5' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

	/**
	 * Test error handling for a missing cell reference.
	 *
	 * @since 1.5.0
	 */
	public function test_table_with_formulas_and_missing_reference_error(): void {
		$table_id = '123';
		$input_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '5' ),
			array( '=SUM(A2:E2)', '=B4', '=A2+B2' ),
		);
		$expected_table = array(
			array( 'foo', 'bar', 'baz' ),
			array( '1', '4', '5' ),
			array( '!ERROR! Cell D2 does not exist', '!ERROR! Cell B4 does not exist', '5' ),
		);
		$evaluated_table = $this->evaluate->evaluate_table_data( $input_table, $table_id );
		$this->assertSame( $expected_table, $evaluated_table );
	}

} // class TablePress_Test_TablePress_Evaluate_Legacy
