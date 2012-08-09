<?php
/**
 * About TablePress View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
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
	 * Number of screen columns for post boxes on this screen
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $screen_columns = 2;

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		$this->add_meta_box( 'plugin-purpose', __( 'Plugin Purpose', 'tablepress' ), array( &$this, 'postbox_plugin_purpose' ), 'normal' );
		$this->add_meta_box( 'usage', __( 'Usage', 'tablepress' ), array( &$this, 'postbox_usage' ), 'normal' );
		$this->add_meta_box( 'more-information', __( 'More Information and Documentation', 'tablepress' ), array( &$this, 'postbox_more_information' ), 'normal' );
		$this->add_meta_box( 'author-license', __( 'Author and License', 'tablepress' ), array( &$this, 'postbox_author_license' ), 'side' );
		$this->add_meta_box( 'help-support', __( 'Help and Support', 'tablepress' ), array( &$this, 'postbox_help_support' ), 'side' );
		$this->add_meta_box( 'debug-version-information', __( 'Debug and Version Information', 'tablepress' ), array( &$this, 'postbox_debug_version_information' ), 'side' );
		$this->add_meta_box( 'credits-thanks', __( 'Credits and Thanks', 'tablepress' ), array( &$this, 'postbox_credits_thanks' ), 'side' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_plugin_purpose( $data, $box ) {
		?>
		<p><?php _e( 'TablePress allows you to create and manage tables in the admin-area of WordPress.', 'tablepress' ); ?> <?php _e( 'Those tables may contain strings, numbers and even HTML (e.g. to include images or links).', 'tablepress' ); ?> <?php _e( 'You can then show the tables in your posts, on your pages or in text-widgets by using a shortcode.', 'tablepress' ); ?> <?php _e( 'If you want to show your tables anywhere else in your theme, you can use a template tag function.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_usage( $data, $box ) {
		?>
		<p><?php _e( 'At first you should add or import a table.', 'tablepress' ); ?> <?php _e( 'This means that you either let the plugin create an empty table for you or that you load an existing table from either a CSV, HTML, or JSON file.', 'tablepress' ); ?></p>
		<p><?php _e( 'Then you can edit your data or change the structure of your table (e.g. by inserting or deleting rows or columns, swaping rows or columns or sorting them) and select specific table options like alternating row colors or whether to print the name or description, if you want.', 'tablepress' ); ?> <?php _e( 'To easily add a link or an image to a cell, use the provided buttons. Those will ask you for the URL and a title. Then you can click into a cell and the corresponding HTML will be added to it for you.', 'tablepress' ); ?></p>
		<p><?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode %s and paste it into the corresponding place in the editor.', 'tablepress' ), '<input type="text" class="table-shortcode table-shortcode-inline" value="[' . TablePress::$shortcode . ' id=&lt;ID&gt; /]" readonly="readonly" />' ); ?> <?php printf( __( 'You can also select the desired table from a list (after clicking the button &quot;%s&quot; in the editor toolbar) and the corresponding Shortcode will be added for you.', 'tablepress' ), __( 'Table', 'tablepress' ) ); ?></p>
		<p><?php _e( 'Tables can be styled by changing and adding CSS commands.', 'tablepress' ); ?> <?php _e( 'The plugin ships with default CSS Stylesheets, which can be customized with own code or replaced with other Stylesheets.', 'tablepress' ); ?> <?php _e( 'For this, each table is given certain CSS classes that can be used as CSS selectors.', 'tablepress' ); ?> <?php printf ( __( 'Please see the <a href="%s">documentation</a> for a list of these selectors and for styling examples.', 'tablepress' ), 'http://tablepress.org/documentation/' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_more_information( $data, $box ) {
		?>
		<p><?php printf( __( 'More information about TablePress can be found on the <a href="%s">plugin\'s website</a> or on its page in the <a href="%s">WordPress Plugin Directory</a>.', 'tablepress' ), 'http://tablepress.org/website/', 'http://wordpress.org/extend/plugins/tablepress/' ); ?> <?php printf( __( 'For technical information, see the <a href="%s">documentation</a>.', 'tablepress' ), 'http://tablepress.org/documentation/' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_author_license( $data, $box ) {
		?>
		<p><?php printf( __( 'This plugin was written by <a href="%s">Tobias Bäthge</a>.', 'tablepress' ), 'http://tobias.baethge.com/' ); ?> <?php _e( 'It is licensed as Free Software under GPL 2.', 'tablepress' ); ?><br /><?php printf( __( 'If you like the plugin, <a href="%s"><strong>a donation</strong></a> is recommended.', 'tablepress' ), 'http://tablepress.org/donate/' ); ?> <?php printf( __( 'Please rate the plugin in the <a href="%s">WordPress Plugin Directory</a>.', 'tablepress' ), 'http://wordpress.org/extend/plugins/tablepress/' ); ?><br /><?php _e( 'Donations and good ratings encourage me to further develop the plugin and to provide countless hours of support. Any amount is appreciated! Thanks!', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_help_support( $data, $box ) {
		?>
		<p><?php printf( __( '<a href="%s">Support</a> is provided through the <a href="%s">WordPress Support Forums</a>.', 'tablepress' ), 'http://tablepress.org/support/', 'http://wordpress.org/support/plugin/tablepress' ); ?> <?php printf( __( 'Before asking for support, please carefully read the <a href="%s">Frequently Asked Questions</a> where you will find answers to the most common questions, and search through the forums.', 'tablepress' ), 'http://tablepress.org/faq/' ); ?></p><p><?php printf( __( 'If you do not find an answer there, please <a href="%s">open a new thread</a> in the WordPress Support Forums.', 'tablepress' ), 'http://wordpress.org/support/plugin/tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_debug_version_information( $data, $box ) {
			// @TODO: Add more relevant things? (like ZIP support?)
		?>
		<p>
			<?php _e( 'You are using the following versions of the software.', 'tablepress' ); ?> <strong><?php _e( 'Please provide this information in bug reports and support requests.', 'tablepress' ); ?></strong><br />
			<br />&middot; Website: <?php echo site_url(); ?>
			<br />&middot; TablePress (DB): <?php echo TablePress::db_version; ?>
			<br />&middot; TablePress (Script): <?php echo TablePress::version; ?>
			<br />&middot; <?php _e( 'Plugin installed', 'tablepress' ); ?>: <?php echo date( 'Y/m/d H:i:s', $data['first_activation'] ); ?>
			<br />&middot; WordPress: <?php echo $GLOBALS['wp_version']; ?>
			<br />&middot; PHP: <?php echo phpversion(); ?>
			<br />&middot; mySQL (Server): <?php echo mysql_get_server_info(); ?>
			<br />&middot; mySQL (Client): <?php echo mysql_get_client_info(); ?>
		</p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_credits_thanks( $data, $box ) {
		?>
		<p>
			<?php _e( 'Thanks go to', 'tablepress' ); ?><br />
			<?php _e( 'Allan Jardine for the <a href="http://www.datatables.net/">DataTables jQuery plugin</a>,', 'tablepress' ); ?><br />
			<?php _e( 'the submitters of translations:', 'tablepress' ); ?>
			<?php
				foreach ( $data['plugin_languages'] as $lang_abbr => $language ) {
					$link = sprintf( '<a href="%1$s">%2$s</a>', $language['translator_url'], $language['translator_name'] );
					echo "<br />&middot; " . sprintf( __( '%s (thanks to %s)', 'tablepress' ), $language['name'], $link ) . "\n";
				}
			?>
			<br /><?php _e( 'and to all donors, contributors, supporters, reviewers and users of the plugin!', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the About TablePress screen';
	}

} // class TablePress_About_View