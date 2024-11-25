<?php
/**
 * CSSTidy Parsing PHP Class
 *
 * @package TablePress
 * @subpackage CSS
 * @author Florian Schmitz, Brett Zamir, Nikolay Matsievsky, Cedric Morin, Christopher Finke, Mark Scherer, Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Load the class for printing CSS code.
 *
 * @since 1.0.0
 */
require __DIR__ . '/class.csstidy_print.php';

/**
 * Load the class for optimising CSS code.
 *
 * @since 1.0.0
 */
require __DIR__ . '/class.csstidy_optimise.php';

/**
 * CSS Parser class
 *
 * This class represents a CSS parser which reads CSS code and saves it in an array.
 * In opposite to most other CSS parsers, it does not use regular expressions and
 * thus has full CSS2 support and a higher reliability.
 * Additionally to that, it applies some optimizations and fixes to the CSS code.
 *
 * @package CSSTidy
 * @since 1.0.0
 */
class TablePress_CSSTidy {

	/**
	 * Defines constants.
	 *
	 * @since 1.0.0
	 */
	public const AT_START = 1;
	public const AT_END = 2;
	public const SEL_START = 3;
	public const SEL_END = 4;
	public const PROPERTY = 5;
	public const VALUE = 6;
	public const COMMENT = 7;
	public const IMPORTANT_COMMENT = 8;
	public const DEFAULT_AT = 41;

	/**
	 * The parsed CSS.
	 *
	 * This array is empty if preserve_css is on.
	 *
	 * @since 1.0.0
	 */
	public array $css = array();

	/**
	 * The raw parsed CSS.
	 *
	 * @since 1.0.0
	 */
	public array $tokens = array();

	/**
	 * Instance of the CSS Printer class.
	 *
	 * @since 1.0.0
	 */
	public \TablePress_CSSTidy_Print $print;

	/**
	 * Instance of the CSS Optimiser class.
	 *
	 * @since 1.0.0
	 */
	public \TablePress_CSSTidy_Optimise $optimise;

	/**
	 * The CSS charset.
	 *
	 * @since 1.0.0
	 */
	public string $charset = '';

	/**
	 * All @import URLs.
	 *
	 * @since 1.0.0
	 */
	public array $import = array();

	/**
	 * The namespace.
	 *
	 * @since 1.0.0
	 */
	public string $namespace = '';

	/**
	 * The settings.
	 *
	 * @since 1.0.0
	 */
	protected array $settings = array();

	/**
	 * The parser-status.
	 *
	 * Possible values:
	 * - is = in selector
	 * - ip = in property
	 * - iv = in value
	 * - instr = in string (started at " or ' or ( )
	 * - ic = in comment (ignore everything)
	 * - at = in @-block
	 *
	 * @since 1.0.0
	 */
	protected string $status = 'is';

	/**
	 * The current at rule (@media).
	 *
	 * @since 1.0.0
	 */
	public string $at = '';

	/**
	 * The at rule for next selector (during @font-face or other @).
	 *
	 * @since 1.0.0
	 * @var string|int
	 */
	protected $next_selector_at = '';

	/**
	 * The current selector.
	 *
	 * @since 1.0.0
	 */
	public string $selector = '';

	/**
	 * The current property.
	 *
	 * @since 1.0.0
	 */
	public string $property = '';

	/**
	 * The position of , in selectors.
	 *
	 * @since 1.0.0
	 */
	protected array $sel_separate = array();

	/**
	 * The current value.
	 *
	 * @since 1.0.0
	 */
	public string $value = '';

	/**
	 * The current sub-value.
	 *
	 * Example for a sub-value: In the CSS rule
	 * background: url(foo.png) red no-repeat;
	 * "url(foo.png)", "red", and  "no-repeat" are sub-values,
	 * separated by whitespace.
	 *
	 * @since 1.0.0
	 */
	public string $sub_value = '';

	/**
	 * All sub-values for a property.
	 *
	 * @since 1.0.0
	 */
	protected array $sub_value_arr = array();

	/**
	 * The stack of characters that opened the current strings.
	 *
	 * @since 1.0.0
	 */
	public array $str_char = array();

	/**
	 * [$cur_string description]
	 *
	 * @since 1.0.0
	 */
	public array $cur_string = array();

	/**
	 * Status from which the parser switched to ic or instr
	 *
	 * @since 1.0.0
	 */
	protected array $from = array();

	/**
	 * True if in invalid at-rule.
	 *
	 * @since 1.0.0
	 */
	protected bool $invalid_at = false;

	/**
	 * True if something has been added to the current selector.
	 *
	 * @since 1.0.0
	 */
	protected bool $added = false;

	/**
	 * The message log.
	 *
	 * @since 1.0.0
	 */
	public array $log = array();

	/**
	 * The line number.
	 *
	 * @since 1.0.0
	 */
	protected int $line = 1;

	/**
	 * Marks if we need to leave quotes for a string.
	 *
	 * @since 1.0.0
	 */
	protected array $quoted_string = array();

	/**
	 * List of tokens.
	 *
	 * @since 1.0.0
	 */
	protected string $tokens_list = '';

	/**
	 * Various CSS Data for CSSTidy.
	 *
	 * @since 1.0.0
	 */
	public array $data = array();

	/**
	 * The output templates.
	 *
	 * @since 1.0.0
	 */
	public array $template = array();

