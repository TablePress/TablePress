/*
 * TablePress Grunt configuration.
 *
 * Performs syntax checks for CSS, JS, and JSON files.
 * To run, use "npm install" and "npm run grunt lint" in the main plugin folder.
 */

/* jshint node: true */

module.exports = function ( grunt ) {
	const autoprefixer = require( 'autoprefixer' );

	// Load tasks.
	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	// Task configuration.
	grunt.initConfig( {
		// JavaScript coding style validation.
		jshint: {
			options: {
				boss: true,
				curly: true,
				eqeqeq: true,
				eqnull: true,
				esversion: 11,
				expr: true,
				immed: true,
				noarg: true,
				nonbsp: true,
				onevar: true,
				quotmark: 'single',
				trailing: true,
				undef: true,
				unused: true,
				browser: true,
				globals: {},
			},
			all: {
				src: [
					'**/*.js',
					// Exclude files that contain JSX from jshint checking.
					'!admin/js/common/react-loader.js',
					'!admin/js/export.js',
					'!admin/js/export/*.js',
					'!admin/js/import.js',
					'!admin/js/import/*.js',
					'!blocks/**/src/edit.js',
					'!blocks/**/src/save.js',
					// Exclude built JS files from jshint checking.
					'!admin/js/build/*.js',
					'!blocks/**/build/index.js',
					// Exclude external libraries and scripts from jshint checking.
					'!admin/js/jspreadsheet.js',
					'!admin/js/jsuites.js',
					'!js/jquery.datatables.min.js',
					'!libraries/composer/**/*',
					'!libraries/freemius/**/*',
					'!libraries/vendor/**/*',
					'!node_modules/**/*',
					'!vendor/**/*',
				],
			},
		},

		// Validation of JSON files.
		jsonlint: {
			all: {
				src: [
					'**/*.json',
					// Explicitly add hidden files.
					'.stylelintrc.json',
					// Exclude external JSON files from jsonlint checking.
					'!libraries/composer/**/*',
					'!libraries/freemius/**/*',
					'!libraries/vendor/**/*',
					'!node_modules/**/*',
					'!vendor/**/*',
				],
			},
		},

		// CSS vendor autoprefixing.
		postcss: {
			options: {
				processors: [
					autoprefixer( {
						cascade: false,
					} ),
				],
			},
			all: {
				src: [
					'**/*.scss',
					'**/*.css',
					'!admin/css/build/*.css',
					'!blocks/**/build/*.css',
					'!css/build/*.css',
					// Exclude .scss files that use features that postcss does not understand.
					'!blocks/table/src/editor.scss',
					'!css/_default-datatables.scss',
					// Exclude external libraries from autoprefixing and csslint checking.
					'!libraries/composer/**/*',
					'!libraries/freemius/**/*',
					'!libraries/vendor/**/*',
					'!node_modules/**/*',
					'!vendor/**/*',
				],
			},
		},

		// CSS syntax validation.
		csslint: {
			options: {
				'adjoining-classes': false,
				'box-model': false,
				'display-property-grouping': false,
				ids: false,
				important: false,
				'known-properties': false,
				'order-alphabetical': false,
				'outline-none': false,
				'overqualified-elements': false,
				'universal-selector': false,
				// These only apply to old versions of IE and are not relevant.
				'bulletproof-font-face': false,
				'fallback-colors': false,
			},
			all: {
				src: [
					'<%= postcss.all.src %>',
					// Exclude .scss files that use features that csslint does not understand.
					'!admin/css/codemirror.scss',
					'!admin/css/common.scss',
					'!admin/css/common-rtl.scss',
					'!admin/css/edit.scss',
					'!admin/css/edit-features.scss',
					'!admin/css/import.scss',
					'!admin/css/_spinner-alert.scss',
					'!css/default.scss',
					'!css/default-rtl.scss',
					'!css/_default-core.scss',
					'!css/_default-datatables.scss',
					// Exclude external libraries from csslint checking.
					'!admin/css/jspreadsheet.css',
					'!admin/css/jsuites.css',
				],
			},
		},
	} );

	// Register "lint" task.
	grunt.registerTask( 'lint:js', [ 'jshint:all', 'jsonlint:all' ] );
	grunt.registerTask( 'lint:css', [ 'postcss:all', 'csslint:all' ] );
	grunt.registerTask( 'lint', [ 'lint:js', 'lint:css' ] );

	// Make "lint" the default task.
	grunt.registerTask( 'default', [ 'lint' ] );
};
