=== TablePress - Tables in WordPress made easy ===
Contributors: TobiasBg
Donate link: https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=donate-link
Tags: table, spreadsheet, csv, excel, tables
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 3.2.5
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

1. Go to the "Plugins" screen, click "Add Plugin", and search for "TablePress" in the WordPress Plugin Directory.
1. Click "Install Now" and after that's complete, click "Activate".
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. To insert a table into a post or page, add a "TablePress table" block in the block editor or a widget in the Elementor page builder and select the desired table or use Shortcodes with other page builders.

Manual installation works just as for other WordPress plugins:

1. [Download the TablePress ZIP file](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip).
1. Go to the “Plugins” screen on your site and upload it by clicking “Add Plugin” → “Upload Plugin”.
1. Or, extract the ZIP file and move the folder “tablepress” to the “wp-content/plugins/” directory of your WordPress installation, e.g. via FTP.
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

= Version 3.2.5 (October 28, 2025) =

* **Security fix**: Authenticated Stored XSS (CVE-2025-12324). Thanks to Rafshanzani Suhada and the Wordfence team for following responsible disclosure policies when reporting this issue!
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Improved support for PHP 8.5.

= Version 3.2.4 (October 21, 2025) =

* Improvement: Improve table import when importing URLs from external services like Google Sheets, Microsoft OneDrive, and Dropbox.
* Bugfix: Elementor integration: Prevent an error with the “Element Cache”.
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.

= Version 3.2.3 (September 23, 2025) =

* Elementor integration: Support for clearing the “Element Cache” was added.
* Elementor widget: The “Configuration Parameters” field is now shown properly again.
* Elementor widget: “Dynamic Tags” are now supported for the “Configuration Parameters” field.
* New feature: The “Advanced Pagination Settings” feature module now offers pagination with a select dropdown field. (TablePress Pro and Max only.)
* Improvement: The “Individual Column Filtering” search fields now support the native Clear button. (TablePress Pro and Max only.)
* Bug fix: Ensure that available premium translation files are loaded correctly. (TablePress Pro and Max only.)
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Improved support for PHP 8.5.

= Version 3.2.2 (September 23, 2025) =

This version was not released, due to issues in the release process.

= Version 3.2.1 (August 28, 2025) =

* **Security fix**: Authenticated Stored XSS (CVE-2025-9500). Thanks to Muhammad Yudha and the Wordfence team for following responsible disclosure policies when reporting this issue!
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Improved support for PHP 8.5.

= Version 3.2 (August 26, 2025) =

TablePress 3.2 is a feature, stability, maintenance, and compatibility update. Here are the highlights:

**Improved Frontend Table Performance**

* Tables and their interactivity features are more accessible for visitors with disabilities and users of assistive technologies, with improved labelling and easier-to-use keyboard navigation!

**Many New Features and Enhancements for Existing Premium Features**

* **Fixed Header**
  * When combining the Fixed Header Row with Horizontal Scrolling, the header will scroll properly as well.
* **Server-side Processing**
  * It is now possible to use the “Column Filter Dropdowns”, “Individual Column Filtering”, and “Inverted Filtering” feature modules while benefitting from the fast loading of large tables from the server!
* **Advanced Access Rights**
  * The user interface now allows filtering for users and tables, to quickly find the right combination!
  * Only user roles that are allowed to edit tables will be shown, making the overview much more lightweight!
* **Index or Counter Column**
  * The rendering performance has been improved for large tables.
* **Row Order and Column Order**
  * The “Random” option is now shown in the block’s feature section’s dropdown!
* **Advanced Pagination Settings**
  * The “Show more” button shows better scrolling behavior when using it with long tables!
* **Column Filter Dropdowns**
  * The filtering dropdowns can now be used with Server-side Processing, making it a great choice for large tables!
* **Individual Column Filtering**
  * Besides text input fields, the dropdowns are now also supported when using Server-side Processing!
* **Inverted Filtering**
  * Large tables now benefit from speed improvements, when using Server-side Processing and Inverted Filtering!

**Behind the scenes**

* Several minor bugs and inconsistencies have been fixed and improved!
* Cleaned up and simplified code, for easier future maintenance, to follow WordPress Coding Standards, and to offer helpful inline documentation.
* Updated external libraries to benefit from enhancements and bug fixes.
* Automated code compatibility checks and build tools simplify chores for easier development.
* Improved support for PHP 8.4.

**Premium versions**

* Even more great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. [Go check them out!](https://tablepress.org/premium/?utm_source=wordpress.org&utm_medium=textlink&utm_content=readme)

== Upgrade Notice ==

= 3.2.5 =
This update is a security, maintenance, and compatibility release. Updating is highly recommended!

= 3.2 =
This update is a feature, stability, maintenance, and compatibility release. Updating is highly recommended!
