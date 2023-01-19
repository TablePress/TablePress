/**
 * JavaScript code for the "Table" button in the TinyMCE editor toolbar.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* globals tinymce, tablepress_editor_button */

/* jshint strict: global */
'use strict'; // Necessary as this file does not use "import".

/**
 * Register a button for the TinyMCE (aka Visual Editor) toolbar
 *
 * @since 1.0.0
 */
if ( 'undefined' !== typeof tinymce ) {
	tinymce.create( 'tinymce.plugins.TablePressPlugin', {
		init( ed, url ) {
			ed.addCommand( 'TablePress_insert_table', window.tablepress_open_shortcode_thickbox );

			ed.addButton( 'tablepress_insert_table', {
				title: tablepress_editor_button.title,
				cmd: 'TablePress_insert_table',
				image: url.slice( 0, url.length - 8 ) + 'img/tablepress-editor-button.png'
			} );
		}
	} );
	tinymce.PluginManager.add( 'tablepress_tinymce', tinymce.plugins.TablePressPlugin );
}
