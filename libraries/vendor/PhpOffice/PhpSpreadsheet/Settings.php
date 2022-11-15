<?php

namespace TablePress\PhpOffice\PhpSpreadsheet;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Chart\Renderer\IRenderer;
use TablePress\PhpOffice\PhpSpreadsheet\Collection\Memory;
use TablePress\Psr\Http\Client\ClientInterface;
use TablePress\Psr\Http\Message\RequestFactoryInterface;
use TablePress\Psr\SimpleCache\CacheInterface;
use ReflectionClass;

class Settings
{
	/**
	 * Class name of the chart renderer used for rendering charts
	 * eg: TablePress\PhpOffice\PhpSpreadsheet\Chart\Renderer\JpGraph.
	 *
	 * @var ?string
	 */
	private static $chartRenderer;

	/**
	 * Default options for libxml loader.
	 *
	 * @var ?int
	 */
	private static $libXmlLoaderOptions;

	/**
	 * Allow/disallow libxml_disable_entity_loader() call when not thread safe.
	 * Default behaviour is to do the check, but if you're running PHP versions
	 *      7.2 < 7.2.1
	 * then you may need to disable this check to prevent unwanted behaviour in other threads
	 * SECURITY WARNING: Changing this flag is not recommended.
	 *
	 * @var bool
	 */
	private static $libXmlDisableEntityLoader = true;

	/**
	 * The cache implementation to be used for cell collection.
	 *
	 * @var ?CacheInterface
	 */
	private static $cache;

	/**
	 * The HTTP client implementation to be used for network request.
	 *
	 * @var null|ClientInterface
	 */
	private static $httpClient;

	/**
	 * @var null|RequestFactoryInterface
	 */
	private static $requestFactory;

	/**
	 * Set the locale code to use for formula translations and any special formatting.
	 *
	 * @param string $locale The locale code to use (e.g. "fr" or "pt_br" or "en_uk")
	 *
	 * @return bool Success or failure
	 */
	public static function setLocale(string $locale)
	{
		return Calculation::getInstance()->setLocale($locale);
	}

	public static function getLocale(): string
	{
		return Calculation::getInstance()->getLocale();
	}

	/**
	 * Identify to PhpSpreadsheet the external library to use for rendering charts.
	 *
	 * @param string $rendererClassName Class name of the chart renderer
	 *    eg: TablePress\PhpOffice\PhpSpreadsheet\Chart\Renderer\JpGraph
	 */
	public static function setChartRenderer(string $rendererClassName): void
	{
		if (!is_a($rendererClassName, IRenderer::class, true)) {
			throw new Exception('Chart renderer must implement ' . IRenderer::class);
		}

		self::$chartRenderer = $rendererClassName;
	}

	/**
	 * Return the Chart Rendering Library that PhpSpreadsheet is currently configured to use.
	 *
	 * @return null|string Class name of the chart renderer
	 *    eg: TablePress\PhpOffice\PhpSpreadsheet\Chart\Renderer\JpGraph
	 */
	public static function getChartRenderer(): ?string
	{
		return self::$chartRenderer;
	}

	public static function htmlEntityFlags(): int
	{
		return \ENT_COMPAT;
	}

	/**
	 * Set default options for libxml loader.
	 *
	 * @param ?int $options Default options for libxml loader
	 */
	public static function setLibXmlLoaderOptions($options): int
	{
		if ($options === null) {
			$options = defined('LIBXML_DTDLOAD') ? (LIBXML_DTDLOAD | LIBXML_DTDATTR) : 0;
		}
		self::$libXmlLoaderOptions = $options;

		return $options;
	}

	/**
	 * Get default options for libxml loader.
	 * Defaults to LIBXML_DTDLOAD | LIBXML_DTDATTR when not set explicitly.
	 *
	 * @return int Default options for libxml loader
	 */
	public static function getLibXmlLoaderOptions(): int
	{
		if (self::$libXmlLoaderOptions === null) {
			return self::setLibXmlLoaderOptions(null);
		}

		return self::$libXmlLoaderOptions;
	}

	/**
	 * Enable/Disable the entity loader for libxml loader.
	 * Allow/disallow libxml_disable_entity_loader() call when not thread safe.
	 * Default behaviour is to do the check, but if you're running PHP versions
	 *      7.2 < 7.2.1
	 * then you may need to disable this check to prevent unwanted behaviour in other threads
	 * SECURITY WARNING: Changing this flag to false is not recommended.
	 *
	 * @param bool $state
	 */
	public static function setLibXmlDisableEntityLoader(/** @scrutinizer ignore-unused */ $state): void
	{
		self::$libXmlDisableEntityLoader = (bool) $state;
	}

	/**
	 * Return the state of the entity loader (disabled/enabled) for libxml loader.
	 *
	 * @return bool $state
	 */
	public static function getLibXmlDisableEntityLoader(): bool
	{
		return self::$libXmlDisableEntityLoader;
	}

	/**
	 * Sets the implementation of cache that should be used for cell collection.
	 */
	public static function setCache(CacheInterface $cache): void
	{
		self::$cache = $cache;
	}

	/**
	 * Gets the implementation of cache that is being used for cell collection.
	 */
	public static function getCache(): CacheInterface
	{
		if (!self::$cache) {
			self::$cache = self::useSimpleCacheVersion3() ? new Memory\SimpleCache3() : new Memory\SimpleCache1();
		}

		return self::$cache;
	}

	public static function useSimpleCacheVersion3(): bool
	{
		return
			PHP_MAJOR_VERSION === 8 &&
			(new ReflectionClass(CacheInterface::class))->getMethod('get')->getReturnType() !== null;
	}

	/**
	 * Set the HTTP client implementation to be used for network request.
	 */
	public static function setHttpClient(ClientInterface $httpClient, RequestFactoryInterface $requestFactory): void
	{
		self::$httpClient = $httpClient;
		self::$requestFactory = $requestFactory;
	}

	/**
	 * Unset the HTTP client configuration.
	 */
	public static function unsetHttpClient(): void
	{
		self::$httpClient = null;
		self::$requestFactory = null;
	}

	/**
	 * Get the HTTP client implementation to be used for network request.
	 */
	public static function getHttpClient(): ClientInterface
	{
		if (!self::$httpClient || !self::$requestFactory) {
			throw new Exception('HTTP client must be configured via Settings::setHttpClient() to be able to use WEBSERVICE function.');
		}

		return self::$httpClient;
	}

	/**
	 * Get the HTTP request factory.
	 */
	public static function getRequestFactory(): RequestFactoryInterface
	{
		if (!self::$httpClient || !self::$requestFactory) {
			throw new Exception('HTTP client must be configured via Settings::setHttpClient() to be able to use WEBSERVICE function.');
		}

		return self::$requestFactory;
	}
}
