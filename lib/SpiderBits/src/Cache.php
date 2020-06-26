<?php

namespace SpiderBits;

/**
 * Allow to cache text easily
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Cache
{
    /** @var string */
    private $path;

    /**
     * @param string $path
     *
     * @throws RuntimeException if the path doesn't exist or if it's not a
     *                          directory
     */
    public function __construct($path)
    {
        $this->path = realpath($path);

        if ($this->path === false) {
            throw new \RuntimeException('The cache path does not exist');
        }

        if (!is_dir($this->path)) {
            throw new \RuntimeException('The cache path is not a directory');
        }
    }

    /**
     * Return text from the cache
     *
     * @param string $key
     * @param integer $validity_interval How many seconds the cache is valid,
     *                                   default is 1 day
     *
     * @return string|null
     */
    public function get($key, $validity_interval = 24 * 60 * 60)
    {
        $cached_text_path = $this->path . '/' . $key;
        $mtime = @filemtime($cached_text_path);
        if ($mtime === false || $mtime <= (time() - $validity_interval)) {
            return null;
        }

        $compressed_text = @file_get_contents($cached_text_path);
        if (!$compressed_text) {
            return null;
        }

        $text = @gzdecode($compressed_text);
        if ($text === false) {
            return null;
        }

        return $text;
    }

    /**
     * Save text in cache
     *
     * @param string $key
     * @param string $text
     *
     * @return boolean
     */
    public function save($key, $text)
    {
        $cached_text_path = $this->path . '/' . $key;
        $compressed_text = @gzencode($text);
        $result = @file_put_contents($cached_text_path, $compressed_text);
        return $result !== false;
    }

    /**
     * Returns a hash of the given string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function hash($string)
    {
        return hash('sha256', $string);
    }
}
