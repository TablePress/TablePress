import { defineConfig } from 'eslint/config';
import wpScriptsConfig from './node_modules/@wordpress/scripts/config/eslint.config.cjs';

export default defineConfig( [
	...wpScriptsConfig,
	{
		ignores: [
			// Ignore external libraries.
			'libraries/composer/',
			'libraries/freemius/',
			'libraries/vendor/',
			'vendor/',
			// Ignore minified files.
			'admin/js/build/',
			'blocks/**/build/',
			// Ignore external scripts.
			'admin/js/jspreadsheet.js',
			'admin/js/jsuites.js',
			'js/jquery.datatables.min.js',
		],
	},
	{
		rules: {
			camelcase: "off",
			"import/no-extraneous-dependencies": "off",
			"import/no-unresolved": "off",
			"no-alert": "off",
			"no-redeclare": "off",
			"no-undef": "off",
			"no-useless-return": "off",
			"prettier/prettier": "off",
			"@wordpress/i18n-hyphenated-range": "off",
			"@wordpress/i18n-translator-comments": "off",
			"jsdoc/check-tag-names": "off",
			"jsdoc/empty-tags": "off",
			"jsdoc/reject-function-type": "off",
		},
	},
] );
