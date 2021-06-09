<?php
/**
 * About TablePress View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * About TablePress View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_About_View extends TablePress_View {

	/**
	 * Number of screen columns for post boxes on this screen.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $screen_columns = 2;

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view.
	 * @param array  $data   Data for this view.
	 */
	public function setup( $action, array $data ) {
		parent::setup( $action, $data );

		$this->add_meta_box( 'plugin-purpose', __( 'Plugin Purpose', 'tablepress' ), array( $this, 'postbox_plugin_purpose' ), 'normal' );
		$this->add_meta_box( 'usage', __( 'Usage', 'tablepress' ), array( $this, 'postbox_usage' ), 'normal' );
		$this->add_meta_box( 'more-information', __( 'More Information and Documentation', 'tablepress' ), array( $this, 'postbox_more_information' ), 'normal' );
		$this->add_meta_box( 'help-support', __( 'Help and Support', 'tablepress' ), array( $this, 'postbox_help_support' ), 'normal' );
		$this->add_meta_box( 'author-license', __( 'Author and License', 'tablepress' ), array( $this, 'postbox_author_license' ), 'side' );
		$this->add_meta_box( 'credits-thanks', __( 'Credits and Thanks', 'tablepress' ), array( $this, 'postbox_credits_thanks' ), 'side' );
		$this->add_meta_box( 'debug-version-information', __( 'Debug and Version Information', 'tablepress' ), array( $this, 'postbox_debug_version_information' ), 'side' );
	}

	/**
	 * Print the content of the "Plugin Purpose" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_plugin_purpose( array $data, array $box ) {
		?>
	<p>
		<?php _e( 'TablePress allows you to create and manage tables in the admin area of WordPress.', 'tablepress' ); ?>
		<?php _e( 'Tables may contain text, numbers, and even HTML (e.g. to include images or links).', 'tablepress' ); ?>
		<?php _e( 'You can then show the tables in your posts, on your pages, or in text widgets by using a Shortcode.', 'tablepress' ); ?>
		<?php _e( 'If you want to show your tables anywhere else in your theme, you can use a Template Tag function.', 'tablepress' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Usage" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_usage( array $data, array $box ) {
		?>
	<p>
		<?php _e( 'At first, you should add or import a table.', 'tablepress' ); ?>
		<?php _e( 'This means that you either let the plugin create an empty table for you or that you load an existing table from either a CSV, HTML, JSON, XLS, or XLSX file.', 'tablepress' ); ?>
	</p>
	<p>
		<?php _e( 'Then, you can edit your data or change the structure of your table (e.g. by inserting, deleting, moving, or swapping rows or columns or sorting them) and select specific table features like alternating row colors or whether to print the name or description, if you want.', 'tablepress' ); ?>
		<?php _e( 'To easily add a link or an image to a cell, use the provided buttons.', 'tablepress' ); ?>
		<?php _e( 'Those will ask you for the necessary information and corresponding HTML code will be added to the cell automatically.', 'tablepress' ); ?>
	</p>
	<p>
		<label class="screen-reader-text" for="table-shortcode-inline"><?php esc_html_e( 'Shortcode text for editor', 'tablepress' ); ?></label>
		<?php printf( __( 'To insert a table into a post or page, copy its Shortcode %s and paste it into a &#8220;Shortcode&#8221; block at the desired place in the block editor.', 'tablepress' ), '<label class="screen-reader-text" for="table-shortcode-inline">' . esc_html__( 'Shortcode text for editor', 'tablepress' ) . '</label>' . '<input type="text" class="table-shortcode table-shortcode-inline" id="table-shortcode-inline" value="' . esc_attr( '[' . TablePress::$shortcode . ' id=<ID> /]' ) . '" readonly="readonly" />' ); ?>
		<?php _e( 'Each table has a unique ID that needs to be adjusted in that Shortcode.', 'tablepress' ); ?>
	</p>
	<p>
		<?php _e( 'Tables can be styled by changing and adding CSS commands.', 'tablepress' ); ?>
		<?php _e( 'The plugin ships with default CSS stylesheets, which can be customized with own code or replaced with other stylesheets.', 'tablepress' ); ?>
		<?php _e( 'For this, each table is given certain CSS classes that can be used as CSS selectors.', 'tablepress' ); ?>
		<?php printf( __( 'Please see the <a href="%s">Documentation</a> for a list of these selectors and for styling examples.', 'tablepress' ), 'https://tablepress.org/documentation/' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "More Information and Documentation" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_more_information( array $data, array $box ) {
		?>
	<p>
		<?php printf( __( 'More information about TablePress can be found on the <a href="%1$s">plugin website</a> or on its page in the <a href="%2$s">WordPress Plugin Directory</a>.', 'tablepress' ), 'https://tablepress.org/', 'https://wordpress.org/plugins/tablepress/' ); ?>
		<?php printf( __( 'For technical information, please see the <a href="%s">Documentation</a>.', 'tablepress' ), 'https://tablepress.org/documentation/' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Author and License" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_author_license( array $data, array $box ) {
		?>
	<p>
		<?php printf( __( 'This plugin was written and developed by <a href="%s">Tobias Bäthge</a>.', 'tablepress' ), 'https://tobias.baethge.com/' ); ?>
		<?php _e( 'It is licensed as Free Software under GNU General Public License 2 (GPL 2).', 'tablepress' ); ?>
	<br />
		<?php printf( __( 'If you like the plugin, <a href="%s"><strong>giving a donation</strong></a> is recommended.', 'tablepress' ), 'https://tablepress.org/donate/' ); ?>
		<?php printf( __( 'Please rate and review the plugin in the <a href="%s">WordPress Plugin Directory</a>.', 'tablepress' ), 'https://wordpress.org/support/view/plugin-reviews/tablepress' ); ?>
	<br />
		<?php _e( 'Donations and good ratings encourage me to further develop the plugin and to provide countless hours of support. Any amount is appreciated! Thanks!', 'tablepress' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Help and Support" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_help_support( array $data, array $box ) {
		?>
	<p>
		<?php printf( __( '<a href="%1$s">Support</a> is provided through the <a href="%2$s">WordPress Support Forums</a>.', 'tablepress' ), 'https://tablepress.org/support/', 'https://wordpress.org/support/plugin/tablepress' ); ?>
		<?php printf( __( 'Before asking for support, please carefully read the <a href="%s">Frequently Asked Questions</a>, where you will find answers to the most common questions, and search through the forums.', 'tablepress' ), 'https://tablepress.org/faq/' ); ?>
	</p>
	<p>
		<?php printf( __( 'If you do not find an answer there, please <a href="%s">open a new thread</a> in the WordPress Support Forums.', 'tablepress' ), 'https://wordpress.org/support/plugin/tablepress' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Debug and Version Information" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_debug_version_information( array $data, array $box ) {
		$mysqli = ( isset( $GLOBALS['wpdb']->use_mysqli, $GLOBALS['wpdb']->dbh ) && $GLOBALS['wpdb']->use_mysqli );
		?>
		<p>
			<strong><?php _e( 'Please provide this information in bug reports and support requests.', 'tablepress' ); ?></strong>
		</p>
		<p class="ltr">
			&middot; Website: <?php echo site_url(); ?>
			<br />&middot; TablePress: <?php echo TablePress::version; ?>
			<br />&middot; TablePress (DB): <?php echo TablePress::db_version; ?>
			<br />&middot; TablePress table scheme: <?php echo TablePress::table_scheme_version; ?>
			<br />&middot; Plugin installed: <?php echo wp_date( 'Y/m/d H:i:s', $data['first_activation'] ); ?>
			<br />&middot; WordPress: <?php echo $GLOBALS['wp_version']; ?>
			<br />&middot; Multisite: <?php echo is_multisite() ? 'yes' : 'no'; ?>
			<br />&middot; PHP: <?php echo phpversion(); ?>
			<br />&middot; mysqli Extension: <?php echo $mysqli ? 'true' : 'false'; ?>
			<br />&middot; mySQL (Server): <?php echo $mysqli ? mysqli_get_server_info( $GLOBALS['wpdb']->dbh ) : '<em>no mysqli</em>'; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info ?>
			<br />&middot; mySQL (Client): <?php echo $mysqli ? mysqli_get_client_info( $GLOBALS['wpdb']->dbh ) : '<em>no mysqli</em>'; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info ?>
			<br />&middot; ZIP support: <?php echo $data['zip_support_available'] ? 'yes' : 'no'; ?>
			<br />&middot; UTF-8 conversion: <?php echo ( function_exists( 'mb_detect_encoding' ) && function_exists( 'iconv' ) ) ? 'yes' : 'no'; ?>
			<br />&middot; WP Memory Limit: <?php echo WP_MEMORY_LIMIT; ?>
			<br />&middot; Server Memory Limit: <?php echo (int) @ini_get( 'memory_limit' ) . 'M'; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged ?>
			<br />&middot; WP_DEBUG: <?php echo WP_DEBUG ? 'true' : 'false'; ?>
			<br />&middot; WP_POST_REVISIONS: <?php echo is_bool( WP_POST_REVISIONS ) ? ( WP_POST_REVISIONS ? 'true' : 'false' ) : WP_POST_REVISIONS; ?>
		</p>
		<?php
	}

	/**
	 * Print the content of the "Credits and Thanks" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_credits_thanks( array $data, array $box ) {
		?>
		<p>
			<?php _e( 'Special Thanks go to:', 'tablepress' ); ?>
			<br />&middot; <?php printf( __( 'Allan Jardine for <a href="%s">DataTables</a>,', 'tablepress' ), 'https://www.datatables.net/' ); ?>
			<br />&middot; <?php printf( __( 'the translators in the <a href="%s">Plugin Directory</a>,', 'tablepress' ), 'https://translate.wordpress.org/projects/wp-plugins/tablepress/' ); ?>
			<br />&middot; <?php _e( 'all donors, contributors, supporters, reviewers, and users of the plugin!', 'tablepress' ); ?>
		</p>
		<hr />
		<p>
			<a href="https://wpactivitylog.com/?utm_source=tablepress&utm_medium=referral&utm_campaign=WSAL" target="_blank" rel="noopener"><img src="<?php echo plugins_url( 'admin/img/wsal-logo.png', TABLEPRESS__FILE__ ); ?>" alt="<?php printf( esc_attr_x( 'This release of TablePress is supported by %s, the most comprehensive WordPress activity logs plugin.', 'WP Activity Log', 'tablepress' ), 'WP Activity Log' ); ?>" style="width:100%;height:auto;max-width:320px;display:block;margin:0 auto" /></a>
			<?php printf( _x( 'This release of TablePress is supported by %s, the most comprehensive WordPress activity logs plugin.', 'WP Activity Log', 'tablepress' ), '<a href="https://wpactivitylog.com/?utm_source=tablepress&utm_medium=referral&utm_campaign=WSAL" target="_blank" rel="noopener">WP Activity Log</a>' ); ?>
		</p>
		<?php
	}

} // class TablePress_About_View
