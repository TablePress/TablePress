const eslintConfig = {
	root: true,
	extends: [ './node_modules/@wordpress/scripts/config/.eslintrc.js' ],
	rules: {
		camelcase: 'off',
		'import/no-extraneous-dependencies': 'off',
		'import/no-unresolved': 'off',
		'no-alert': 'off',
		'no-redeclare': 'off',
		'no-undef': 'off',
		'no-useless-return': 'off',
		'prettier/prettier': 'off',
		'@wordpress/i18n-hyphenated-range': 'off',
		'@wordpress/i18n-translator-comments': 'off',
		'jsdoc/check-tag-names': 'off',
		'jsdoc/empty-tags': 'off',
	},
};

module.exports = eslintConfig;
