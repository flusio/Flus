<?php

namespace App\utils;

/**
 * The Memoizer trait can be used to cache the results of time-consuming functions.
 *
 * It wraps a function in a "memoize" callback. When the memoize() method is
 * called, it checks if the result is present in the cache. If it's not, it
 * calls the callback and caches the result.
 *
 *     use App\models;
 *     use App\utils;
 *
 *     class MyClass
 *     {
 *         use utils\Memoizer;
 *
 *         public function listUsers(): array
 *         {
 *             return $this->memoize('users', function (): array {
 *                 return models\User::listAll();
 *             });
 *         }
 *     }
 *
 * In this example, `listUsers()` can be called several time, but users will be
 * fetched from the database only the first time.
 *
 * The key can also be given as an array, when the cached value depends on
 * some data (e.g. a user). The key is then built using these values. The array
 * accepts strings, integers, booleans, null, or objects with an "id" field.
 * The first element should be the name of the value (e.g. `count_links`) so the
 * key can be removed with `unmemoizePrefixed()`.
 *
 *     return $this->memoize(['count_links', $user], function () use ($user): int {
 *         // ...
 *     });
 *
 * @phpstan-type MemoizerKey string|array<string|int|bool|null|object{id: int|string}>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Memoizer
{
    /** @var array<string, mixed> */
    private array $memoizer_cache = [];

    /**
     * Cache and return the result of a callback.
     *
     * @template T of mixed
     *
     * @param MemoizerKey $key
     * @param callable(): T $callback
     *
     * @return T
     */
    protected function memoize(string|array $key, callable $callback): mixed
    {
        if (!$this->isMemoized($key)) {
            $this->memoizeValue($key, $callback());
        }

        return $this->memoizer_cache[self::buildMemoizerKey($key)];
    }

    /**
     * Store a value in the cache.
     *
     * @param MemoizerKey $key
     */
    protected function memoizeValue(string|array $key, mixed $value): void
    {
        $this->memoizer_cache[self::buildMemoizerKey($key)] = $value;
    }

    /**
     * Remove a result from the cache.
     *
     * @param MemoizerKey $key
     */
    protected function unmemoize(string|array $key): void
    {
        if ($this->isMemoized($key)) {
            unset($this->memoizer_cache[self::buildMemoizerKey($key)]);
        }
    }

    /**
     * Remove all the results whose keys start with the given prefix.
     */
    protected function unmemoizePrefixed(string $prefix): void
    {
        foreach (array_keys($this->memoizer_cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->memoizer_cache[$key]);
            }
        }
    }

    /**
     * Return true if the key exists in the cache, false otherwise.
     *
     * @param MemoizerKey $key
     */
    protected function isMemoized(string|array $key): bool
    {
        return array_key_exists(self::buildMemoizerKey($key), $this->memoizer_cache);
    }

    /**
     * Return the key of the cache corresponding to the given key.
     *
     * @param MemoizerKey $key
     */
    private static function buildMemoizerKey(string|array $key): string
    {
        if (is_string($key)) {
            return $key;
        }

        $parts = array_map(function (mixed $part): string {
            if (is_object($part)) {
                return (string) $part->id;
            } elseif (is_bool($part)) {
                return $part ? '1' : '0';
            } elseif ($part === null) {
                return 'null';
            } else {
                return (string) $part;
            }
        }, $key);

        return implode('_', $parts);
    }
}
