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
     * @param callable(): T $callback
     *
     * @return T
     */
    protected function memoize(string $key, callable $callback): mixed
    {
        if (!$this->isMemoized($key)) {
            $this->memoizeValue($key, $callback());
        }

        return $this->memoizer_cache[$key];
    }

    /**
     * Store a value in the cache.
     */
    protected function memoizeValue(string $key, mixed $value): void
    {
        $this->memoizer_cache[$key] = $value;
    }

    /**
     * Remove a result from the cache.
     */
    protected function unmemoize(string $key): void
    {
        if ($this->isMemoized($key)) {
            unset($this->memoizer_cache[$key]);
        }
    }

    /**
     * Return true if the key exists in the cache, false otherwise.
     */
    protected function isMemoized(string $key): bool
    {
        return array_key_exists($key, $this->memoizer_cache);
    }
}
