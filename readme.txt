=== TablePress ===
Contributors: TobiasBg
Donate link: https://tablepress.org/donate/
Tags: table,spreadsheet,data,csv,excel,html,tables
Requires at least: 5.8
Requires PHP: 5.6.20
Tested up to: 6.2
Stable tag: 2.1.3
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful and feature-rich tables into your posts and pages, without having to write code.

== Description ==

TablePress is the most popular and highest rated WordPress table plugin. It allows you to easily create and manage beautiful tables on your website. You can embed the tables into posts, pages, or other site areas using a block in the block editor. Table data can be edited in a spreadsheet-like interface, without any coding. Tables can contain any type of data, even math formulas that will be evaluated. Additional features like sorting, pagination, and filtering make it easy for site visitors to interact with the table data. Tables can be imported and exported from/to Excel, CSV, HTML, and JSON files.

**Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/)**

= More information =
Visit the plugin website at [tablepress.org](https://tablepress.org/) for more information, take a look at [example tables](https://tablepress.org/demo/), or [check out TablePress on a free test site](https://tablepress.org/demo/#try). For latest news, [follow @TablePress](https://twitter.com/TablePress) on Twitter.

== Screenshots ==

1. "All Tables" screen
2. "Edit" screen
3. "Add new Table" screen
4. "Import" screen
5. "Export" screen
6. "Plugin Options" screen
7. "About" screen
8. The “TablePress table” block in the block editor
9. An example table (as it can be seen on the [TablePress website](https://tablepress.org/demo/))

== Installation ==

The easiest way to install TablePress is via your WordPress Dashboard:

1. Go to the "Plugins" screen, click "Add New", and search for "TablePress" in the WordPress Plugin Directory.
1. Click "Install Now" and after that's complete, click "Activate".
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table.
1. You can change the table styling by using CSS code, which can be entered into the "Custom CSS" textarea on the "Plugin Options" screen.

Manual installation works just as for other WordPress plugins:

1. [Download](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip) and extract the ZIP file.
1. Move the folder "tablepress" to the "wp-content/plugins/" directory of your WordPress installation, e.g. via FTP.
1. Activate the plugin "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table.
1. You can change the table styling by using CSS code, which can be entered into the "Custom CSS" textarea on the "Plugin Options" screen.

== Frequently Asked Questions ==

= Where can I find answers to Frequently Asked Questions? =
Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) on the plugin website.

= Support? =

**Premium Support**

Users with a valid TablePress Premium license plan are eligible for Priority Email Support, directly from the plugin developer! [Find out more!](https://tablepress.org/premium/)

**Community Support for users of the Free version**

For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress/). Please search through the forums first, and only [create a new topic](https://wordpress.org/support/plugin/tablepress/#new-post) if you don't find an existing answer. Thank you!

= Requirements? =
In short: WordPress 5.8 or higher, while the latest version of WordPress is always recommended.

= Languages and Localization? =
TablePress uses the ["Translate WordPress" platform](https://translate.wordpress.org/). Please see the sidebar on the TablePress page in the [WordPress Plugin Directory](https://wordpress.org/plugins/tablepress/) for available translations.

To make TablePress available in your language, go to the [TablePress translations page](https://translate.wordpress.org/projects/wp-plugins/tablepress), log in with a free wordpress.org account and start translating.

= Development =
You can follow the development of TablePress more closely in its official [GitHub repository](https://github.com/TablePress/TablePress).

= Where can I get more information? =
Visit the plugin website at [tablepress.org](https://tablepress.org/) for the latest information on TablePress or [follow @TablePress](https://twitter.com/TablePress) on Twitter.

== Usage ==

After installing the plugin, you can create and manage tables on the "TablePress" screen in the WordPress Dashboard.

To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table.

Examples for common styling changes via "Custom CSS" code can be found on the [TablePress FAQ page](https://tablepress.org/faq/).
You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

**Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/)**

== License ==

This plugin is Free Software, released and licensed under the [GPL, version 2](https://www.gnu.org/licenses/gpl-2.0.html). You may use it free of charge for any purpose.

== Changelog ==

Changes in recent versions are shown below. For earlier changes, please see the [changelog history](https://tablepress.org/info/#changelog).

= Version 2.1.3 =

TablePress 2.1.3 fixes a few bugs and brings some nice enhancements. For more information on changes and new features in TablePress 2.1, please see below.

* Pasting text into table cells via the right-click context menu on the “Edit” screen now works as expected.
* The list of allowed CSS properties in “Custom CSS” has been extended to include new properties.
* The Server-side Processing feature now shows a loading animation while data is retrieved from the server. (TablePress Max only.)
* The TablePress REST API now only returns the “_links” field upon request, for improved performance. (TablePress Max only.)
* The TablePress REST API now caches the generated JSON schema, for improved performance. (TablePress Max only.)
* Cleaned up, improved, and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Several external code libraries and build tools have been updated to benefit from enhancements and bug fixes.

= Version 2.1.2 =

TablePress 2.1.2 fixes a few bugs and brings some nice enhancements. For more information on changes and new features in TablePress 2.1, please see below.

* The “Modules” screen now supports the Ctrl/Cmd + S keyboard shortcut for saving changes on the screen. (TablePress Pro and Max only.)
* Some erroneously removed CSS code for the TablePress admin screens was restored.
* The TablePress REST API now properly returns the table ID as well. (TablePress Max only.)
* Cleaned up, improved, and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Several external code libraries and build tools have been updated to benefit from enhancements and bug fixes.

TablePress 2.1.1 contains these changes:

* The “Plugin Options” screen now supports the Ctrl/Cmd + S keyboard shortcut for saving changes on the screen.
* Keyboard shortcuts for moving cells no longer erroneously trigger when navigating inside an edited cell.
* A few typos in translatable strings were corrected.
* Some instances of invalid HTML code were fixed.
* Cleaned up, improved, and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Several external code libraries and build tools have been updated to benefit from enhancements and bug fixes.

TablePress 2.1 contains these changes:

**New features, enhancements, and bug fixes**

* Full compatibility with WordPress 6.2.
* On the “Edit” screen, you can now set the desired default cell size via the “Screen Options” tab, for maximum flexibility when editing large tables.
* The table editor’s context menu now also works when editing a cell.
* The table editor’s context menu and keyboard shortcuts now allow inserting images and links at the current cursor position and as well as turning existing text into a link.
* On the “Edit” screen, potential confusion is reduced by adjusting how individual sections can be collapsed.
* Tables that use scrolling are less likely to have misplaced header cells.
* The “Export” screen now has a button to quickly reverse a long list of tables, which can save time if you want to export a table from the end of the list.
* The “Last Editor” of a table is again determined correctly.
* More detailed error messages are shown on the “Edit” and “Import” screens.
* A few styling glitches on the different TablePress admin screens have been fixed.
* The “Edit” screen shows other available features, to make users aware of them. (TablePress Free only.)
* The “Fixed Header” module now works more reliably with themes that use floating elements as well. (TablePress Pro and Max only.)
* The “Alphabet Filtering” module has more options, like choosing a filtering column and alphabet, and is more robust on small screens. (TablePress Pro and Max only.)
* The “Automatic Filtering” module also allows using commas in filter values now. (TablePress Pro and Max only.)
* The “Column Filter Dropdowns” module now only disables selections if none can be made. (TablePress Pro and Max only.)
* The “REST API” module now allows public and unauthenticaed requests, if desired. (TablePress Max only.)
* The “Server-side Processing” module is more robust when dealing with large tables that use many settings. (TablePress Max only.)
* Cleaned up, improved, and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Several external code libraries and build tools have been updated to benefit from enhancements and bug fixes.

**Premium versions**

* Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/)

== Upgrade Notice ==

= 2.1.3 =
This update is a stability and maintenance release. Updating is highly recommended.

= 2.1 =
This update is a feature and enhancement release. Updating is highly recommended.
