/*
 * TablePress Grunt configuration
 *
 * Performs syntax checks and minifies CSS and JS files.
 * To run, use "npm install" and "grunt build" in the main plugin folder.
 * Running just "grunt" will run the "watch" task, which will automatically
 * lint and minify all changed CSS or JS files.
 */

/* jshint node: true */

module.exports = function( grunt ) {
	var autoprefixer = require( 'autoprefixer' );

	// Load tasks
	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	// Task configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// Syntax validation of JavaScript files
		jsvalidate: {
			options: {
				globals: {},
				esprimaOptions: {},
				verbose: false
			},
			all: {
				src: [
						'**/*.js',
						'!node_modules/**/*.js',
						'!vendor/**/*.js'
				]
			},
			changed: {
				src: []
			}
		},

		// JavaScript coding style validation
		jshint: {
			options: '<%= pkg.jshintConfig %>',
			all: {
				src: [
					'<%= jsvalidate.all.src %>',
					'!**/*.min.js'
				]
			},
			changed: {
				src: []
			}
		},

		// JavaScript minification
		uglify: {
			options: {
				ASCIIOnly: true,
				screwIE8: false
			},
			all: {
				expand: true,
				ext: '.min.js',
				extDot: 'last',
				src: [
					'<%= jshint.all.src %>',
					'!Gruntfile.js'
				]
			},
			changed: {
				expand: true,
				ext: '.min.js',
				extDot: 'last',
				src: []
			}
		},

		// Validation of JSON files
		jsonlint: {
			all: {
				src: [
					'**/*.json',
					'!node_modules/**/*.json',
					'!vendor/**/*.json'
				]
			},
			changed: {
				src: []
			}
		},

		// CSS vendor autoprefixing
		postcss: {
			options: {
				processors: [
					autoprefixer( {
						cascade: false
					} )
				]
			},
			all: {
				src: [
					'<%= csslint.all.src %>'
				]
			},
			changed: {
				src: []
			}
		},

		// CSS syntax validation
		csslint: {
			options: {
				'important': false,
				'ids': false,
				'regex-selectors': false,
				'unqualified-attributes': false,
				'outline-none': false,
				'box-model': false,
				'display-property-grouping': false,
				'adjoining-classes': false,
				'empty-rules': false,
				'overqualified-elements': false,
				'known-properties': false,
				'compatible-vendor-prefixes': false,
				'order-alphabetical': false,
				'universal-selector': false,
				'bulletproof-font-face': false,
				'box-sizing': false
			},
			all: {
				src: [
					'**/*.css',
					'!**/*.min.css',
					'!node_modules/**/*.css',
					'!vendor/**/*.css'
				]
			},
			changed: {
				src: []
			}
		},

		// CSS minification
		cssmin: {
			options: {
				removeEmpty: true
			},
			all: {
				expand: true,
				ext: '.min.css',
				extDot: 'last',
				src: [
					'<%= csslint.all.src %>'
				]
			},
			changed: {
				expand: true,
				ext: '.min.css',
				extDot: 'last',
				src: []
			}
		},

		// Watch files for changes
		watch: {
			options: {
				event: [ 'changed' ],
				spawn: false
			},
			js: {
				files: '<%= jshint.all.src %>',
				tasks: [ 'jshint:changed', 'uglify:changed', 'jsvalidate:changed' ]
			},
			json: {
				files: '<%= jsonlint.all.src %>',
				tasks: [ 'jsonlint:changed' ]
			},
			css: {
				files: '<%= csslint.all.src %>',
				tasks: [ 'postcss:changed', 'csslint:changed', 'cssmin:changed' ]
			}
		}
	} );

	// Register "build" task
	grunt.registerTask( 'build:js', [ 'jshint:all', 'jsonlint:all', 'uglify:all', 'jsvalidate:all' ] );
	grunt.registerTask( 'build:css', [ 'postcss:all', 'csslint:all', 'cssmin:all' ] );
	grunt.registerTask( 'build', [ 'build:js', 'build:css' ] );

	// Make "watch" the default task
	grunt.registerTask( 'default', [ 'watch' ] );

	// Add a listener to the "watch" task
	//
	// On "watch", automatically updates the "changed" target for the task configurations,
	// so that only the changed files are touched by the task.
	grunt.event.on( 'watch', function( action, filepath /*, target */ ) {
		grunt.config( [ 'jsvalidate', 'changed', 'src' ], filepath );
		grunt.config( [ 'jshint', 'changed', 'src' ], filepath );
		grunt.config( [ 'uglify', 'changed', 'src' ], filepath );
		grunt.config( [ 'jsonlint', 'changed', 'src' ], filepath );
		grunt.config( [ 'postcss', 'changed', 'src' ], filepath );
		grunt.config( [ 'csslint', 'changed', 'src' ], filepath );
		grunt.config( [ 'cssmin', 'changed', 'src' ], filepath );
	} );

};
