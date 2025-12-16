<?php
/**
 * Configuration file for PHP Transpilation using Rector.
 *
 * @package TablePress
 * @subpackage Build tools
 * @author Tobias Bäthge
 * @since 2.1.0
 */

declare(strict_types=1);

return static function ( Rector\Config\RectorConfig $rector_config ): void {
	// Scan paths that contain externally maintained libraries.
	$rector_config->paths( array(
		__DIR__ . '/libraries/vendor',
		__DIR__ . '/modules/libraries/vendor',
	) );

	// Set default indenting.
	$rector_config->indent( "\t", 1 );

	// Downgrade everything to PHP 7.4.
	$rector_config->sets( array(
		Rector\Set\ValueObject\DowngradeLevelSetList::DOWN_TO_PHP_74,
	) );

	// Ignore downgrade rules for functions that WordPress is polyfilling.
	$rector_config->skip( array(
		Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrContainsRector::class, // str_contains().
		Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRector::class, // str_ends_with().
		Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrStartsWithRector::class, // str_starts_with().
	) );

	// Set used (maximum) PHP version. This has to be at the end of the configuration, for some reason.
	$rector_config->phpVersion( Rector\ValueObject\PhpVersion::PHP_85 );
};