	/**
	 * Loads standard template and sets default settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->data = require __DIR__ . '/data.inc.php';

		$this->settings['remove_bslash'] = true;
		$this->settings['compress_colors'] = true;
		$this->settings['compress_font-weight'] = true;
		$this->settings['lowercase_s'] = false;

		/*
		 * 1 common shorthands optimization
		 * 2 + font property optimization
		 * 3 + background property optimization
		 */
		$this->settings['optimise_shorthands'] = 1;
		$this->settings['remove_last_;'] = true;
		// Rewrite all properties with lower case, better for later gzipping.
		$this->settings['case_properties'] = 1;

		/*
		 * Sort properties in alphabetic order, better for later gzipping,
		 * but can cause trouble in case of overriding same properties or using hacks.
		 */
		$this->settings['sort_properties'] = false;

		/*
		 * 1, 3, 5, etc -- Enable sorting selectors inside @media: a{}b{}c{}.
		 * 2, 5, 8, etc -- Enable sorting selectors inside one CSS declaration: a,b,c{}.
		 * Preserve order by default cause it can break functionality.
		 */
		$this->settings['sort_selectors'] = 0;
		// Is dangerous to be used: CSS is broken sometimes.
		$this->settings['merge_selectors'] = 0;
		// Whether to preserve browser hacks.
		$this->settings['discard_invalid_selectors'] = false;
		$this->settings['discard_invalid_properties'] = false;
		$this->settings['css_level'] = 'CSS3.0';
		$this->settings['preserve_css'] = false;
		$this->settings['timestamp'] = false;
		$this->settings['template'] = ''; // say that property exists.
		$this->set_cfg( 'template', 'default' ); // Call load_template.

		$this->print = new TablePress_CSSTidy_Print( $this );
		$this->optimise = new TablePress_CSSTidy_Optimise( $this );

