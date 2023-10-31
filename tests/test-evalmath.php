<?php
/**
 * Tests for the EvalMath class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.5.0
 */

/**
 * Tests for the EvalMath class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.5.0
 */
class TablePress_Test_EvalMath extends TablePress_TestCase {

	/**
	 * Instance of the EvalMath class.
	 *
	 * @since 1.5.0
	 * @var EvalMath
	 */
	protected $evalmath;

	/**
	 * Load the EvalMath class PHP file once for all tests.
	 *
	 * @since 1.5.0
	 */
	public static function set_up_before_class(): void {
		TablePress_TestCase::set_up_before_class();
		require_once TABLEPRESS_ABSPATH . 'libraries/evalmath.class.php';
	}

	/**
	 * Set up an instance of the EvalMath class before every test.
	 *
	 * @since 1.5.0
	 */
	public function set_up(): void {
		parent::set_up();
		$this->evalmath = new EvalMath();
	}

	/**
	 * Test that EvalMath classes are loaded.
	 *
	 * @since 1.5.0
	 */
	public function test_evalmath_classes_loaded(): void {
		$this->assertTrue( class_exists( 'EvalMath', false ) );
		$this->assertTrue( class_exists( 'EvalMath_Stack', false ) );
		$this->assertTrue( class_exists( 'EvalMath_Functions', false ) );
	}

	/**
	 * Test that a proper instance of the EvalMath class was created.
	 *
	 * @since 1.5.0
	 */
	public function test_evalmath_instance(): void {
		$this->assertInstanceOf( 'EvalMath', $this->evalmath );
	}

	/**
	 * Test basic formula evaluation.
	 *
	 * @since 1.5.0
	 */
	public function test_basic(): void {
		$result = $this->evalmath->evaluate( '1+2' );
		$this->assertSame( 3, $result );
	}

	/**
	 * Test some basic math functions.
	 *
	 * @since 1.5.0
	 */
	public function test_other_functions(): void {
		$this->assertSame( 2, $this->evalmath->evaluate( 'average(1,2,3)' ) );
		$this->assertSame( 1, $this->evalmath->evaluate( 'mod(10,3)' ) );
		$this->assertSame( 8, $this->evalmath->evaluate( 'power(2,3)' ) );
	}

	/**
	 * Test the min and max functions for integer and double inputs.
	 *
	 * @TODO: Use assertSame()!
	 *
	 * @since 1.5.0
	 */
	public function test_minmax_function(): void {
		$result = $this->evalmath->evaluate( 'min(20,10,30)' );
		$this->assertEquals( 10, $result );

		$result = $this->evalmath->evaluate( 'min(20.0,10.0,30.0)' );
		$this->assertEquals( 10.0, $result );

		$result = $this->evalmath->evaluate( 'max(10,30,20)' );
		$this->assertEquals( 30, $result );

		$result = $this->evalmath->evaluate( 'max(10.0,30.0,20.0)' );
		$this->assertEquals( 30.0, $result );
	}

	/**
	 * Test some slightly more complex expressions.
	 *
	 * @since 1.5.0
	 */
	public function test_more_complex_expressions(): void {
		$result = $this->evalmath->evaluate( 'pi() + 10' );
		$this->assertSame( pi() + 10, $result );

		$result = $this->evalmath->evaluate( 'pi()^10' );
		$this->assertSame( pow( pi(), 10 ), $result );

		$result = $this->evalmath->evaluate( '-8*(5/2)^2*(1-sqrt(4))-8' );
		$this->assertSame( -8 * pow( ( 5 / 2 ), 2 ) * ( 1 - sqrt( 4 ) ) - 8, $result );
	}

	/**
	 * Test error handling.
	 *
	 * @since 1.5.0
	 */
	public function test_error_handling(): void {
		$result = $this->evalmath->evaluate( 'pi( + 10' );
		$this->assertFalse( $result );
		$this->assertSame( "unexpected operator '+'", $this->evalmath->last_error );

		$result = $this->evalmath->evaluate( 'pi(' );
		$this->assertFalse( $result );
		$this->assertSame( 'expecting a closing bracket', $this->evalmath->last_error );

		$result = $this->evalmath->evaluate( 'pi^' );
		$this->assertFalse( $result );
		$this->assertSame( "operator '^' lacks operand", $this->evalmath->last_error );
	}

	/**
	 * Test rounding functions.
	 *
	 * @dataProvider data_rounding_function
	 *
	 * @since 1.5.0
	 *
	 * @param string $expression Expression with a rounding function to evaluate.
	 * @param double $result     Expected result.
	 */
	public function test_rounding_function( string $expression, float $result ): void {
		$this->assertSame( $result, $this->evalmath->evaluate( $expression ) );
	}

	/**
	 * Provide test data for the rounding functions test.
	 *
	 * @since 1.5.0
	 *
	 * @return array<int, array{string, float}> Test data.
	 */
	public function data_rounding_function(): array {
		return array(
			// Rounding to the default number of decimal places (0 decimals).
			array( 'round(2.5)', 3.0 ),
			array( 'round(1.5)', 2.0 ),
			array( 'round(-1.49)', -1.0 ),
			array( 'round(-2.49)', -2.0 ),
			array( 'round(-1.5)', -2.0 ),
			array( 'round(-2.5)', -3.0 ),
			array( 'ceil(2.5)', 3.0 ),
			array( 'ceil(1.5)', 2.0 ),
			array( 'ceil(-1.49)', -1.0 ),
			array( 'ceil(-2.49)', -2.0 ),
			array( 'ceil(-1.5)', -1.0 ),
			array( 'ceil(-2.5)', -2.0 ),
			array( 'floor(2.5)', 2.0 ),
			array( 'floor(1.5)', 1.0 ),
			array( 'floor(-1.49)', -2.0 ),
			array( 'floor(-2.49)', -3.0 ),
			array( 'floor(-1.5)', -2.0 ),
			array( 'floor(-2.5)', -3.0 ),
			// Rounding to a specific number of decimal places.
			array( 'round(2.5, 1)', 2.5 ),
			array( 'round(2.5, 0)', 3.0 ),
			array( 'round(1.2345, 2)', 1.23 ),
			array( 'round(123.456, -1)', 120.0 ),
		);
	}

	/**
	 * Test the conversion numbers in scientific notation.
	 *
	 * @since 1.5.0
	 */
	public function test_scientific_notation(): void {
		$this->assertEqualsWithDelta( 1e11, $this->evalmath->evaluate( '10e10' ), 1e11 * 1e-15 );
		$this->assertEqualsWithDelta( 1e-9, $this->evalmath->evaluate( '10e-10' ), 1e11 * 1e-15 );
		$this->assertEqualsWithDelta( 1e11, $this->evalmath->evaluate( '10e+10' ), 1e11 * 1e-15 );
		$this->assertEqualsWithDelta( 5e11, $this->evalmath->evaluate( '10e10*5' ), 1e11 * 1e-15 );
		$this->assertEqualsWithDelta( 1e22, $this->evalmath->evaluate( '10e10^2' ), 1e22 * 1e-15 );
	}

	/**
	 * Test the return types for the random number generators.
	 *
	 * @since 1.5.0
	 */
	public function test_rand_functions_type(): void {
		$result = $this->evalmath->evaluate( 'rand_float()' );
		$this->assertIsFloat( $result );

		$result = $this->evalmath->evaluate( 'rand_int(0,1000)' );
		$this->assertIsInt( $result );
	}

} // class TablePress_Test_EvalMath
