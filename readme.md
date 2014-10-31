# [TablePress](https://tablepress.org/) [![Flattr TablePress](http://api.flattr.com/button/flattr-badge-large.png)](http://flattr.com/thing/783658/TablePress) [![Donate with PayPal](https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5XDSNLGYWMVV2) [![Build Status](https://travis-ci.org/TobiasBg/TablePress.png)](https://travis-ci.org/TobiasBg/TablePress)

TablePress is a plugin for the [WordPress](https://wordpress.org/) publishing platform.

TablePress allows you to easily create and manage beautiful tables. You can embed the tables into posts, pages, or text widgets with a simple Shortcode. Table data can be edited in a speadsheet-like interface, so no coding is necessary. Tables can contain any type of data, even formulas that will be evaluated. An additional JavaScript library adds features like sorting, pagination, filtering, and more for site visitors. Tables can be imported and exported from/to Excel, CSV, HTML, and JSON files.

Please visit the plugin website at https://tablepress.org/ for the latest information on this plugin, or [follow @TablePress](https://twitter.com/TablePress) on Twitter.

## Screenshots

Screenshots of the TablePress interface are available at https://wordpress.org/plugins/tablepress/screenshots/.

## Installation

The easiest way to install TablePress is via your WordPress Dashboard. Go to the "Plugins" screen, click "Add New", and search for "TablePress" in the WordPress Plugin Directory. Then, click "Install Now" and the following steps will be done for you automatically. After the installation, you'll just have to activate the TablePress plugin.

Manual installation works just as for other WordPress plugins:

1. [Download](https://downloads.wordpress.org/plugin/tablepress.latest-stable.zip) and extract the ZIP file.
1. Move the folder "tablepress" into the "wp-content/plugins/" directory of your WordPress installation.
1. Activate the plugin "TablePress" on the "Plugins" screen of your WordPress Dashboard.
1. Create and manage tables by going to the "TablePress" screen in the admin menu.
1. Add a table to a page, post, or text widget, by embedding the Shortcode `[table id=<your-table's-ID> /]` into its content, or by using the "Table" button in the editor toolbar.
1. You can change the table styling by using CSS code, which can be entered into the "Custom CSS" textarea on the "Plugin Options" screen.

## Supporting future development

If you like the TablePress plugin, please rate and review it in the [WordPress Plugin Directory](https://wordpress.org/support/view/plugin-reviews/tablepress), support it with your [donation](https://tablepress.org/donate/), or [flattr it](https://flattr.com/thing/783658/TablePress). Thank you!

[![Flattr TablePress](http://api.flattr.com/button/button-static-50x60.png)](http://flattr.com/thing/783658/TablePress) [![Donate with PayPal](https://www.paypal.com/en_US/i/btn/x-click-but04.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5XDSNLGYWMVV2) [![Support TablePress on Gratipay](https://assets.gratipay.com/-/gratipay.png)](https://gratipay.com/TobiasBg/)

## Frequently Asked Questions

**Where can I find answers to Frequently Asked Questions?**

Many questions, regarding different features or styling, have been answered on the [FAQ page](https://tablepress.org/faq/) on the plugin website.

**Support?**

For support questions, bug reports, or feature requests, please use the [WordPress Support Forums](https://wordpress.org/support/plugin/tablepress). Please [search](https://wordpress.org/support/) through the forums first, and only [open a new thread](https://wordpress.org/support/plugin/tablepress) if you don't find an existing answer. Thank you!

**Requirements?**

In short: WordPress 4.0 or higher, while the latest version of WordPress is always recommended.

**Languages and Localization?**

The plugin currently comes with the following translations:

Brazilian Portuguese, Chinese (Simplified), Chinese (Taiwan), Czech, Dutch, English, Finnish, French, German, Hebrew, Icelandic, Italian, Japanese, Latvian, Polish, Russian, Serbian, Slovak, Spanish, Turkish and Ukrainian.

Translations into other languages are always welcome! With the [Codestyling Localization](https://wordpress.org/plugins/codestyling-localization/) plugin, that's really easy. Just install the plugin, add your language, create the .po file, translate the strings, and create the .mo file. It will automatically be saved into the TablePress plugin folder. If you send me the .mo and .po files, I will gladly include them into future plugin releases.

There is also a .pot file available in the "i18n" subfolder, which can be used e.g. with the [poEdit](http://www.poedit.net/) editor.

**Migration from WP-Table Reloaded**

TablePress is the official successor of the WP-Table Reloaded plugin. It has been rewritten from the ground up and uses an entirely new internal structure. This fixes some major flaws of WP-Table Reloaded and prepares the plugin for easier, safer, and better future development.
If you are currently using WP-Table Reloaded, it is highly recommended that you switch to TablePress. WP-Table Reloaded will no longer be maintained or developed. For further information on how to switch from WP-Table Reloaded to TablePress, please see the [migration guide](https://tablepress.org/migration-from-wp-table-reloaded/) on the plugin website.

## Usage

After installing the plugin, you can create and manage tables on the "TablePress" screen in the WordPress Dashboard.
Everything should be self-explaining there.

To show one of your tables in a post, on a page, or in a text widget, just embed the Shortcode `[table id=<the-ID> /]` into the post/page/text widget, where `<the-ID>` is the ID of your table (can be found on the left side of the "All Tables" screen.)
Alternatively, you can also insert tables by clicking the "Table" button in the editor toolbar, and then selecting the desired table.

After that, you might want to change the styling of the table. You can do this by entering CSS commands into the "Custom CSS" textarea on the "Plugin Options" screen.

You may also add certain features (like sorting, pagination, filtering, alternating row colors, row highlighting, print name and/or description, ...) by enabling the corresponding checkboxes on a table's "Edit" screen.

## Acknowledgements

Special thanks go to [Allan Jardine](http://www.sprymedia.co.uk/) for the [DataTables JavaScript library](http://www.datatables.net/).

Thanks to all language file translators!

Thanks to every donor, supporter, and bug reporter!

## License

This plugin is Free Software, released and licensed under the GPL, version 2 (http://www.gnu.org/licenses/gpl-2.0.html).
You may use it free of charge for any purpose.

## Changelog

A changelog is available at https://wordpress.org/plugins/tablepress/changelog/.