		$this->tokens_list = &$this->data['csstidy']['tokens'];
	}

	/**
	 * Gets the value of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting Setting to get.
	 * @return string|int|bool Value of the setting.
	 */
	public function get_cfg( string $setting ) /* : string|int|bool */ {
		if ( isset( $this->settings[ $setting ] ) ) {
			return $this->settings[ $setting ];
		}
		return false;
	}

	/**
	 * Sets the value of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $setting Setting.
	 * @param string|int|bool|null $value   Optional. Value of the setting.
	 * @return bool Whether the setting was set.
	 */
	public function set_cfg( string $setting, $value = null ): bool {
		if ( isset( $this->settings[ $setting ] ) && '' !== $value ) {
			$this->settings[ $setting ] = $value;
			if ( 'template' === $setting ) {
				$this->load_template( $this->settings['template'] );
			}
			return true;
		}
		return false;
	}

	/**
	 * Adds a token to $this->tokens.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $type  Type.
	 * @param string $data  Data.
	 * @param bool   $force Optional. Add a token even if preserve_css is off.
	 */
	public function _add_token( $type, string $data, bool $force = false ): void {
		if ( $this->get_cfg( 'preserve_css' ) || $force ) {
			// nested @...: if opening a new part we just closed, remove the previous closing instead of adding opening.
			if ( self::AT_START === $type
				&& count( $this->tokens )
				&& ( $last = end( $this->tokens ) ) // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found,Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
				&& self::AT_END === $last[0]
				&& trim( $data ) === $last[1] ) {
					array_pop( $this->tokens );
			} else {
				$this->tokens[] = array( $type, ( self::COMMENT === $type || self::IMPORTANT_COMMENT === $type ) ? $data : trim( $data ) );
			}
		}
	}

	/**
	 * Adds a message to the message log.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @param string $type    Type.
	 * @param int    $line    Optional. Line number. -1 will use the current line.
	 */
	public function log( string $message, string $type, int $line = -1 ): void {
		if ( -1 === $line ) {
			$line = $this->line;
		}
		$line = (int) $line;
		$add = array(
			'm' => $message,
			't' => $type,
		);
		if ( ! isset( $this->log[ $line ] ) || ! in_array( $add, $this->log[ $line ], true ) ) {
			$this->log[ $line ][] = $add;
		}
	}

	/**
	 * Parses Unicode notations and find a replacement character.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a_string String.
	 * @param int    $i        i.
	 * @return string [return value]
	 */
	public function _unicode( string &$a_string, int &$i ): string {
		++$i;
		$add = '';
		$replaced = false;

		while ( $i < strlen( $a_string ) && ( ctype_xdigit( $a_string[ $i ] ) || ctype_space( $a_string[ $i ] ) ) && strlen( $add ) < 6 ) { // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
			$add .= $a_string[ $i ];
			if ( ctype_space( $a_string[ $i ] ) ) {
				break;
			}
			++$i;
		}

		if ( ( hexdec( $add ) > 47 && hexdec( $add ) < 58 ) || ( hexdec( $add ) > 64 && hexdec( $add ) < 91 ) || ( hexdec( $add ) > 96 && hexdec( $add ) < 123 ) ) {
			$this->log( 'Replaced unicode notation: Changed \\' . $add . ' to ' . chr( hexdec( $add ) ), 'Information' );
			$add = chr( hexdec( $add ) );
			$replaced = true;
		} else {
			$add = trim( '\\' . $add );
		}

		if ( ( @ctype_xdigit( $a_string[ $i + 1 ] ) && ctype_space( $a_string[ $i ] ) && ! $replaced ) || ! ctype_space( $a_string[ $i ] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			--$i;
		}

		if ( '\\' !== $add || ! $this->get_cfg( 'remove_bslash' ) || str_contains( $this->tokens_list, $a_string[ $i + 1 ] ) ) {
			return $add;
		}

		if ( '\\' === $add ) {
			$this->log( 'Removed unnecessary backslash', 'Information' );
		}

		return '';
	}

	/**
	 * Loads a new template.
	 *
	 * @since 1.0.0
	 *
	 * @link https://csstidy.sourceforge.net/templates.php
	 *
	 * @param string $content   Either file name (if $from_file is true), content of a template file, "default", "low", high", or "highest".
	 * @param bool   $from_file Optional. Uses $content as filename if true.
	 */
	protected function load_template( string $content, bool $from_file = true ): void {
		if ( in_array( $content, array( 'default', 'low', 'high', 'highest' ), true ) ) {
			$this->template = $this->data['csstidy']['predefined_templates'][ $content ];
			return;
		}

		if ( $from_file ) {
			$content = strip_tags( file_get_contents( $content ), '<span>' );
		}

		// Unify newlines (because the output also only uses \n).
		$content = str_replace( "\r\n", "\n", $content );
		$this->template = explode( '|', $content );
	}

	/**
	 * Starts parsing from URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return bool Whether the CSS code could be parsed successfully.
	 */
	protected function parse_from_url( string $url ): bool {
		$data = file_get_contents( $url );
		if ( false === $data ) {
			return false;
		}
		return $this->parse( $data );
	}

	/**
	 * Checks if there is a token at the current position.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a_string String.
	 * @param int    $i      i.
	 * @return bool [return value]
	 */
	protected function is_token( string &$a_string, int $i ): bool {
		return ( str_contains( $this->tokens_list, $a_string[ $i ] ) && ! $this->escaped( $a_string, $i ) );
	}

	/**
	 * Parses CSS in a string. The output is saved as an array in $this->css.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a_string The CSS code.
	 * @return bool Whether the CSS code could be parsed successfully.
	 */
	public function parse( string $a_string ): bool {
		// Temporarily set locale to en_US in order to handle floats properly.
		$old = @setlocale( LC_ALL, 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@setlocale( LC_ALL, 'C' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		$at_rules = &$this->data['csstidy']['at_rules'];
		$quoted_string_properties = &$this->data['csstidy']['quoted_string_properties'];

		$this->css = array();
		$this->print->input_css = $a_string;
		$a_string = str_replace( "\r\n", "\n", $a_string ) . ' ';
		$cur_comment = '';
		$cur_at = '';

		for ( $i = 0, $size = strlen( $a_string ); $i < $size; $i++ ) {
			if ( "\n" === $a_string[ $i ] || "\r" === $a_string[ $i ] ) {
				++$this->line;
			}

			switch ( $this->status ) {
				/* Case in at-block */
				case 'at':
					if ( $this->is_token( $a_string, $i ) ) {
						if ( '/' === $a_string[ $i ] && '*' === @$a_string[ $i + 1 ] ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
							$this->status = 'ic';
							++$i;
							$this->from[] = 'at';
						} elseif ( '{' === $a_string[ $i ] ) {
							$this->status = 'is';
							$this->at = $this->css_new_media_section( $this->at, $cur_at );
							$this->_add_token( self::AT_START, $this->at );
						} elseif ( ',' === $a_string[ $i ] ) {
							$cur_at = trim( $cur_at ) . ',';
						} elseif ( '\\' === $a_string[ $i ] ) {
							$cur_at .= $this->_unicode( $a_string, $i );
						} elseif ( in_array( $a_string[ $i ], array( '(', ')', ':', '.', '/' ), true ) ) {
							// Fix for complicated media, i.e @media screen and (-webkit-min-device-pixel-ratio:1.5).
							// '/' is included for ratios in Opera: (-o-min-device-pixel-ratio: 3/2).
							$cur_at .= $a_string[ $i ];
						}
					} else {
						$lastpos = strlen( $cur_at ) - 1;
						if ( ! ( ( ctype_space( $cur_at[ $lastpos ] ) || ( $this->is_token( $cur_at, $lastpos ) && ',' === $cur_at[ $lastpos ] ) ) && ctype_space( $a_string[ $i ] ) ) ) {
							$cur_at .= $a_string[ $i ];
						}
					}
					break;
				/* Case in-selector */
				case 'is':
					if ( $this->is_token( $a_string, $i ) ) {
						if ( '/' === $a_string[ $i ] && '*' === @$a_string[ $i + 1 ] && '' === trim( $this->selector ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
							$this->status = 'ic';
							++$i;
							$this->from[] = 'is';
						} elseif ( '@' === $a_string[ $i ] && '' === trim( $this->selector ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
							// Check for at-rule.
							$this->invalid_at = true;
							foreach ( $at_rules as $name => $type ) {
								if ( ! strcasecmp( substr( $a_string, $i + 1, strlen( $name ) ), $name ) ) {
									if ( 'at' === $type ) {
										$cur_at = '@' . $name;
									} else {
										$this->selector = '@' . $name;
									}
									if ( 'atis' === $type ) {
										$this->next_selector_at = ( $this->next_selector_at ? $this->next_selector_at : ( $this->at ? $this->at : self::DEFAULT_AT ) );
										$this->at = $this->css_new_media_section( $this->at, ' ', true );
										$type = 'is';
									}
									$this->status = $type;
									$i += strlen( $name );
									$this->invalid_at = false;
									break;
								}
							}

							if ( $this->invalid_at ) {
								$this->selector = '@';
								$invalid_at_name = '';
								for ( $j = $i + 1; $j < $size; ++$j ) {
									if ( ! ctype_alpha( $a_string[ $j ] ) ) {
										break;
									}
									$invalid_at_name .= $a_string[ $j ];
								}
								$this->log( 'Invalid @-rule: ' . $invalid_at_name . ' (removed)', 'Warning' );
							}
						} elseif ( ( '"' === $a_string[ $i ] || "'" === $a_string[ $i ] ) ) {
							$this->cur_string[] = $a_string[ $i ];
							$this->status = 'instr';
							$this->str_char[] = $a_string[ $i ];
							$this->from[] = 'is';
							/* Fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
							$this->quoted_string[] = ( '=' === $a_string[ $i - 1 ] );
						} elseif ( $this->invalid_at && ';' === $a_string[ $i ] ) {
							$this->invalid_at = false;
							$this->status = 'is';
							if ( $this->next_selector_at ) {
								$this->at = $this->css_close_media_section( $this->at );
								$this->at = $this->css_new_media_section( $this->at, $this->next_selector_at );
								$this->next_selector_at = '';
							}
						} elseif ( '{' === $a_string[ $i ] ) {
							$this->status = 'ip';
							if ( '' === $this->at ) {
								$this->at = $this->css_new_media_section( $this->at, self::DEFAULT_AT );
							}
							$this->selector = $this->css_new_selector( $this->at, $this->selector );
							$this->_add_token( self::SEL_START, $this->selector );
							$this->added = false;
						} elseif ( '}' === $a_string[ $i ] ) {
							$this->_add_token( self::AT_END, $this->at );
							$this->at = $this->css_close_media_section( $this->at );
							$this->selector = '';
							$this->sel_separate = array();
						} elseif ( ',' === $a_string[ $i ] ) {
							$this->selector = trim( $this->selector ) . ',';
							$this->sel_separate[] = strlen( $this->selector );
						} elseif ( '\\' === $a_string[ $i ] ) {
							$this->selector .= $this->_unicode( $a_string, $i );
						} elseif ( '*' === $a_string[ $i ] && @in_array( $a_string[ $i + 1 ], array( '.', '#', '[', ':' ), true ) && ( 0 === $i || '/' !== $a_string[ $i - 1 ] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Generic.CodeAnalysis.EmptyStatement.DetectedElseif
							// Remove unnecessary universal selector, FS#147, but not comment in selector.
						} else {
							$this->selector .= $a_string[ $i ];
						}
					} else {
						$lastpos = strlen( $this->selector ) - 1;
						if ( -1 === $lastpos || ! ( ( ctype_space( $this->selector[ $lastpos ] ) || ( $this->is_token( $this->selector, $lastpos ) && ',' === $this->selector[ $lastpos ] ) ) && ctype_space( $a_string[ $i ] ) ) ) {
							$this->selector .= $a_string[ $i ];
						}
					}
					break;
				/* Case in-property */
				case 'ip':
					if ( $this->is_token( $a_string, $i ) ) {
						if ( ( ':' === $a_string[ $i ] || '=' === $a_string[ $i ] ) && '' !== $this->property ) {
							$this->status = 'iv';
							if ( ! $this->get_cfg( 'discard_invalid_properties' ) || $this->property_is_valid( $this->property ) ) {
								$this->property = $this->css_new_property( $this->at, $this->selector, $this->property );
								$this->property = strtolower( $this->property );
								$this->_add_token( self::PROPERTY, $this->property );
							}
						} elseif ( '/' === $a_string[ $i ] && '*' === @$a_string[ $i + 1 ] && '' === $this->property ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
							$this->status = 'ic';
							++$i;
							$this->from[] = 'ip';
						} elseif ( '}' === $a_string[ $i ] ) {
							$this->explode_selectors();
							$this->status = 'is';
							$this->invalid_at = false;
							$this->_add_token( self::SEL_END, $this->selector );
							$this->selector = '';
							$this->property = '';
							if ( $this->next_selector_at ) {
								$this->at = $this->css_close_media_section( $this->at );
								$this->at = $this->css_new_media_section( $this->at, $this->next_selector_at );
								$this->next_selector_at = '';
							}
						} elseif ( ';' === $a_string[ $i ] ) {
							$this->property = '';
						} elseif ( '\\' === $a_string[ $i ] ) {
							$this->property .= $this->_unicode( $a_string, $i );
						} elseif ( ( '' === $this->property && ! ctype_space( $a_string[ $i ] ) ) || ( '/' === $this->property || '/' === $a_string[ $i ] ) ) {
							// else this is dumb IE a hack, keep it.
							// including /.
							$this->property .= $a_string[ $i ];
						}
					} elseif ( ! ctype_space( $a_string[ $i ] ) ) {
						$this->property .= $a_string[ $i ];
					}
					break;
				/* Case in-value */
				case 'iv':
					$pn = ( ( ( "\n" === $a_string[ $i ] || "\r" === $a_string[ $i ] ) && $this->property_is_next( $a_string, $i + 1 ) ) || ( strlen( $a_string ) - 1 ) === $i );
					if ( ( $this->is_token( $a_string, $i ) || $pn ) && ( ! ( ',' === $a_string[ $i ] && ! ctype_space( $a_string[ $i + 1 ] ) ) ) ) {
						if ( '/' === $a_string[ $i ] && '*' === @$a_string[ $i + 1 ] ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
							$this->status = 'ic';
							++$i;
							$this->from[] = 'iv';
						} elseif ( ( '"' === $a_string[ $i ] || "'" === $a_string[ $i ] || '(' === $a_string[ $i ] ) ) {
							$this->cur_string[] = $a_string[ $i ];
							$this->str_char[] = ( '(' === $a_string[ $i ] ) ? ')' : $a_string[ $i ];
							$this->status = 'instr';
							$this->from[] = 'iv';
							$this->quoted_string[] = in_array( strtolower( $this->property ), $quoted_string_properties, true );
						} elseif ( ',' === $a_string[ $i ] ) {
							$this->sub_value = trim( $this->sub_value ) . ',';
						} elseif ( '\\' === $a_string[ $i ] ) {
							$this->sub_value .= $this->_unicode( $a_string, $i );
						} elseif ( ';' === $a_string[ $i ] || $pn ) {
							if ( '@' === $this->selector[0] && isset( $at_rules[ substr( $this->selector, 1 ) ] ) && 'iv' === $at_rules[ substr( $this->selector, 1 ) ] ) {
								$this->status = 'is';

								switch ( $this->selector ) {
									case '@charset':
										// Add quotes to charset.
										$this->sub_value_arr[] = '"' . trim( $this->sub_value ) . '"';
										$this->charset = $this->sub_value_arr[0];
										break;
									case '@namespace':
										// Add quotes to namespace.
										$this->sub_value_arr[] = '"' . trim( $this->sub_value ) . '"';
										$this->namespace = implode( ' ', $this->sub_value_arr );
										break;
									case '@import':
										$this->sub_value = trim( $this->sub_value );

										if ( empty( $this->sub_value_arr ) ) {
											// Quote URLs in imports only if they're not already inside url() and not already quoted.
											if ( ! str_starts_with( $this->sub_value, 'url(' ) ) {
												if ( ! str_ends_with( $this->sub_value, $this->sub_value[0] ) && in_array( $this->sub_value[0], array( "'", '"' ), true ) ) {
													$this->sub_value = '"' . $this->sub_value . '"';
												}
											}
										}

										$this->sub_value_arr[] = $this->sub_value;
										$this->import[] = implode( ' ', $this->sub_value_arr );
										break;
								}

								$this->sub_value_arr = array();
								$this->sub_value = '';
								$this->selector = '';
								$this->sel_separate = array();
							} else {
								$this->status = 'ip';
							}
						} elseif ( '}' !== $a_string[ $i ] ) {
							$this->sub_value .= $a_string[ $i ];
						}
						if ( ( '}' === $a_string[ $i ] || ';' === $a_string[ $i ] || $pn ) && ! empty( $this->selector ) ) {
							if ( '' === $this->at ) {
								$this->at = $this->css_new_media_section( $this->at, self::DEFAULT_AT );
							}

							// Case settings.
							if ( $this->get_cfg( 'lowercase_s' ) ) {
								$this->selector = strtolower( $this->selector );
							}
							$this->property = strtolower( $this->property );

							$this->optimise->subvalue();
							if ( '' !== $this->sub_value ) {
								$this->sub_value_arr[] = $this->sub_value;
								$this->sub_value = '';
							}

							$this->value = '';
							while ( count( $this->sub_value_arr ) ) { // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
								$sub = array_shift( $this->sub_value_arr );
								if ( strstr( $this->selector, 'font-face' ) ) {
									$sub = $this->quote_font_format( $sub );
								}

								if ( '' !== $sub ) {
									if ( strlen( $this->value ) && ( ! str_ends_with( $this->value, ',' ) || $this->get_cfg( 'preserve_css' ) ) ) {
										$this->value .= ' ';
									}
									$this->value .= $sub;
								}
							}

							$this->optimise->value();

							$valid = $this->property_is_valid( $this->property );
							if ( ( ! $this->invalid_at || $this->get_cfg( 'preserve_css' ) ) && ( ! $this->get_cfg( 'discard_invalid_properties' ) || $valid ) ) {
								$this->css_add_property( $this->at, $this->selector, $this->property, $this->value );
								$this->_add_token( self::VALUE, $this->value );
								$this->optimise->shorthands();
							}
							if ( ! $valid ) {
								if ( $this->get_cfg( 'discard_invalid_properties' ) ) {
									$this->log( 'Removed invalid property: ' . $this->property, 'Warning' );
								} else {
									$this->log( 'Invalid property in ' . strtoupper( $this->get_cfg( 'css_level' ) ) . ': ' . $this->property, 'Warning' );
								}
							}

							$this->property = '';
							$this->sub_value_arr = array();
							$this->value = '';
						}
						if ( '}' === $a_string[ $i ] ) {
							$this->explode_selectors();
							$this->_add_token( self::SEL_END, $this->selector );
							$this->status = 'is';
							$this->invalid_at = false;
							$this->selector = '';
							if ( $this->next_selector_at ) {
								$this->at = $this->css_close_media_section( $this->at );
								$this->at = $this->css_new_media_section( $this->at, $this->next_selector_at );
								$this->next_selector_at = '';
							}
						}
					} elseif ( ! $pn ) {
						$this->sub_value .= $a_string[ $i ];

						if ( ctype_space( $a_string[ $i ] ) || ',' === $a_string[ $i ] ) {
							$this->optimise->subvalue();
							if ( '' !== $this->sub_value ) {
								$this->sub_value_arr[] = $this->sub_value;
								$this->sub_value = '';
							}
						}
					}
					break;
				/* Case in string */
				case 'instr':
					$_str_char = $this->str_char[ count( $this->str_char ) - 1 ];
					$_cur_string = $this->cur_string[ count( $this->cur_string ) - 1 ];
					$_quoted_string = $this->quoted_string[ count( $this->quoted_string ) - 1 ];
					$temp_add = $a_string[ $i ];

					// Add another string to the stack. Strings can't be nested inside of quotes, only parentheses,
					// but parentheticals can be nested more than once.
					if ( ')' === $_str_char && ( '(' === $a_string[ $i ] || '"' === $a_string[ $i ] || '\\' === $a_string[ $i ] ) && ! $this->escaped( $a_string, $i ) ) {
						$this->cur_string[] = $a_string[ $i ];
						$this->str_char[] = ( '(' === $a_string[ $i ] ) ? ')' : $a_string[ $i ];
						$this->from[] = 'instr';
						$this->quoted_string[] = ( ')' === $_str_char && '(' !== $a_string[ $i ] && '(' === trim( $_cur_string ) ) ? $_quoted_string : ( '(' !== $a_string[ $i ] );
						continue 2;
					}

					if ( ')' !== $_str_char && ( "\n" === $a_string[ $i ] || "\r" === $a_string[ $i ] ) && ! ( '\\' === $a_string[ $i - 1 ] && ! $this->escaped( $a_string, $i - 1 ) ) ) {
						$temp_add = '\\A';
						$this->log( 'Fixed incorrect newline in string', 'Warning' );
					}

					$_cur_string .= $temp_add;

					if ( $a_string[ $i ] === $_str_char && ! $this->escaped( $a_string, $i ) ) {
						$this->status = array_pop( $this->from );

						if ( ! preg_match( '|[' . implode( '', $this->data['csstidy']['whitespace'] ) . ']|uis', $_cur_string ) && 'content' !== $this->property ) {
							if ( ! $_quoted_string ) {
								if ( ')' !== $_str_char ) {
									/*
									 * Convert properties like
									 * font-family: 'Arial';
									 * to
									 * font-family: Arial;
									 * or
									 * url("abc")
									 * to
									 * url(abc)
									 */
									$_cur_string = substr( $_cur_string, 1, -1 );
								}
							} else {
								$_quoted_string = false;
							}
						}

						array_pop( $this->cur_string );
						array_pop( $this->quoted_string );
						array_pop( $this->str_char );

						if ( ')' === $_str_char ) {
							$_cur_string = '(' . trim( substr( $_cur_string, 1, -1 ) ) . ')';
						}

						if ( 'iv' === $this->status ) {
							if ( ! $_quoted_string ) {
								if ( str_contains( $_cur_string, ',' ) ) {
									// We can on only remove space next to ','.
									$_cur_string = implode( ',', array_map( 'trim', explode( ',', $_cur_string ) ) );
								}
								// and multiple spaces (too expensive).
								if ( str_contains( $_cur_string, '  ' ) ) {
									$_cur_string = preg_replace( ',\s+,', ' ', $_cur_string );
								}
							}
							$this->sub_value .= $_cur_string;
						} elseif ( 'is' === $this->status ) {
							$this->selector .= $_cur_string;
						} elseif ( 'instr' === $this->status ) {
							$this->cur_string[ count( $this->cur_string ) - 1 ] .= $_cur_string;
						}
					} else {
						$this->cur_string[ count( $this->cur_string ) - 1 ] = $_cur_string;
					}
					break;
				/* Case in-comment */
				case 'ic':
					if ( '*' === $a_string[ $i ] && '/' === $a_string[ $i + 1 ] ) {
						$this->status = array_pop( $this->from );
						++$i;
						if ( strlen( $cur_comment ) > 1 && str_starts_with( $cur_comment, '!' ) ) {
							$this->_add_token( self::IMPORTANT_COMMENT, $cur_comment );
							$this->css_add_important_comment( $cur_comment );
						} else {
							$this->_add_token( self::COMMENT, $cur_comment );
						}
						$cur_comment = '';
					} else {
						$cur_comment .= $a_string[ $i ];
					}
					break;
			}
		}

		$this->optimise->postparse();
		$this->print->_reset();

		// Set locale back to original setting.
		@setlocale( LC_ALL, $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return ! ( empty( $this->css ) && empty( $this->import ) && empty( $this->charset ) && empty( $this->tokens ) && empty( $this->namespace ) );
	}

	/**
	 * Quoting: format() in font-face needs quoted values for some browser (FF at least).
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Value.
	 * @return string String.
	 */
	protected function quote_font_format( string $value ): string {
		if ( str_starts_with( $value, 'format' ) ) {
			$p = strpos( $value, ')', 7 );
			$end = substr( $value, $p );
			$format_strings = $this->parse_string_list( substr( $value, 7, $p - 7 ) );
			if ( ! $format_strings ) {
				$value = '';
			} else {
				$value = 'format(';
				foreach ( $format_strings as $format_string ) {
					$value .= '"' . str_replace( '"', '\\"', $format_string ) . '",';
				}
				$value = substr( $value, 0, -1 ) . $end;
			}
		}
		return $value;
	}

	/**
	 * Explodes selectors.
	 *
	 * @since 1.0.0
	 */
	protected function explode_selectors(): void {
		// Explode multiple selectors.
		if ( 1 === $this->get_cfg( 'merge_selectors' ) ) {
			$new_sels = array();
			$lastpos = 0;
			$this->sel_separate[] = strlen( $this->selector );
			foreach ( $this->sel_separate as $num => $pos ) {
				if ( ( count( $this->sel_separate ) - 1 ) === $num ) {
					++$pos;
				}

				$new_sels[] = substr( $this->selector, $lastpos, $pos - $lastpos - 1 );
				$lastpos = $pos;
			}

			if ( count( $new_sels ) > 1 ) {
				foreach ( $new_sels as $selector ) {
					if ( isset( $this->css[ $this->at ][ $this->selector ] ) ) {
						$this->merge_css_blocks( $this->at, $selector, $this->css[ $this->at ][ $this->selector ] );
					}
				}
				unset( $this->css[ $this->at ][ $this->selector ] );
			}
		}
		$this->sel_separate = array();
	}

	/**
	 * Checks if a character is escaped.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a_string String.
	 * @param int    $pos    Position.
	 * @return bool Whether a character is escaped.
	 */
	public function escaped( string &$a_string, int $pos ): bool {
		return $pos ? ! ( @( '\\' !== $a_string[ $pos - 1 ] ) || $this->escaped( $a_string, $pos - 1 ) ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Adds an important comment to the CSS code (one we want to keep when minifying).
	 *
	 * @since 1.10.0
	 *
	 * @param string $comment CSS Comment.
	 */
	protected function css_add_important_comment( string $comment ): void {
		if ( $this->get_cfg( 'preserve_css' ) || '' === trim( $comment ) ) {
			return;
		}
		if ( ! isset( $this->css['!'] ) ) {
			$this->css['!'] = '';
		} else {
			$this->css['!'] .= "\n";
		}
		$this->css['!'] .= $comment;
	}

	/**
	 * Adds a property with value to the existing CSS code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $media    Media.
	 * @param string $selector Selector.
	 * @param string $property Property.
	 * @param string $new_val  New value.
	 */
	protected function css_add_property( string $media, string $selector, string $property, string $new_val ): void {
		if ( $this->get_cfg( 'preserve_css' ) || '' === trim( $new_val ) ) {
			return;
		}

		$this->added = true;
		if ( isset( $this->css[ $media ][ $selector ][ $property ] ) ) {
			if ( ( $this->is_important( $this->css[ $media ][ $selector ][ $property ] ) && $this->is_important( $new_val ) ) || ! $this->is_important( $this->css[ $media ][ $selector ][ $property ] ) ) {
				$this->css[ $media ][ $selector ][ $property ] = trim( $new_val );
			}
		} else {
			$this->css[ $media ][ $selector ][ $property ] = trim( $new_val );
		}
	}

	/**
	 * Checks if a current media section is the continuation of the last one.
	 * If not increase the name of the media section to avoid a merging.
	 *
	 * @since 1.10.0
	 *
	 * @param int|string $media Media.
	 * @return int|string [return value]
	 */
	protected function css_check_last_media_section_or_inc( $media ) /* : int|string */ {
		// Are we starting?
		if ( empty( $this->css ) || ! is_array( $this->css ) ) {
			return $media;
		}
		// If the last @media is the same as this, keep it.
		$at = array_key_last( $this->css );
		if ( $at === $media ) {
			return $media;
		}
		// Otherwise increase the section in the array.
		while ( isset( $this->css[ $media ] ) ) {
			if ( is_numeric( $media ) ) {
				++$media;
			} else {
				$media .= ' ';
			}
		}
		return $media;
	}

	/**
	 * Starts a new media section.
	 *
	 * Check if the media is not already known, else rename it with extra spaces to avoid merging.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $current_media Media.
	 * @param string     $new_media     Media.
	 * @param bool       $at_root       Optional. Default false.
	 * @return int|string [return value]
	 */
	protected function css_new_media_section( $current_media, string $new_media, bool $at_root = false ) /* : int|string */ {
		if ( $this->get_cfg( 'preserve_css' ) ) {
			return $new_media;
		}
		// If we already are in a media and CSS level is 3, manage nested medias.
		if ( $current_media
			&& ! $at_root
			// Numeric $current_media means self::DEFAULT_AT or inc.
			&& ! is_numeric( $current_media )
			&& str_starts_with( $this->get_cfg( 'css_level' ), 'CSS3' ) ) {
				$new_media = rtrim( $current_media ) . '{' . rtrim( $new_media );
		}
		return $this->css_check_last_media_section_or_inc( $new_media );
	}

	/**
	 * Closes a media section.
	 *
	 * Finds the parent media we were in before or the root.
	 *
	 * @since 1.10.0
	 *
	 * @param string $current_media Current Media.
	 * @return string [return value]
	 */
	protected function css_close_media_section( string $current_media ): string {
		if ( $this->get_cfg( 'preserve_css' ) ) {
			return '';
		}
		if ( str_contains( $current_media, '{' ) ) {
			$current_media = explode( '{', $current_media );
			array_pop( $current_media );
			$current_media = implode( '{', $current_media );
			return $current_media;
		}

		return '';
	}

	/**
	 * Starts a new selector.
	 *
	 * If already referenced in this media section, rename it with extra space to avoid merging,
	 * except if merging is required, or last selector is the same (merge siblings).
	 * Never merge @font-face.
	 *
	 * @since 1.0.0
	 *
	 * @param string $media    Media.
	 * @param string $selector Selector.
	 * @return string [return value]
	 */
	protected function css_new_selector( string $media, string $selector ): string {
		if ( $this->get_cfg( 'preserve_css' ) ) {
			return $selector;
		}
		$selector = trim( $selector );
		if ( ! str_starts_with( $selector, '@font-face' ) ) {
			if ( false !== $this->settings['merge_selectors'] ) {
				return $selector;
			}

			if ( empty( $this->css ) || ! isset( $this->css[ $media ] ) || ! $this->css[ $media ] ) {
				return $selector;
			}

			// If last is the same, keep it.
			$sel = array_key_last( $this->css[ $media ] );
			if ( $sel === $selector ) {
				return $selector;
			}
		}

		while ( isset( $this->css[ $media ][ $selector ] ) ) {
			$selector .= ' ';
		}
		return $selector;
	}

	/**
	 * Starts a new property.
	 *
	 * If already referenced in this selector, rename it with extra space to avoid override.
	 *
	 * @since 1.0.0
	 *
	 * @param string $media    Media.
	 * @param string $selector Selector.
	 * @param string $property Property.
	 * @return string [return value]
	 */
	protected function css_new_property( string $media, string $selector, string $property ): string {
		if ( $this->get_cfg( 'preserve_css' ) ) {
			return $property;
		}
		if ( empty( $this->css ) || ! isset( $this->css[ $media ][ $selector ] ) || ! $this->css[ $media ][ $selector ] ) {
			return $property;
		}

		while ( isset( $this->css[ $media ][ $selector ][ $property ] ) ) {
			$property .= ' ';
		}

		return $property;
	}

	/**
	 * Adds CSS to an existing media/selector.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $media    Media.
	 * @param string                $selector Selector.
	 * @param array<string, string> $css_add  Additional CSS.
	 */
	public function merge_css_blocks( string $media, string $selector, array $css_add ): void {
		foreach ( $css_add as $property => $value ) {
			$this->css_add_property( $media, $selector, $property, $value );
		}
	}

	/**
	 * Checks if $value is !important.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Value.
	 * @return bool Whether the value has the !important keyword.
	 */
	public function is_important( string &$value ): bool {
		return (
			str_contains( $value, '!' ) // Quick test.
			&& ! strcasecmp( substr( str_replace( $this->data['csstidy']['whitespace'], '', $value ), -10, 10 ), '!important' ) );
	}

	/**
	 * Returns a value without !important.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Value.
	 * @return string Value without the !important;
	 */
	public function gvw_important( string $value ): string {
		if ( $this->is_important( $value ) ) {
			$value = trim( $value );
			$value = substr( $value, 0, -9 );
			$value = trim( $value );
			$value = substr( $value, 0, -1 );
			$value = trim( $value );
			return $value;
		}
		return $value;
	}

	/**
	 * Checks if the next word in a string from pos is a CSS property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $istring String.
	 * @param int    $pos     Position.
	 * @return bool [return value]
	 */
	protected function property_is_next( string $istring, int $pos ): bool {
		$all_properties = &$this->data['csstidy']['all_properties'];
		$istring = substr( $istring, $pos, strlen( $istring ) - $pos );
		$pos = strpos( $istring, ':' );
		if ( false === $pos ) {
			return false;
		}
		$istring = strtolower( trim( substr( $istring, 0, $pos ) ) );
		if ( isset( $all_properties[ $istring ] ) ) {
			$this->log( 'Added semicolon to the end of declaration', 'Warning' );
			return true;
		}
		return false;
	}

	/**
	 * Checks if a property is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property Property.
	 * @return bool Whether the property is valid.
	 */
	public function property_is_valid( string $property ): bool {
		$property = strtolower( $property );
		if ( str_starts_with( $property, '--' ) ) {
			$property = '--custom'; // Replace custom properties with a temporary placeholder that is marked as valid in the list of properties.
		} elseif ( in_array( trim( $property ), $this->data['csstidy']['multiple_properties'], true ) ) {
			$property = trim( $property );
		}
		$all_properties = &$this->data['csstidy']['all_properties'];
		return isset( $all_properties[ $property ] ) && str_contains( $all_properties[ $property ], strtoupper( $this->get_cfg( 'css_level' ) ) );
	}

	/**
	 * Accepts a list of strings (e.g. the argument to format() in a @font-face src property)
	 * and returns a list of the strings. Converts things like:
	 * format(abc) => format("abc")
	 * format(abc def) => format("abc","def")
	 * format(abc "def") => format("abc","def")
	 * format(abc, def, ghi) => format("abc","def","ghi")
	 * format("abc",'def') => format("abc","def")
	 * format("abc, def, ghi") => format("abc, def, ghi")
	 *
	 * @since 1.0.0
	 *
	 * @param string $value [description].
	 * @return string[] [description]
	 */
	public function parse_string_list( string $value ): array {
		$value = trim( $value );

		// Case: empty.
		if ( ! $value ) {
			return array();
		}

		$a_strings = array();

		$in_str = false;
		$current_string = '';

		for ( $i = 0, $_len = strlen( $value ); $i < $_len; $i++ ) {
			if ( ( ',' === $value[ $i ] || ' ' === $value[ $i ] ) && true === $in_str ) {
				$in_str = false;
				$a_strings[] = $current_string;
				$current_string = '';
			} elseif ( '"' === $value[ $i ] || "'" === $value[ $i ] ) {
				if ( $in_str === $value[ $i ] ) {
					$a_strings[] = $current_string;
					$in_str = false;
					$current_string = '';
					continue;
				} elseif ( ! $in_str ) {
					$in_str = $value[ $i ];
				}
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
				if ( $in_str ) {
					$current_string .= $value[ $i ];
				} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
					if ( ! preg_match( '/[\s,]/', $value[ $i ] ) ) {
						$in_str = true;
						$current_string = $value[ $i ];
					}
				}
			}
		}

		if ( $current_string ) {
			$a_strings[] = $current_string;
		}

		return $a_strings;
	}

} // class TablePress_CSSTidy
