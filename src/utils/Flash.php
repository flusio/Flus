<?php

namespace flusio\utils;

/**
 * The Flash utility provides methods to pass messages from a page to another,
 * through redirections.
 *
 * The messages are saved into the $_SESSION.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Flash
{
    /**
     * Store a value as a flash message.
     *
     * @param string $name
     * @param mixed $value
     */
    public static function set($name, $value)
    {
        $_SESSION['_flash'][$name] = $value;
    }

    /**
     * Return the flash message value and delete it.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    public static function pop($name, $default_value = null)
    {
        if (isset($_SESSION['_flash'][$name])) {
            $value = $_SESSION['_flash'][$name];
            unset($_SESSION['_flash'][$name]);
            return $value;
        } else {
            return $default_value;
        }
    }

    /**
     * Return the flash message value.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    public static function get($name, $default_value = null)
    {
        if (isset($_SESSION['_flash'][$name])) {
            return $_SESSION['_flash'][$name];
        } else {
            return $default_value;
        }
    }
}
