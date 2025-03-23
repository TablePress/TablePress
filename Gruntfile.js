/*
 * TablePress Grunt configuration.
 *
 * Performs syntax checks for CSS, JS, and JSON files.
 * To run, use "npm install" and "npm run grunt lint" in the main plugin folder.
 */

/* jshint node: true */

module.exports = function ( grunt ) {
	// Load tasks.
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );

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
				quotmark: 'single',
				undef: true,
				unused: true,
				browser: true,
				globals: {},
			},
			all: {
				src: [
					'**/*.js',
					'!**/*.min.js',
					// Exclude files that contain JSX from jshint checking.
					'!admin/js/add.js',
					'!admin/js/common/ajax-request.js',
					'!admin/js/common/react-loader.js',
					'!admin/js/edit.js',
					'!admin/js/edit/buttons.js',
					'!admin/js/edit/datatables-features.js',
					'!admin/js/edit/other-actions.js',
					'!admin/js/edit/table-information.js',
					'!admin/js/edit/table-manipulation.js',
					'!admin/js/edit/table-preview.js',
					'!admin/js/edit/table-options.js',
					'!admin/js/export.js',
					'!admin/js/import.js',
					'!admin/js/list.js',
					'!blocks/**/src/edit.js',
					'!blocks/**/src/save.js',
					// Exclude build JS files from jshint checking.
					'!admin/js/build/*.js',
					'!blocks/**/build/index.js',
					// Exclude external libraries and scripts from jshint checking.
					'!admin/js/jspreadsheet.js',
					'!admin/js/jsuites.js',
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
	} );

	// Register "lint" task.
	grunt.registerTask( 'lint', [ 'jshint:all', 'jsonlint:all' ] );

	// Make "lint" the default task.
	grunt.registerTask( 'default', [ 'lint' ] );
};
