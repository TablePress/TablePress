module.exports = function( grunt ) {

	// Task configuration
	grunt.initConfig( {
		jshint: {
			all: [
				'Gruntfile.js',
				'<%= uglify.all.src %>'
			],
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

		cssmin: {
			all: {
				expand: true,
				ext: '.min.css',
				src: [
					'css/*.css',
					'admin/*.css',
					// Exceptions
					'!css/*.min.css',
					'!admin/*.min.css',
				]
			}
		},

		watch: {
			js: {
				files: '<%= jshint.all %>',
				tasks: [ 'jshint', 'uglify' ]
			},
			css: {
				files: '<%= cssmin.all.src %>',
				tasks: [ 'cssmin' ]
			}
		}
	} );

	// Load tasks
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	// Register tasks
	grunt.registerTask( 'build', [ 'jshint', 'cssmin', 'uglify' ] );

	// Default task
	grunt.registerTask( 'default', [ 'build' ] );
	// grunt.registerTask( 'default', [ 'watch' ] );

};