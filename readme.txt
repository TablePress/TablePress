=== TablePress - Tables in WordPress made easy ===
Contributors: TobiasBg
Donate link: https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=donate-link
Tags: table, spreadsheet, csv, excel, tables
Requires at least: 6.0
Requires PHP: 7.2
Tested up to: 6.5
Stable tag: 2.3.1
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful and interactive tables into your WordPress website’s posts and pages, without having to write code!

== Description ==

**Boost your website with feature-rich tables that your visitors will love!**

TablePress is the most popular and highest-rated WordPress table plugin.

* Easily create, edit, and manage **beautiful and modern** data tables, no matter if **small or large**!
* Add live **sorting**, **pagination**, **searching**, and more interactivity for your site’s visitors!
* Use any type of data, insert **images**, **links**, and even **math formulas**!
* **Import** and **export** tables from/to Excel, CSV, HTML, and JSON files or URLs.
* Embed tables into posts, pages, or other site areas using the block editor or Shortcodes.
* All with **no coding knowledge needed**!

Even **more great features** for you and your site’s visitors and **priority email support** are **available** with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

= More information =
Visit [tablepress.org](https://tablepress.org/) for more information, take a look at [example tables](https://tablepress.org/demo/), or [try TablePress on a free test site](https://tablepress.org/demo/#try). For latest news, [follow @TablePress](https://twitter.com/TablePress) on Twitter/X or subscribe to the [TablePress Newsletter](https://tablepress.org/#newsletter).

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
1. To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table or use Shortcodes with common page builders.

Manual installation works just as for other WordPress plugins:

1. [Download the TablePress ZIP file](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip) and extract it on your computer.
1. Move the folder "tablepress" to the "wp-content/plugins/" directory of your WordPress installation, e.g. via FTP.
1. Activate "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table or use Shortcodes with common page builders.

== Frequently Asked Questions ==

= Where can I find answers to Frequently Asked Questions? =
Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) on the TablePress website.

= Support? =

**Premium Support**

Users with an active TablePress Premium license plan are eligible for Priority Email Support, directly from the plugin developer! [Find out more!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

**Community Support for users of the Free version**

For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress/). Please search through the forums first, and only [create a new topic](https://wordpress.org/support/plugin/tablepress/#new-post) if you don't find an existing answer. Thank you!

= Requirements? =
In short: WordPress 6.0 or higher, while the latest version of WordPress is always recommended. In addition, the server must be running PHP 7.2 or newer.

= Languages and Localization? =
TablePress uses the ["Translate WordPress" platform](https://translate.wordpress.org/). Please see the sidebar on the TablePress page in the [WordPress Plugin Directory](https://wordpress.org/plugins/tablepress/) for available translations.

To make TablePress available in your language, go to the [TablePress translations page](https://translate.wordpress.org/projects/wp-plugins/tablepress), log in with a free wordpress.org account and start translating.

= Development =
You can follow the development of TablePress more closely in its official [GitHub repository](https://github.com/TablePress/TablePress).

= Where do I report security issues? =
Please report security issues and bugs found in the source code of TablePress through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/tablepress).
The Patchstack team will assist you with verification, CVE assignment, and notify the TablePress developer.

= Where can I get more information? =
Visit the plugin website at [tablepress.org](https://tablepress.org/) for the latest news on TablePress, [follow @TablePress](https://twitter.com/TablePress) on Twitter/X, or subscribe to the [TablePress Newsletter](https://tablepress.org/#newsletter).

== How to use TablePress ==

After installing the plugin, you can create and manage tables on the "TablePress" screen in the WordPress Dashboard.

To insert a table into a post or page, add a "TablePress table" block in the block editor and select the desired table or use Shortcodes with common page builders.

Beginner-friendly step-by-step [tutorials, guides, and how-tos](https://tablepress.org/tutorials/) show how to achieve common and popular tasks with TablePress.
Examples for common styling changes via "Custom CSS" code can be found on the [TablePress FAQ page](https://tablepress.org/faq/).
You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

**Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)**

== Changelog ==

Changes in recent versions are shown below. For earlier changes, please see the [changelog history](https://tablepress.org/info/#changelog).

= Version 2.3.1 (April 30, 2024) =

* Bugfix: Fix an error in the legacy import that accidentally broke an old and retired TablePress Extension.
* Enhancement: Improve the sorting on the “Edit” and “Automatic Periodic Table Import” screens and of the “Column Filter Dropdowns” feature module.
* Several external code libraries and build tools have been updated to benefit from enhancements and bug fixes.

= Version 2.3 (April 2, 2024) =

* Full compatibility with WordPress 6.5.
* Feature: The “Automatic Periodic Table Import” feature module has been rewritten from scratch: It now supports a different import interval or schedule for each table and is much more reliable. (TablePress Max only.)
* Enhancement: The import of multiple tables from a ZIP file is more robust and now works independently from the server configuration.
* Enhancement: The user interface works more reliably even when other plugins insert code on the TablePress screens.
* Enhancement: The “TablePress table” block settings are less confusing as the unsupported “Alignment” setting was removed.
* Enhancement: The “Automatic Filter” feature module now uses less-restrictive input filtering, to allow for more filtering terms, e.g. from different alphabets. (TablePress Pro and Max only.)
* Enhancement: The “About” screen now shows more useful troubleshooting information.
* Bugfix: The “Column Filter Dropdowns” feature module now properly handles multiple line breaks in table cells. (TablePress Pro and Max only.)
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Automated code compatibility checks and build tools simplify chores for easier development.

**Premium versions**

* Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

== Upgrade Notice ==

= 2.3.1 =
This update is a stability and maintenance release. Updating is highly recommended.

= 2.3 =
This update is a feature, stability, maintenance, and compatibility release. Updating is highly recommended.
