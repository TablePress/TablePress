=== TablePress - Tables in WordPress made easy ===
Contributors: TobiasBg
Donate link: https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=donate-link
Tags: table, spreadsheet, csv, excel, tables
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 3.1.3
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful, accessible, and interactive tables into your WordPress website’s posts and pages, without having to write code!

== Description ==

**Boost your website with feature-rich tables that your visitors will love!**

TablePress is the most popular and highest-rated WordPress table plugin.

* Easily create, edit, and manage **beautiful and modern** data tables, no matter if **small or large**!
* Add live **sorting**, **pagination**, **searching**, and more interactivity for your site’s visitors!
* Use any type of data, insert **images**, **links**, and even **math formulas**!
* **Import** and **export** tables from/to Excel, CSV, HTML, and JSON files or URLs.
* Embed tables into posts, pages, or other site areas using the block editor, an Elementor widget, or Shortcodes.
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
1. To insert a table into a post or page, add a "TablePress table" block in the block editor or a widget in the Elementor page builder and select the desired table or use Shortcodes with other page builders.

Manual installation works just as for other WordPress plugins:

1. [Download the TablePress ZIP file](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip).
1. Go to the “Plugins“ screen on your site and upload it by clicking “Add New” → “Upload Plugin“.
1. Or, extract the ZIP file and move the folder “tablepress“ to the “wp-content/plugins/“ directory of your WordPress installation, e.g. via FTP.
1. Activate "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, add a "TablePress table" block in the block editor or a widget in the Elementor page builder and select the desired table or use Shortcodes with other page builders.

== Frequently Asked Questions ==

= Where can I find answers to Frequently Asked Questions? =
Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) and in the extensive [TablePress plugin documentation](https://tablepress.org/documentation/) on the TablePress website.

= Support? =

**Premium Support**

Users with an active TablePress Premium license plan are eligible for Priority Email Support, directly from the plugin developer! [Find out more!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

**Community Support for users of the Free version**

For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress/). Please search through the forums first, and only [create a new topic](https://wordpress.org/support/plugin/tablepress/#new-post) if you don't find an existing answer. Thank you!

= Requirements? =
In short: WordPress 6.2 or higher, while the latest version of WordPress is always recommended. In addition, the server must be running PHP 7.4 or newer.

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

To insert a table into a post or page, add a "TablePress table" block in the block editor or a widget in the Elementor page builder and select the desired table or use Shortcodes with other page builders.

Beginner-friendly step-by-step [tutorials, guides, and how-tos](https://tablepress.org/tutorials/) show how to achieve common and popular tasks with TablePress.
Examples for common styling changes via "Custom CSS" code can be found on the [TablePress FAQ page](https://tablepress.org/faq/).
You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

**Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)**

== Changelog ==

Changes in recent versions are shown below. For earlier changes, please see the [changelog history](https://tablepress.org/info/#changelog).

= Version 3.1.3 (May 22, 2025) =

* **Security fix**: Authenticated Stored XSS. Thanks to Asaf Mozes and the Wordfence team for following responsible disclosure policies when reporting this issue!
* Enhancement: Improve handling of multi-byte strings with special characters from non-Latin alphabets.
* “Automatic Periodic Table Import“ module: Prevent tables from losing their options by always loading other modules during an automatic import. (TablePress Max only.)
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.

= Version 3.1.2 (April 29, 2025) =

* “Responsive Tables“ module: Don’t require the “Search/Filtering“ feature to be activated in order to enable the “Collapse“ or “Modal“ modes. (TablePress Pro and Max only.)
* “Individual Column Filtering“ module: Fix the table column widths when the “Fixed Header“ or “Fixed Columns“ features are used. (TablePress Pro and Max only.)
* “Automatic Periodic Table Import“ module: Limit the number of revisions for automatically updated tables, to prevent database size and PHP memory issues. (TablePress Max only.)
* “Advanced Pagination Settings“ module: Prevents problems when sites use HTML code minification. (TablePress Pro and Max only.)
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.

= Version 3.1.1 (April 1, 2025) =

* The CSS code for styling individual table rows will now be applied again.
* Users of the Elementor page builder can now properly delete a “TablePress table“ widget again.

= Version 3.1 (March 25, 2025) =

TablePress 3.1 is a major feature, stability, maintenance, compatibility, and security update. Here are the highlights:

**Improved Frontend Table Performance**

* Users of the Elementor page builder plugin can now use a dedicated “TablePress table“ widget that makes embedding tables even easier!
* Showing tables in tabs or accordions will no longer break their size or add visual glitches!
* Tables and their interactivity features are more accessible for visitors with disabilities and users of assistive technologies, with improved labelling and easier-to-use keyboard navigation!

**New Premium Feature Modules**

* **Email Notifications**
  * Get email notifications when certain actions are performed on tables!

**Many New Features and Enhancements for Existing Premium Features**

* **Responsive Tables**
  * The styling and highlighting of rows and child rows when using the “Collapse“ mode has been improved to make it even easier to see which data belongs together!
* **Advanced Pagination Settings**
  * Use a “Show more“ button instead of classical pagination for improved visitor engagement!
* **Column Filter Dropdowns**
  * You can now turn on classical single-selection dropdown controls for a more solid user experience!
* **Row Filtering**
  * A new syntax that understands complex logic expressions gives Row Filtering superpowers!
* **Row Highlighting and Cell Highlighting**
  * Highlighting cells and rows is now possible with complex logic and math expressions, for even more control!

**Behind the scenes**

* **Security fix**: Authenticated Stored XSS (CVE-2025-2685). Thanks to SavPhill and the Wordfence team for following responsible disclosure policies when reporting this issue!
* Several minor bugs and inconsistencies have been fixed and improved!
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Automated code compatibility checks and build tools simplify chores for easier development.
* Improved support for PHP 8.4.

**Premium versions**

* Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

== Upgrade Notice ==

= 3.1.3 =
This update is a security and maintenance release. Updating is highly recommended!

= 3.1 =
This update is a major feature, stability, maintenance, and compatibility release. Updating is highly recommended!
