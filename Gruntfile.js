module.exports = function( grunt ) {

	// Task Configuration
	grunt.initConfig( {
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
				files: '<%= uglify.all.src %>',
				tasks: [ 'uglify' ]
			},
			css: {
				files: '<%= cssmin.all.src %>',
				tasks: [ 'cssmin' ]
			}
		}
	} );

	// Load tasks
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	// Register tasks
	grunt.registerTask( 'build', [ 'cssmin', 'uglify' ] );

	// Default task
	grunt.registerTask( 'default', [ 'build' ] );
	// grunt.registerTask( 'default', [ 'watch' ] );

};