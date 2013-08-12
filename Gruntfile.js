module.exports = function( grunt ) {

	// Task configuration
	grunt.initConfig( {
		jshint: {
			all: {
				src: [
					'Gruntfile.js',
					'<%= uglify.all.src %>'
				]
			},
			options: {
				curly:   false,
				eqeqeq:  false,
				immed:   true,
				latedef: true,
				newcap:  true,
				noarg:   true,
				sub:     true,
				undef:   false,
				boss:    true,
				eqnull:  true,
				laxbreak: true,
				globals: {
					exports: true,
					module:  false
				}
			}
		},

		uglify: {
			all: {
				expand: true,
				ext: '.min.js',
				src: [
					'admin/*.js',
					// Exceptions
					'!admin/*.min.js'
				]
			}
		},

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
				'universal-selector': false,
				'bulletproof-font-face': false
			},
			all: {
				src: [
					'css/*.css',
					'admin/*.css',
					// Exceptions
					'!css/*.min.css',
					'!admin/*.min.css'
				]
			},
		},

		cssmin: {
			options: {
				removeEmpty: true
			},
			all: {
				expand: true,
				ext: '.min.css',
				src: [
					'css/*.css',
					'admin/*.css',
					// Exceptions
					'!css/*.min.css',
					'!admin/*.min.css'
				]
			}
		},

		watch: {
			js: {
				files: '<%= jshint.all.src %>',
				tasks: [ 'jshint', 'uglify' ]
			},
			css: {
				files: '<%= cssmin.all.src %>',
				tasks: [ 'csslint', 'cssmin' ]
			}
		}
	} );

	// Load tasks
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-csslint' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	// Register tasks
	grunt.registerTask( 'build', [ 'jshint', 'uglify', 'csslint', 'cssmin' ] );

	// Default task
	grunt.registerTask( 'default', [ 'build' ] );
	// grunt.registerTask( 'default', [ 'watch' ] );

};