=== TablePress ===
Contributors: TobiasBg
Donate link: https://tablepress.org/donate/
Tags: table,data,html,csv,excel
Requires at least: 4.0
Tested up to: 4.0
Stable tag: 1.5.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful and feature-rich tables into your posts and pages, without having to write code.

== Description ==

TablePress allows you to easily create and manage beautiful tables. You can embed the tables into posts, pages, or text widgets with a simple Shortcode. Table data can be edited in a speadsheet-like interface, so no coding is necessary. Tables can contain any type of data, even formulas that will be evaluated. An additional JavaScript library adds features like sorting, pagination, filtering, and more for site visitors. Tables can be imported and exported from/to Excel, CSV, HTML, and JSON files.

= More information =
Please visit the plugin website at https://tablepress.org/ for more information or a [demo](https://tablepress.org/demo/)).

= Supporting future development =
If you like TablePress, please rate and review it here in the WordPress Plugin Directory, support it with your [donation](https://tablepress.org/donate/), or [flattr it](https://flattr.com/thing/783658/TablePress). Thank you!

= TablePress Extensions =
Additional features and useful enhancements are available as separate plugins, called [TablePress Extensions](http://tablepress.org/extensions/), on the plugin website.

== Screenshots ==

1. "All Tables" screen
2. "Edit" screen
3. "Add new Table" screen
4. "Import" screen
5. "Export" screen
6. "Plugin Options" screen
7. "About" screen
8. An example table (as it can be seen on the [TablePress website](https://tablepress.org/demo/))

== Installation ==

The easiest way to install TablePress is via your WordPress Dashboard. Go to the "Plugins" screen, click "Add New", and search for "TablePress" in the WordPress Plugin Directory. Then, click "Install Now" and the following steps will be done for you automatically. After the installation, you'll just have to activate the TablePress plugin.

Manual installation works just as for other WordPress plugins:

1. [Download](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip) and extract the ZIP file.
1. Move the folder "tablepress" into the "wp-content/plugins/" directory of your WordPress installation.
1. Activate the plugin "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. Add a table to a page, post, or text widget, by embedding the Shortcode `[table id=<your-table's-ID> /]` into its content, or by using the "Table" button in the editor toolbar.
1. You can change the table styling by using CSS code, which can be entered into the "Custom CSS" textarea on the "Plugin Options" screen.

== Frequently Asked Questions ==

= Where can I find answers to Frequently Asked Questions? =
Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) on the plugin website.

= Support? =
For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress). Please [search](https://wordpress.org/support/) through the forums first, and only [open a new thread](https://wordpress.org/support/plugin/tablepress) if you don't find an existing answer. Thank you!

= Requirements? =
In short: WordPress 4.0 or higher, while the latest version of WordPress is always recommended.

= Languages and Localization? =
The plugin currently comes with the following translations:
Brazilian Portuguese, Chinese (Simplified), Chinese (Taiwan), Czech, Dutch, English, Finnish, French, German, Hebrew, Icelandic, Italian, Japanese, Latvian, Polish, Russian, Serbian, Slovak, Spanish, Turkish and Ukrainian.

Translations into other languages are always welcome! With the [Codestyling Localization](https://wordpress.org/plugins/codestyling-localization/) plugin, that's really easy. Just install the plugin, add your language, create the .po file, translate the strings, and create the .mo file. It will automatically be saved into the TablePress plugin folder. If you send me the .mo and .po files, I will gladly include them into future plugin releases.
There is also a .pot file available in the "i18n" subfolder, which can be used e.g. with the [poEdit](http://www.poedit.net/) editor.

= Migration from WP-Table Reloaded =
TablePress is the official successor of the WP-Table Reloaded plugin. It has been rewritten from the ground up and uses an entirely new internal structure. This fixes some major flaws of WP-Table Reloaded and prepares the plugin for easier, safer, and better future development.
If you are currently using WP-Table Reloaded, it is highly recommended that you switch to TablePress. WP-Table Reloaded will no longer be maintained or developed. For further information on how to switch from WP-Table Reloaded to TablePress, please see the [migration guide](https://tablepress.org/migration-from-wp-table-reloaded/) on the plugin website.

= Development =
You can follow the development of TablePress more closely in its official [GitHub repository](https://github.com/TobiasBg/TablePress).

= Where can I get more information? =
Please visit the [official plugin website](https://tablepress.org/) for the latest information on this plugin, or [follow @TablePress](https://twitter.com/TablePress) on Twitter.

== Usage ==

After installing the plugin, you can create and manage tables on the "TablePress" screen in the WordPress Dashboard.
Everything should be self-explaining there.

To show one of your tables in a post, on a page, or in a text widget, just embed the Shortcode `[table id=<the-ID> /]` into the post/page/text widget, where `<the-ID>` is the ID of your table (can be found on the left side of the "All Tables" screen.)
Alternatively, you can also insert tables by clicking the "Table" button in the editor toolbar, and then selecting the desired table.

After that, you might want to change the styling of the table. You can do this by entering CSS commands into the "Custom CSS" textarea on the "Plugin Options" screen. Some examples for common styling changes can be found on the [TablePress FAQ page](https://tablepress.org/faq/).
You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

== Acknowledgements ==

Special thanks go to [Allan Jardine](http://www.sprymedia.co.uk/) for the [DataTables JavaScript library](http://www.datatables.net/).
Thanks to all language file translators!
Thanks to every donor, supporter, and bug reporter!

== License ==

This plugin is Free Software, released and licensed under the GPL, version 2 (http://www.gnu.org/licenses/gpl-2.0.html).
You may use it free of charge for any purpose.

== Changelog ==

= Version 1.5.1 =
* Bugfix: Some properties in Custom CSS code were erroneously removed.
* Updated the Spanish translation.

= Version 1.5 =
* Full compatibility with WordPress 4.0
* Feature: Support for the new Media Manager when inserting images
* Feature: Support for the integrated WP importer/exporter
* Bugfix: The "Insert Link" dialog in the "Advanced Editor" works now.
* Bugfix: Moving the admin menu entry somewhere else was broken in rare cases.
* Bugfix: The HTML export creates valid HTML files now.
* Enhancement: Tables are now stored with extra information about the format, so that other plugins are less likely to break it.
* Extended unit tests for the plugin and some external libraries.
* Updated external libraries (CodeMirror, DataTables, Build tools).
* Added Ukrainian translation.
* Updated several translations (Chinese (Simplified), Dutch, English, French, German, Hebrew, Italian, Japanese, Russian, Turkish).
* Added and updated more language files for the DataTables library.
* TablePress 1.5 requires WordPress 4.0!

= Version 1.4 =
* Compatibility with WordPress 3.9
* Bugfix: Determine the correct Worksheet ID during XLSX import
* Bugfix: Displaying empty Shortcodes was broken
* Enhancement: Improve JSON import to also allow import of JSON objects
* Enhancement: Use more sophisticated error handling and debugging
* Enhancement: Reduce memory usage when loading tables
* Added inline documentation to all plugin filter and action hooks
* Updated external libraries
* Internal improvements to coding standards, inline documentation, and build tools
* Added Serbian translation
* Updated several translations (Chinese (Simplified), Croatian, German, Spanish)

= Version 1.3 =
* Compatibility with WordPress 3.8 and the new admin styling
* Bugfix: Import of JSON files did not take row/column visibility into account
* Bugfix: File names of exported files were sometimes broken
* Bugfix: Translations for some strings were not loaded properly
* Enhancement: Don't search for tables outside of the main search query
* Enhancement: Broken tables are now skipped
* Updated external libraries
* Added Chinese (Taiwan) translation
* Internal improvements to coding standards, inline documentation, and build tools
* TablePress 1.3 requires WordPress 3.8!

= Version 1.2 =
* Compatibility with WordPress 3.7
* Bugfix: WordPress search did not find tables in some cases
* Bugfix: Cells were sometimes erroneously interpreted as formulas
* Bugfix: HTML export did not encode entities properly
* Bugfix: Wrong variable name in table render code
* Enhancement: Add logarithm to math functions for formulas
* Enhancement: Better internal code documentation and variable type checks
* Enhancement: Add parameter to Shortcode that allows showing debug information
* Updated external libraries
* Updated several translations (Brazilian Portuguese, Czech, French, German, Latvian)
* Many more internal code improvements
* TablePress 1.2 requires WordPress 3.6!

= Version 1.1.1 =
* Fixed a bug with CSS handling that broke some TablePress Extensions

= Version 1.1 =
* Experimental import for Excel files (.xls and .xlsx)
* More math functions in formulas (including if-conditionals, statistical functions, ...)
* Better "Custom CSS" saving for higher performance
* Bugfix: Encoding problem during HTML import
* Bugfix: Roles are now deleted during uninstallation
* Bugfix: Search for tables was broken, if Shortcode had been changed
* Plugin Unit Tests for automated code testing
* Added several new translations (Brazilian Portuguese, Czech, Dutch, Finnish, Hebrew, Icelandic, Italian, Japanese, Latvian, Russian, and Turkish)
* Many more internal improvements of code and usability
* Updated external libraries

= Version 1.0 =
Official release with a few fixes and many enhancements and improvements

= Version 0.9-RC =
Release candidate in which all intended features are included and very stable.

= Version 0.8.1-beta =
Initial version where most features are ready and pretty stable.

== Upgrade Notice ==

= 1.5.1 =
This update includes several new features, enhancements, and bugfixes. Updating is recommended.

= 1.5 =
This update includes several new features, enhancements, and bugfixes. Updating is recommended.

= 1.4 =
This update is a stability, maintenance, and compatibility release. Updating is recommended.

= 1.3 =
This update is a stability, maintenance, and compatibility release. Updating is recommended.

= 1.2 =
This update is a stability, maintenance, and compatibility release. Updating is recommended.

= 1.1.1 =
This upgrade includes several new features, enhancements, and bugfixes, and is a recommended maintenance release.

= 1.1 =
This upgrade includes several new features, enhancements, and bugfixes, and is a recommended maintenance release.

= 1.0 =
This release contains a few bug fixed and many enhancements and new features, and is a recommended update.

= 0.9-RC =
This release contains many enhancements and bug fixes.
