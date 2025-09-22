/*
 * TablePress webpack configuration, part of the @wordpress/scripts workflow.
 *
 * Performs syntax checks and minifies CSS and JS files.
 * To run, use "npm install" and "npm run build" in the main plugin folder.
 * Running "npm run start" will run a watch task, which will automatically
 * lint and minify all changed CSS or JS files.
 */

/* jshint node: true */

/**
 * External dependencies.
 */
const MiniCSSExtractPlugin = require( 'mini-css-extract-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const { EnvironmentPlugin } = require( 'webpack' );
const glob = require( 'glob' );

/**
 * WordPress dependencies.
 */
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

/**
 * Configuration for the TablePress table block.
 */
const blockConfig = {
	...defaultConfig,
	name: 'tablepress-block',
	context: __dirname,
	entry: {
		index: './blocks/table/src/index.js',
	},
	output: {
		filename: '[name].js',
		path: `${ __dirname }/blocks/table/build`,
	},
	// Override @wordpress/scripts default plugins, so that the build/index.php file is kept.
	plugins: [
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ '**/*', '!index.php' ],
			cleanAfterEveryBuildPatterns: [ '!fonts/**', '!images/**' ],
		} ),
		new MiniCSSExtractPlugin( { filename: '[name].css' } ),
		new DependencyExtractionWebpackPlugin(),
		new EnvironmentPlugin( {
			DEVELOP: false,
		} ),
	],
};

/**
 * Configuration for the TablePress admin JavaScript files.
 */
const adminJsConfig = {
	...defaultConfig,
	name: 'tablepress-admin-js',
	context: __dirname,
	entry:
		// Find all .js files in ./admin/js and construct an "entry" object ( { name: <file> } ).
		glob.sync(
			'*.js',
			{
				cwd: './admin/js/',
			}
		).reduce(
			( entries, path ) => {
				// Skip library files.
				if ( path.endsWith( '.min.js' ) ) {
					return entries;
				}
				const name = path.replace( '.js', '' );
				entries[ name ] = `./admin/js/${ name }.js`;
				return entries;
			},
			{}
		),
	output: {
		filename: '[name].js',
		path: `${ __dirname }/admin/js/build`,
	},
	// Override @wordpress/scripts default plugins, so that the build/index.php file is kept.
	plugins: [
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ '**/*', '!index.php' ],
		} ),
		new DependencyExtractionWebpackPlugin(),
		new EnvironmentPlugin( {
			DEVELOP: false,
		} ),
	],
};

/**
 * Configuration for the TablePress admin CSS files.
 */
const adminCssConfig = {
	...defaultConfig,
	name: 'tablepress-admin-css',
	context: __dirname,
	entry:
		// Find all .scss and .css files in ./admin/css and construct an "entry" object ( { name: <file> } ).
		glob.sync(
			'[^_]*.?(s)css',
			{
				cwd: './admin/css/',
			}
		).reduce(
			( entries, path ) => {
				const name = path.replace( '.scss', '' ).replace( '.css', '' );
				entries[ name ] = `./admin/css/${ path }`;
				return entries;
			},
			{}
		),
	output: {
		filename: '[name].js', // File extension .js is necessary here, as a temporary .js file is created.
		path: `${ __dirname }/admin/css/build`,
	},
	// Override @wordpress/scripts default plugins, so that the build/index.php file is kept and to remove empty dummy .js files.
	plugins: [
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ '**/*', '!index.php' ],
		} ),
		new MiniCSSExtractPlugin( { filename: '[name].css' } ),
		new RemoveEmptyScriptsPlugin(),
	],
};

/**
 * Configuration for the TablePress frontend CSS files.
 */
const frontendCssConfig = {
	...defaultConfig,
	name: 'tablepress-frontend-css',
	context: __dirname,
	entry:
		// Find all .scss and .css files in ./css and construct an "entry" object ( { name: <file> } ).
		glob.sync(
			'[^_]*.?(s)css',
			{
				cwd: './css/',
			}
		).reduce(
			( entries, path ) => {
				const name = path.replace( '.scss', '' ).replace( '.css', '' );
				entries[ name ] = `./css/${ path }`;
				return entries;
			},
			{}
		),
	output: {
		filename: '[name].js', // File extension .js is necessary here, as a temporary .js file is created.
		path: `${ __dirname }/css/build`,
	},
	// Override @wordpress/scripts default plugins, so that the build/index.php file is kept and to remove empty dummy .js files.
	plugins: [
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ '**/*', '!index.php' ],
		} ),
		new MiniCSSExtractPlugin( { filename: '[name].css' } ),
		new RemoveEmptyScriptsPlugin(),
	],
};

/**
 * Add a configuration to add a polyfill for the React JSX runtime.
 * WP 6.6 switched to a new mechanism for loading React, which requires this polyfill for older versions of WordPress.
 * Once WP 6.6 is the minimum required version for TablePress, this can be removed, and `clean-webpack-plugin` can be updated to `^4.0`.
 *
 * @see https://make.wordpress.org/core/2024/06/06/jsx-in-wordpress-6-6/
 * @see https://github.com/WordPress/gutenberg/issues/62202#issuecomment-2156796649
 */
const reactJSXRuntimePolyfill = {
	entry: {
		'react-jsx-runtime': {
			import: 'react/jsx-runtime',
		},
	},
	output: {
		filename: 'react-jsx-runtime.min.js',
		path: `${ __dirname }/admin/js`,
		library: {
			name: 'ReactJSXRuntime',
			type: 'window',
		},
	},
	externals: {
		react: 'React',
	},
	plugins: [
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [],
			cleanAfterEveryBuildPatterns: [ 'react-jsx-runtime.min.js.LICENSE.txt' ],
		} ),
	],
};

/**
 * Export all configs, which are then treated separately by webpack.
 */
module.exports = [
	blockConfig,
	adminJsConfig,
	adminCssConfig,
	frontendCssConfig,
	reactJSXRuntimePolyfill,
];
