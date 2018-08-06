<?php declare(strict_types = 1);

namespace Contributte\Cache\Storages;

use Nette\Caching\IStorage;

class ChainStorage implements IStorage
{

	/** @var IStorage[] */
	private $storages;

	/**
	 * @param IStorage[] $storages
	 */
	public function __construct(array $storages)
	{
		$this->storages = $storages;
	}

	/**
	 * Read from cache.
	 *
	 * @param string $key
	 * @return mixed|null
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function read($key)
	{
		/**
		 * @var IStorage[] $emptyStorages
		 * List of all storages, where were data not found (and were defined before storage, where data were found)
		 * Used to write these data into storages, which has higher priority to load them faster on second access
		 */
		$emptyStorages = [];
		$found = false;
		$returned = null;

		foreach ($this->storages as $storage) {
			$data = $storage->read($key);
			if ($data !== null) {
				$returned = $data;
				$found = true;
				break;
			}
			$emptyStorages[] = $storage;
		}

		if ($found) {
			foreach ($emptyStorages as $storage) {
				$storage->write($key, $data, []); //todo - how to get $dependencies?
			}
		}

		return $returned;
	}

	/**
	 * Prevents item reading and writing. Lock is released by write() or remove().
	 *
	 * @param string $key
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function lock($key): void
	{
		foreach ($this->storages as $storage) {
			$storage->lock($key);
		}
	}

	/**
	 * Writes item into the cache.
	 *
	 * @param string  $key
	 * @param mixed   $data
	 * @param mixed[] $dependencies
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function write($key, $data, array $dependencies): void
	{
		foreach ($this->storages as $storage) {
			$storage->write($key, $data, $dependencies);
		}
	}

	/**
	 * Removes item from the cache.
	 *
	 * @param string $key
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function remove($key): void
	{
		foreach ($this->storages as $storage) {
			$storage->remove($key);
		}
	}

	/**
	 * Removes items from the cache by conditions.
	 *
	 * @param mixed[] $conditions
	 */
	public function clean(array $conditions): void
	{
		foreach ($this->storages as $storage) {
			$storage->clean($conditions);
		}
	}

}
