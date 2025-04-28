<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Collection\Memory;

use DateInterval;
use TablePress\Psr\SimpleCache\CacheInterface;

/**
 * This is the default implementation for in-memory cell collection.
 *
 * Alternative implementation should leverage off-memory, non-volatile storage
 * to reduce overall memory usage.
 */
class SimpleCache3 implements CacheInterface
{
	private array $cache = [];

	public function clear(): bool
	{
		$this->cache = [];

		return true;
	}

	public function delete(string $key): bool
	{
		unset($this->cache[$key]);

		return true;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		foreach ($keys as $key) {
			$this->delete($key);
		}

		return true;
	}

	/**
				 * @param mixed $default
				 * @return mixed
				 */
				public function get(string $key, $default = null)
	{
		if ($this->has($key)) {
			return $this->cache[$key];
		}

		return $default;
	}

	/**
				 * @param mixed $default
				 */
				public function getMultiple(iterable $keys, $default = null): iterable
	{
		$results = [];
		foreach ($keys as $key) {
			$results[$key] = $this->get($key, $default);
		}

		return $results;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->cache);
	}

	/**
				 * @param null|int|\DateInterval $ttl
				 * @param mixed $value
				 */
				public function set(string $key, $value, $ttl = null): bool
	{
		$this->cache[$key] = $value;

		return true;
	}

	/**
				 * @param null|int|\DateInterval $ttl
				 */
				public function setMultiple(iterable $values, $ttl = null): bool
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value);
		}

		return true;
	}
}
