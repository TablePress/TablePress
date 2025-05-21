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
 *
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
	 */
	protected int $screen_columns = 2;

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	#[\Override]
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		parent::setup( $action, $data );

		$this->add_text_box( 'spacer', array( $this, 'textbox_spacer' ), 'normal' );
		$this->add_text_box( 'spacer', array( $this, 'textbox_spacer' ), 'side' );
		$this->add_meta_box( 'plugin-purpose', __( 'Plugin Purpose', 'tablepress' ), array( $this, 'postbox_plugin_purpose' ), 'normal' );
		$this->add_meta_box( 'usage', __( 'Usage', 'tablepress' ), array( $this, 'postbox_usage' ), 'normal' );
		$this->add_meta_box( 'more-information', __( 'More Information and Documentation', 'tablepress' ), array( $this, 'postbox_more_information' ), 'normal' );
		$this->add_meta_box( 'help-support', __( 'Help and Support', 'tablepress' ), array( $this, 'postbox_help_support' ), 'normal' );
		$this->add_meta_box( 'author-license', __( 'Author and License', 'tablepress' ), array( $this, 'postbox_author_license' ), 'side' );
		$this->add_meta_box( 'credits-thanks', __( 'Credits and Thanks', 'tablepress' ), array( $this, 'postbox_credits_thanks' ), 'side' );
		$this->add_meta_box( 'debug-version-information', __( 'Debug and Version Information', 'tablepress' ), array( $this, 'postbox_debug_version_information' ), 'side' );
	}

	/**
	 * Prints the screen head spacer.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_spacer( array $data, array $box ): void {
		echo '<p></p>';
	}

	/**
	 * Print the content of the "Plugin Purpose" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_plugin_purpose( array $data, array $box ): void {
		?>
	<p>
		<?php _e( 'TablePress allows you to create and manage tables in the admin area of WordPress.', 'tablepress' ); ?>
		<?php _e( 'Tables may contain text, numbers, formulas, and even HTML code (e.g. to include images or links).', 'tablepress' ); ?>
		<?php _e( 'You can embed the tables into posts, pages, or other site areas using a block in the block editor, a widget in the Elementor page builder, or a Shortcode.', 'tablepress' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Usage" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_usage( array $data, array $box ): void {
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
		<?php
		// Show the instructions string depending on whether the Block Editor is used on the site or not.
		if ( 'block' === $data['site_used_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} elseif ( 'elementor' === $data['site_used_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” widget in the Elementor editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} else {
			_e( 'To insert a table into a post or page, paste its Shortcode at the desired place in the editor.', 'tablepress' );
			echo ' ';
			_e( 'Each table has a unique ID that needs to be adjusted in that Shortcode.', 'tablepress' );
		}
		?>
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
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_more_information( array $data, array $box ): void {
		?>
	<p>
		<?php printf( __( 'More information about TablePress can be found on the <a href="%1$s">plugin website</a> or on its page in the <a href="%2$s">WordPress Plugin Directory</a>.', 'tablepress' ), 'https://tablepress.org/', 'https://wordpress.org/plugins/tablepress/' ); ?>
		<?php printf( __( 'For technical information, please see the <a href="%s">Documentation</a>.', 'tablepress' ), 'https://tablepress.org/documentation/' ); ?>
		<?php printf( __( 'Common questions are answered in the <a href="%s">FAQ</a>.', 'tablepress' ), 'https://tablepress.org/faq/' ); ?>
	</p>
		<?php
	}

	/**
	 * Print the content of the "Author and License" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_author_license( array $data, array $box ): void {
		?>
	<p>
		<?php printf( __( 'This plugin was written and developed by <a href="%s">Tobias Bäthge</a>.', 'tablepress' ), 'https://tobias.baethge.com/' ); ?>
		<?php _e( 'It is licensed as Free Software under GNU General Public License 2 (GPL 2).', 'tablepress' ); ?>
	</p>
	<p>
		<?php printf( __( 'Please rate and review the plugin in the <a href="%s">WordPress Plugin Directory</a>.', 'tablepress' ), 'https://wordpress.org/support/view/plugin-reviews/tablepress' ); ?>
	</p>
		<?php
		if ( tb_tp_fs()->is_free_plan() ) {
			echo '<p><strong>' . sprintf( __( 'I would like to invite you to check out the <a href="%s">Premium versions of TablePress</a>.', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=about-screen' ) . ' '
			. __( 'The available Pro and Max plans offer user support and many exciting and helpful features for your tables.', 'tablepress' ) . '</strong></p>';
		}
		?>
		<?php
	}

	/**
	 * Print the content of the "Help and Support" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_help_support( array $data, array $box ): void {
		if ( tb_tp_fs()->is_free_plan() ) {
			?>
		<h4><?php _e( 'Premium Support', 'tablepress' ); ?></h4>
		<p>
			<?php _e( 'Users with a valid TablePress Premium license plan are eligible for Priority Email Support, directly from the plugin developer!', 'tablepress' ); ?>
			<strong><?php printf( __( '<a href="%s">Find out more!</a>', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=about-screen' ); ?></strong>
		</p>
		<h4><?php _e( 'Community Support for users of the Free version', 'tablepress' ); ?></h4>
		<p>
			<?php printf( __( '<a href="%1$s">Support</a> is provided through the <a href="%2$s">WordPress Support Forums</a>.', 'tablepress' ), 'https://tablepress.org/support/', 'https://wordpress.org/support/plugin/tablepress' ); ?>
			<?php printf( __( 'Before asking for support, please carefully read the <a href="%s">Frequently Asked Questions</a>, where you will find answers to the most common questions, and search through the forums.', 'tablepress' ), 'https://tablepress.org/faq/' ); ?>
			<?php printf( __( 'If you do not find an answer there, please <a href="%s">open a new thread</a> in the WordPress Support Forums.', 'tablepress' ), 'https://wordpress.org/support/plugin/tablepress' ); ?>
		</p>
			<?php
		}
	}

	/**
	 * Print the content of the "Debug and Version Information" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_debug_version_information( array $data, array $box ): void {
		?>
		<p>
			<strong><?php _e( 'Please provide this information in bug reports and support requests.', 'tablepress' ); ?></strong>
		</p>
		<ul>
			<li>Website: <?php echo site_url(); ?></li>
			<li>Block Theme: <?php echo wp_is_block_theme() ? 'yes' : 'no'; ?></li>
			<li>TablePress: <?php echo TablePress::version; ?></li>
			<li>TablePress (DB): <?php echo TablePress::db_version; ?></li>
			<li>TablePress table scheme: <?php echo TablePress::table_scheme_version; ?></li>
			<li>Plan: Free</li>
			<li>Plugin installed: <?php echo wp_date( 'Y/m/d H:i:s', $data['first_activation'] ); ?></li>
			<li>WordPress: <?php echo $GLOBALS['wp_version']; ?></li>
			<li>Multisite: <?php echo is_multisite() ? 'yes' : 'no'; ?></li>
			<li>PHP: <?php echo PHP_VERSION; ?></li>
			<li>mySQL (Server): <?php echo ( isset( $GLOBALS['wpdb']->dbh ) && function_exists( 'mysqli_get_server_info' ) ) ? mysqli_get_server_info( $GLOBALS['wpdb']->dbh ) : 'no mySQL server'; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info ?></li>
			<li>mySQL (Client): <?php echo function_exists( 'mysqli_get_client_info' ) ? mysqli_get_client_info() : 'no mySQL client'; // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info ?></li>
			<li>mbstring: <?php echo extension_loaded( 'mbstring' ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>ZipArchive: <?php echo class_exists( 'ZipArchive', false ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>DOMDocument: <?php echo class_exists( 'DOMDocument', false ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>simplexml_load_string: <?php echo function_exists( 'simplexml_load_string' ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>libxml_disable_entity_loader: <?php echo function_exists( 'libxml_disable_entity_loader' ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>UTF-8 conversion: <?php echo ( function_exists( 'mb_detect_encoding' ) && function_exists( 'iconv' ) ) ? 'yes' : '<span style="color:#800000;font-weight:bold;">no</span>'; ?></li>
			<li>WP Memory Limit: <?php echo WP_MEMORY_LIMIT; ?></li>
			<li>Server Memory Limit: <?php echo esc_html( @ini_get( 'memory_limit' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged ?></li>
			<li>WP_DEBUG: <?php echo WP_DEBUG ? 'true' : 'false'; ?></li>
			<li>WP_POST_REVISIONS: <?php echo is_bool( WP_POST_REVISIONS ) ? ( WP_POST_REVISIONS ? 'true' : 'false' ) : WP_POST_REVISIONS; // @phpstan-ignore constant.notFound, phpstanWP.wpConstant.fetch, phpstanWP.wpConstant.fetch, phpstanWP.wpConstant.fetch (The constant is printed for debugging purposes.) ?></li>
		</ul>
		<?php
	}

	/**
	 * Print the content of the "Credits and Thanks" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_credits_thanks( array $data, array $box ): void {
		?>
		<p>
			<?php _e( 'Special Thanks go to:', 'tablepress' ); ?>
		</p>
		<ul>
			<li><?php printf( __( 'Allan Jardine for <a href="%s">DataTables</a>,', 'tablepress' ), 'https://datatables.net/' ); ?></li>
			<li><?php printf( __( 'the translators in the <a href="%s">Plugin Directory</a>,', 'tablepress' ), 'https://translate.wordpress.org/projects/wp-plugins/tablepress/' ); ?></li>
			<li><?php _e( 'all customers, donors, contributors, supporters, reviewers, and users of the plugin!', 'tablepress' ); ?></li>
		</ul>
		<?php
	}

} // class TablePress_About_View
