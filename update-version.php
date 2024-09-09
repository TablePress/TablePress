<?php
/**
 * Helper script that updates the TablePress version number in all configured files.
 *
 * Usage:
 *   php update-version.php <new_version>
 * or
 *   composer update-version <new_version>
 *
 * @package TablePress
 * @subpackage Build tools
 * @author Tobias Bäthge
 * @since 2.4.2
 */

// Check if a new version number was provided and provide usage instructions.
if ( ! isset( $argv[1] ) ) {
	echo "Updates the TablePress version number across configured files.\n\n";
	echo "Usage:\n";
	echo "\tcomposer update-version <new_version>\n";
	echo "or\n";
	echo "\tphp update-version.php <new_version>\n\n";
	echo "Example:\n\tcomposer update-version 3.1\n";
	exit( 1 );
}

// Get new version from command line parameters.
$new_version = $argv[1];

// Append .0 to the x.y version number if it's a major x.y release.
$new_version_long = $new_version;
if ( 1 === substr_count( $new_version_long, '.' ) ) {
	$new_version_long .= '.0';
}

/*
 * Define the replacements to be made in the files.
 *
 * Each entry in the array is an array with the following keys:
 * - search: The regular expression to search for.
 * - replace: The replacement string.
 * - type: (optional) The type of replacement to perform. Currently only 'integer' is supported, which increments the number found in the search pattern (instead of a version replacement).
 */
$replacements = array(
	'blocks/table/block.json'      => array(
		array(
			'search'  => '	"version": "\d+\.\d+\.?\d*",',
			'replace' => "	\"version\": \"{$new_version}\",",
		),
	),
	'classes/class-tablepress.php' => array(
		array(
			'search'  => "	public const version = '\d+\.\d+\.?\d*';",
			'replace' => "	public const version = '{$new_version}';",
		),
		array(
			'type'   => 'integer',
			'search' => '(	public const db_version = )(\d+)(;)',
		),
	),
	'package-lock.json'            => array(
		array(
			'search'  => '	"name": "@tablepress/tablepress",' . "\n" . '	"version": "\d+\.\d+\.?\d*",',
			'replace' => '	"name": "@tablepress/tablepress",' . "\n	\"version\": \"{$new_version_long}\",",
		),
		array(
			'search'  => '			"name": "@tablepress/tablepress",' . "\n" . '			"version": "\d+\.\d+\.?\d*",',
			'replace' => '			"name": "@tablepress/tablepress",' . "\n			\"version\": \"{$new_version_long}\",",
		),
	),
	'package.json'                 => array(
		array(
			'search'  => '	"name": "@tablepress/tablepress",' . "\n" . '	"version": "\d+\.\d+\.?\d*",',
			'replace' => '	"name": "@tablepress/tablepress",' . "\n	\"version\": \"{$new_version_long}\",",
		),
	),
	'readme.txt'                   => array(
		array(
			'search'  => 'Stable tag: \d+\.\d+\.?\d*',
			'replace' => "Stable tag: {$new_version}",
		),
	),
	'tablepress.php'               => array(
		array(
			'search'  => ' \* @version \d+\.\d+\.?\d*',
			'replace' => " * @version {$new_version}",
		),
		array(
			'search'  => ' \* Version: \d+\.\d+\.?\d*',
			'replace' => " * Version: {$new_version}",
		),
	),
);

// Perform the replacements.
foreach ( $replacements as $file => $replacements ) {
	$file = __DIR__ . "/{$file}";
	$content = file_get_contents( $file );
	foreach ( $replacements as $replacement ) {
		if ( isset( $replacement['type'] ) && 'integer' === $replacement['type'] ) {
			$content = preg_replace_callback(
				"#{$replacement['search']}#",
				static function ( $matches ) {
					$increased_version = $matches[2] + 1;
					return $matches[1] . $increased_version . $matches[3];
				},
				$content
			);
		} else {
			$content = (string) preg_replace( "#{$replacement['search']}#", $replacement['replace'], $content );
		}
	}
	file_put_contents( $file, $content );
}
