{
	"name": "@tablepress/tablepress",
	"version": "2.2.4",
	"description": "Embed beautiful and feature-rich tables into your posts and pages, without having to write code.",
	"author": "Tobias Bäthge",
	"license": "GPL-2.0-only",
	"private": true,
	"keywords": [
		"wordpress",
		"plugin",
		"table"
	],
	"homepage": "https://tablepress.org/",
	"funding": "https://tablepress.org/donate/",
	"repository": "github:TablePress/TablePress",
	"bugs": "https://github.com/TablePress/TablePress/issues",
	"devDependencies": {
		"@wordpress/icons": "^9.36.0",
		"@wordpress/scripts": "^26.16.0",
		"autoprefixer": "^9.8.8",
		"grunt": "^1.6.1",
		"grunt-contrib-csslint": "^2.0.0",
		"grunt-contrib-jshint": "^3.2.0",
		"grunt-jsonlint": "^2.1.3",
		"grunt-postcss": "^0.9.0",
		"matchdep": "^2.0.0",
		"webpack-remove-empty-scripts": "^1.0.4"
	},
	"scripts": {
		"grunt": "grunt",
		"prebuild": "grunt lint && wp-scripts lint-js && wp-scripts lint-style",
		"build": "wp-scripts build",
		"postbuild": "find ./admin/css/build/ ./css/build/ -type f -name \"*.css\" -exec sed -i '' 's||\\\\f053|g;s||\\\\f054|g;s||\\\\f0dc|g;s||\\\\f0d8|g;s||\\\\f0d7|g;s|●|\\\\25cf|g;s|✓|\\\\2713|g' {} +",
		"build:block": "wp-scripts build --config-name tablepress-block",
		"build:css": "wp-scripts build --config-name tablepress-admin-css && wp-scripts build --config-name tablepress-frontend-css",
		"build:js": "wp-scripts build --config-name tablepress-admin-js",
		"build-dev": "DEVELOP=true npm run build",
		"format": "wp-scripts format",
		"lint:css": "wp-scripts lint-style",
		"lint:js": "wp-scripts lint-js",
		"start": "DEVELOP=true wp-scripts start",
		"packages-update": "wp-scripts packages-update"
	}
}
