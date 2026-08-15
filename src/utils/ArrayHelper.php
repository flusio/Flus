<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ArrayHelper
{
    /**
     * Returns the first element satisfying a callback function.
     *
     * Polyfill for array_find coming in PHP 8.4.
     *
     * @template T of mixed
     *
     * @param T[] $array
     * @param callable(T $value, mixed $key): bool $callback
     *
     * @return ?T
     */
    public static function find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Checks if at least one array element satisfies a callback function.
     *
     * Polyfill for array_any coming in PHP 8.4.
     *
     * @template T of mixed
     *
     * @param T[] $array
     * @param callable(T $value, mixed $key): bool $callback
     *
     * @return bool
     */
    public static function any(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trim an array of strings.
     *
     * @param string[] $strings
     * @return string[]
     */
    public static function trim(array $strings): array
    {
        return array_map('trim', $strings);
    }
}
