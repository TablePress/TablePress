=== TablePress ===
Contributors: TobiasBg
Donate link: https://tablepress.org/donate/
Tags: table,spreadsheet,data,csv,excel,html,tables
Requires at least: 5.6
Requires PHP: 5.6.20
Tested up to: 5.8
Stable tag: 1.14
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful and feature-rich tables into your posts and pages, without having to write code.

== Description ==

TablePress allows you to easily create and manage beautiful tables. You can embed the tables into posts, pages, or text widgets with a simple Shortcode. Table data can be edited in a spreadsheet-like interface, so no coding is necessary. Tables can contain any type of data, even formulas that will be evaluated. An additional JavaScript library adds features like sorting, pagination, filtering, and more for site visitors. Tables can be imported and exported from/to Excel, CSV, HTML, and JSON files.

= More information =
Please visit the plugin website at [tablepress.org](https://tablepress.org/) for more information or a [demo](https://tablepress.org/demo/). For latest news, [follow @TablePress](https://twitter.com/TablePress) on Twitter.

= Supporting future development =
If you like TablePress, please rate and review it here in the WordPress Plugin Directory or support it with your [donation](https://tablepress.org/donate/). Thank you!

= TablePress Extensions =
Additional features and useful enhancements are available as separate plugins, called [TablePress Extensions](https://tablepress.org/extensions/), on the plugin website.

Do you have a lot of tables? You can organize them in folders! [Wicked Folders Pro supports TablePress](https://tablepress.org/extensions/wicked-folders-pro/) and might be the right solution for you.

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

The easiest way to install TablePress is via your WordPress Dashboard. Go to the "Plugins" screen, click "Add New", and search for "TablePress" in the WordPress Plugin Directory. Then, click "Install Now" and wait a moment. Finally, click "Activate" and start using the plugin!

Manual installation works just as for other WordPress plugins:

1. [Download](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip) and extract the ZIP file.
1. Move the folder "tablepress" to the "wp-content/plugins/" directory of your WordPress installation, e.g. via FTP.
1. Activate the plugin "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, copy its Shortcode `[table id=<ID> /]` and paste it into a "Shortcode" block at the desired place in the block editor. Each table has a unique ID that needs to be adjusted in that Shortcode.
1. You can change the table styling by using CSS code, which can be entered into the "Custom CSS" textarea on the "Plugin Options" screen.

== Frequently Asked Questions ==

= Where can I find answers to Frequently Asked Questions? =
Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) on the plugin website.

= Support? =
For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress/). Please search through the forums first, and only [create a new topic](https://wordpress.org/support/plugin/tablepress#new-post) if you don't find an existing answer. Thank you!

= Requirements? =
In short: WordPress 5.6 or higher, while the latest version of WordPress is always recommended.

= Languages and Localization? =
TablePress uses the ["Translate WordPress" platform](https://translate.wordpress.org/). Please see the sidebar on the TablePress page in the [WordPress Plugin Directory](https://wordpress.org/plugins/tablepress/) for available translations.

To make TablePress available in your language, go to the [TablePress translations page](https://translate.wordpress.org/projects/wp-plugins/tablepress), log in with a free wordpress.org account and start translating.

= Migration from WP-Table Reloaded =
Several years ago, TablePress has superseded the now discontinued WP-Table Reloaded plugin. If you are still using that, please see the [migration guide](https://tablepress.org/migration-from-wp-table-reloaded/) and switch to TablePress.

= Development =
You can follow the development of TablePress more closely in its official [GitHub repository](https://github.com/TobiasBg/TablePress).

= Where can I get more information? =
Please visit the [official plugin website](https://tablepress.org/) for the latest information on this plugin, or [follow @TablePress](https://twitter.com/TablePress) on Twitter.

== Usage ==

After installing the plugin, you can create and manage tables on the "TablePress" screen in the WordPress Dashboard.

To show one of your tables in a post or on a page, embed the Shortcode `[table id=<the-ID> /]` into a "Shortcode" block at the desired place in the block editor. Each table has a unique ID that needs to be adjusted in that Shortcode.

After that, you might want to change the styling of the table. You can do this by entering CSS commands into the "Custom CSS" textarea on the "Plugin Options" screen. Some examples for common styling changes can be found on the [TablePress FAQ page](https://tablepress.org/faq/).
You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

== Acknowledgements ==

Special thanks go to [Allan Jardine](https://www.sprymedia.co.uk/) for the [DataTables JavaScript library](https://www.datatables.net/).
Thanks to all language file translators!
Thanks to every donor, supporter, and bug reporter!

== License ==

This plugin is Free Software, released and licensed under the GPL, version 2 (https://www.gnu.org/licenses/gpl-2.0.html).
You may use it free of charge for any purpose.

== Changelog ==

Recent changes are shown below. For earlier changes, please see the [changelog history](https://tablepress.org/info/#changelog).

= Version 1.14 =
* Full compatibility with WordPress 5.8.
* Enhancement: Make behavior of bulk action selectors on the "All tables" screen more user-friendly.
* Enhancement: Apply and enforce more granular coding standards for better code maintainability.
* Enhancement: Allow import files with an .xlsm file extension, in addition to .xlsx files.
* Enhancement: Add more options (filter and action hooks) for external integration of custom code.
* Bugfix: Fix handling of the help box on the "Edit" screen when it's opened again after closing it.
* Bugfix: Properly return modified table data when external code integrates into TablePress.
* Bugfix: Correctly localize dates in files names of downloaded export files.
* Bugfix: Fix typos in the code for math formula parsing that could lead to error messages in rare cases.
* Updated external libraries (DataTables, SimpleXLSX, Build tools).
* Some internal changes for better stability, translations, and documentation.

= Version 1.13 =
* Full compatibility with WordPress 5.7.
* Enhancement: Allow replacing/appending existing tables when importing a ZIP archive of files.
* Enhancement: Add ARIA labels to the table when its name or description is printed.
* Enhancement: Update list of allowed CSS features in "Custom CSS".
* Enhancement: Increase compatibility with PHP 8 and jQuery 3.x by no longer using deprecated functions.
* Bugfix: Properly update cell references in formulas when one is moved, on the "Edit" screen.
* Updated external libraries (DataTables, SimpleXLSX, CSSTidy, Build tools).
* Some internal changes for better stability, translations, and documentation.
* TablePress 1.13 requires WordPress 5.6!

== Upgrade Notice ==

= 1.14 =
This update is a stability, maintenance, and compatibility release. Updating is recommended.

= 1.13 =
This update is a stability, maintenance, and compatibility release. Updating is recommended.
