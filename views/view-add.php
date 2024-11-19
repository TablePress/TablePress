<?php
/**
 * Add Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Add Table View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Add_View extends TablePress_View {

	/**
	 * Sets up the view with data and does things that are specific for this view.
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

		$this->add_text_box( 'no-javascript', array( $this, 'textbox_no_javascript' ), 'header' );

		$this->process_action_messages( array(
			'error_add' => __( 'Error: The table could not be added.', 'tablepress' ),
		) );

		$this->admin_page->enqueue_script( 'add' );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'add-table', array( $this, 'textbox_add_table' ), 'normal' );
	}

	/**
	 * Prints the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		?>
		<p>
			<?php _e( 'To add a new table, enter its name, a description (optional), and the number of rows and columns into the form below.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'You can always change the name, description, and size of your table later.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Prints the content of the "Add New Table" text box.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_add_table( array $data, array $box ): void {
		echo '<div id="tablepress-add-screen"></div>';
	}

} // class TablePress_Add_View
