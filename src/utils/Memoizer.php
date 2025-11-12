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
 *         public function timeConsumingMethod(): array
 *         {
 *             return $this->memoize('my_cache_key', function (): array {
 *                 return models\User::listAll();
 *             });
 *         }
 *     }
 *
 * In this example, `MyClass::timeConsumingMethod` can be called several time,
 * but users will be fetched from the database only the first time.
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
        if (!array_key_exists($key, $this->memoizer_cache)) {
            $this->memoizer_cache[$key] = $callback();
        }

        return $this->memoizer_cache[$key];
    }

    /**
     * Remove a result from the cache.
     */
    protected function unmemoize(string $key): void
    {
        if (array_key_exists($key, $this->memoizer_cache)) {
            unset($this->memoizer_cache[$key]);
        }
    }
}
