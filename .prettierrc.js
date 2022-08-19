// Configuration file for the Prettier JS linting/formatting of @wordpress/scripts.

module.exports = {
	...require( '@wordpress/prettier-config' ),
	printWidth: 250, // This overrides unwanted shortening of lines and extra word-wrapping, to some degree.
};
