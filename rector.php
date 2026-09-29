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

use Rector\Config\RectorConfig;
use Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrContainsRector;
use Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRector;
use Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrStartsWithRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
	// Scan paths that contain externally maintained libraries.
	->withPaths( array(
		__DIR__ . '/libraries/vendor',
	) )

	// Set default indenting.
	->withIndent( "\t", 1 )

	// Downgrade everything to PHP 7.4.
	->withSets( array(
		DowngradeLevelSetList::DOWN_TO_PHP_74,
	) )

	// Ignore downgrade rules for functions that WordPress is polyfilling.
	->withSkip( array(
		DowngradeStrContainsRector::class, // str_contains().
		DowngradeStrEndsWithRector::class, // str_ends_with().
		DowngradeStrStartsWithRector::class, // str_starts_with().
	) )

	// Set used (maximum) PHP version.
	->withPhpVersion( PhpVersion::PHP_85 );
