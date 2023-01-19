=== TablePress ===
Contributors: TobiasBg
Donate link: https://tablepress.org/donate/
Tags: table,spreadsheet,data,csv,excel,html,tables
Requires at least: 5.8
Requires PHP: 5.6.20
Tested up to: 6.1
Stable tag: 2.0.4
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

Recent changes are shown below. For earlier changes, please see the [changelog history](https://tablepress.org/info/#changelog).

= Version 2.0.4 =

TablePress 2.0.4 fixes a few bugs and brings some nice enhancements. For more information on changes and new features in TablePress 2.x, please see below.

* The “Table” button for users that don’t use the WordPress block editor is back!
* Issues that some users had with saving changes on the “Edit” screen are fixed.
* No errors should be thrown anymore when required PHP extensions are missing on a server when importing files.
* Visual glitches in the Horizontal Scrolling, caused by themes adding conflicting CSS, are reduced.
* The “Automatic Filtering” feature now allows retrieving the filter term from a URL parameter. (TablePress Pro and Max only.)
* A backwards compatibility improvement was added to the “Individual Column Filtering” feature. (TablePress Pro and Max only.)
* The “Server-side Processing” feature now uses much shorter request URL which improves performance and prevents possible server errors. (TablePress Max only.)
* Some internal documentation and build tools were updated.

TablePress 2.0.3 contains these changes:

* The “Edit” screen now has context menu entries and supports Ctrl/Cmd + Alt/option + Shift + ↑/↓/←/→ keyboard shortcuts for moving the currently selected rows to the top or bottom, or the currently selected columns to the left or right edge.
* On the “Edit” screen, it's now easier to drag and drop rows and columns with the mouse, as the clickable regions are now bigger.
* On the “Edit” screen, the cell height was reduced, so that more content fits on the screen.
* The “Edit” screen now properly supports copying and pasting of content that contains quotation marks.
* The “Show Shortcode” link on the “All tables” screen, often used for copy and paste, is back!
* The automatic format detection of the file import is more robust, especially when importing CSV files.
* TablePress better protects itself against conflicts caused by other plugins that use outdated versions of the Composer tool.
* An error where tables could not be saved was fixed. (TablePress Pro and Max only.)
* The Column Filter Dropdowns module now properly deals with & characters and HTML code in cells, and applies better sorting to the selectable options. (TablePress Pro and Max only.)

TablePress 2.0.2 contains these changes:

* The “Edit” screen now supports Ctrl/Cmd + L/I/E keyboard shortcuts for the “Insert Link”, “Insert Image”, and “Advanced Editor” buttons, respectively.
* The “Edit” screen now supports Ctrl/Cmd + Shift + ↑/↓/←/→ keyboard shortcuts for moving the currently selected rows up or down, or the currently selected columns left or right.
* An error where tables could not be saved was fixed. (TablePress Pro and Max only.)
* The Fixed Columns module now properly shows multiple fixed columns. (TablePress Pro and Max only.)
* The integration of the Automatic Periodic Table Import module was fixed. (TablePress Max only.)

TablePress 2.0.1 contains these changes:

* TablePress will again work correctly when it's “network activated” on WordPress Multisite installations.
* Issues that some users had with saving changes on the “Edit” screen are fixed.
* The misalignment between head and body rows when using “Horizontal Scrolling” is fixed.
* The vertical alignment of elements in table cells is back to its old behavior, due to issues in some themes.
* No errors should be thrown anymore when the “Table Features for Site Visitors” are active for tables that have combined cells or no head row.
* The “Shortcode” text field, often used for copy and paste, on the “Edit” screen is back!
* More valuable information about errors is given when a table import fails.
* An issue with the sorting arrow icons showing as weird characters is fixed.
* No errors should be thrown anymore when required PHP extensions are missing on a server when evaluating math formulas.

Besides a fresh and modern look of the TablePress screens, here are the highlights of TablePress 2.0:

**Completely new “Edit” screen for tables**

* TablePress now has an even more spreadsheet-like user interface that makes working with large tables a breeze.
* Editing will be much faster: A right-click context menu allows for quick access to the table manipulation tools.
* A keyboard shortcut for saving changes will save a lot of scrolling.

**Block editor support instead of having to deal with Shortcodes**

* The new “TablePress table” block will give you a preview of the table when inserting it into a post or page, for a more intuitive embedding of tables. You won’t even have to remember the table ID, as a table search is included.
* And if you want, converting existing Shortcodes is just two clicks away.

**Easier styling of tables with CSS variables**

* You can now use CSS variables instead of complex CSS selectors for quicker and easier styling changes, in particular of table colors.
* The CSS selectors in the default CSS have been simplified for higher compatibility with more themes.

**Importing tables is easier and more powerful than ever**

* Simply drag&drop files to import them -- even multiple files at once, even of different formats!
* No more need to choose the import file format: The auto detection will know if it’s a CSV, Excel, HTML, or JSON file.
* In addition, more file formats are recognized, like the LibreOffice ODS format.
* The Excel import is much more powerful: For example, clickable links and simple styling will be recognized and imported!
* The Replace/Append select box also has a live-search with autocomplete now, making finding the right table much faster.

**New formula calculation engine**

* TablePress now understands many more formulas, just as you know them from Excel!
* The formulas even support text strings now, which enables many new ways of automatically creating table content.
* When exporting tables, potentially dangerous formulas will be escaped, to increase protection against CSV injection attacks.

**Premium versions**

* Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/)

**Behind the scenes**

* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Automated code compatibility checks and build tools simplify chores for easier development.
* Support for PHP 8.1 and PHP 8.2.
* TablePress 2.0 requires WordPress 5.8.

== Upgrade Notice ==

= 2.0.4 =
This update is a stability, maintenance, and compatibility release. Updating is highly recommended.

= 2.0 =
This update is a major feature update that brings many cool and new things. Updating is highly recommended.
