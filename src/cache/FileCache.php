<?php

namespace App\cache;

use App\utils;

/**
 * A generic cache system storing items on the filesystem.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FileCache
{
    public function __construct(
        private string $namespace,
    ) {
        if (!is_dir($this->cachePath())) {
            $this->createFolder($this->cachePath());
        }
    }

    /**
     * Returns a CacheItem representing the specified key.
     *
     * The method always returns a CacheItem, even in case of a cache miss. Use
     * CacheItem::isHit() to verify if the item is cached.
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function getItem(string $key): CacheItem
    {
        $item_fullpath = $this->keyToFullpath($key);
        $cache_item = $this->getItemFromPath($item_fullpath);

        if ($cache_item && $cache_item->getKey() === $key) {
            return $cache_item;
        } else {
            return new CacheItem($key, null);
        }
    }

    /**
     * Returns whether the cache contains the cache item or not.
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function hasItem(string $key): bool
    {
        $item_fullpath = $this->keyToFullpath($key);
        return is_file($item_fullpath);
    }

    /**
     * Removes the item from the cache.
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function deleteItem(string $key): bool
    {
        $item_fullpath = $this->keyToFullpath($key);

        if (!file_exists($item_fullpath)) {
            return true;
        }

        return @unlink($item_fullpath);
    }

    /**
     * Removes the items from the cache.
     *
     * @param string[] $keys
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function deleteItems(array $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            $ok = $ok && $this->deleteItem($key);
        }

        return $ok;
    }

    /**
     * Persists a cache item on the filesystem.
     *
     * @throws CacheException
     *     Raised if the cache folder cannot be written.
     */
    public function save(CacheItem $item): bool
    {
        $item_path = $this->keyToPath($item->getKey());
        if (!is_dir($item_path)) {
            $this->createFolder($item_path);
        }

        $data = serialize([
            $item->getKey(),
            $item->getExpiration(),
            $item->get(),
        ]);

        $item_fullpath = $this->keyToFullpath($item->getKey());
        return @file_put_contents($item_fullpath, $data) !== false;
    }

    /**
     * Deletes all items from the cache.
     */
    public function clear(): bool
    {
        $cache_path = $this->cachePath();

        if (!file_exists($cache_path)) {
            return true;
        }

        return utils\FilesystemHelper::recursiveUnlink($cache_path, keep_current: true);
    }

    /**
     * Deletes expired items from the cache.
     *
     * @throws CacheException
     *     Raised if the cache folder cannot be read.
     */
    public function clearExpiredItems(): bool
    {
        $root_path = $this->cachePath();
        $root_subdirectory_names = scandir($root_path, SCANDIR_SORT_NONE);

        if ($root_subdirectory_names === false) {
            throw new CacheException("Cannot read the {$root_path} cache directory.");
        }

        $root_subdirectory_names = array_filter($root_subdirectory_names, function (string $name): bool {
            // Exclude files or dirs starting with a dot.
            return $name[0] !== '.';
        });

        $ok = true;

        foreach ($root_subdirectory_names as $directory_name) {
            // Get the list of all the files under the current subdirectory.
            $directory_fullpath = "{$root_path}/{$directory_name}";
            $items_fullpaths = glob("{$directory_fullpath}/???/???/*", GLOB_NOSORT);

            if ($items_fullpaths === false) {
                $ok = false;
                continue;
            }

            // Load cache items from paths, and remove the files of the expired ones.
            foreach ($items_fullpaths as $item_fullpath) {
                $cache_item = $this->getItemFromPath($item_fullpath);

                if (!$cache_item || $cache_item->hasExpired()) {
                    $ok = $ok && @unlink($item_fullpath);
                }
            }
        }

        // Remove empty directories under the cache root path to save few inodes.
        utils\FilesystemHelper::cleanTreeEmptyDirectories($root_path);

        return $ok;
    }

    /**
     * Returns the current cache path.
     */
    public function cachePath(): string
    {
        return \App\Configuration::$application['cache_path'] . '/' . $this->namespace;
    }

    /**
     * Returns the path name corresponding to the key.
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function keyToPath(string $key): string
    {
        $hash = $this->hashKeyForPath($key);
        $item_path = utils\Belt::filenameToSubpath($hash);

        if (!$item_path) {
            throw new InvalidArgumentException("Key {$key} is invalid.");
        }

        return $this->cachePath() . '/' . $item_path;
    }

    /**
     * Returns the full path name (including filename) corresponding to the key.
     *
     * @throws InvalidArgumentException
     *     Raised if the key contains invalid chars.
     */
    public function keyToFullpath(string $key): string
    {
        $hash = $this->hashKeyForPath($key);
        $path = $this->keyToPath($key);
        return "{$path}/{$hash}.cache";
    }

    /**
     * Returns a CacheItem from the filesystem, or null if it's missing.
     */
    private function getItemFromPath(string $item_fullpath): ?CacheItem
    {
        $data = @file_get_contents($item_fullpath);

        if ($data === false) {
            return null;
        }

        $data = unserialize($data);

        if (!is_array($data) || count($data) !== 3) {
            return null;
        }

        $key = $data[0];
        $expiration = $data[1];
        $value = $data[2];

        if (!is_string($key)) {
            \Minz\Log::error("Malformed cache key at {$item_fullpath}");
            return null;
        }

        if ($expiration !== null && !($expiration instanceof \DateTimeInterface)) {
            \Minz\Log::error("Malformed cache expiration at {$item_fullpath}");
            return null;
        }

        $cache_item = new CacheItem($key, $value, is_hit: true);
        $cache_item->expiresAt($expiration);

        return $cache_item;
    }

    /**
     * Returns a unique hash for the given key.
     */
    private function hashKeyForPath(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Creates the folder recursively.
     *
     * @throws CacheException
     *     Raised if the cache folder cannot be written.
     */
    private function createFolder(string $fullpath): void
    {
        $result = mkdir($fullpath, permissions: 0755, recursive: true);

        if (!$result) {
            throw new CacheException("Cannot create the {$fullpath} cache directory.");
        }
    }
}
