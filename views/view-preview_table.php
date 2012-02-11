<?php
/**
 * Table Preview View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Table Preview View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Preview_Table_View extends TablePress_View {

	/**
	 * Initialize the View class
	 *
	 * Intentionally left empty, to void code from parent::__construct()
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// intentionally left empty, to void code from parent::__construct()
	}

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		$this->action = $action;
		$this->data = $data;
	}

	/**
	 * Render the current view
	 *
	 * @since 1.0.0
	 */
	public function render() {
		_wp_admin_html_begin();
?>
<title><?php printf( __( '%s &lsaquo; TablePress', 'tablepress' ), __( 'Preview', 'tablepress' ) ); ?></title>
<style type="text/css">
body {
	margin: 10px;
}
</style>
<?php echo $this->data['head_html']; ?>
</head>
<body>
<div id="tablepress-page">
<p>
<?php _e( 'This is a preview of your table.', 'tablepress' ); ?>
</p>
<?php echo $this->data['body_html']; ?>
</div>
</body>
</html>
<?php
	}

} // class TablePress_Preview_Table_View