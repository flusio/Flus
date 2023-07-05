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
    private string $path;

    /**
     * @throws \RuntimeException
     *     If the path doesn't exist or if it's not a directory.
     */
    public function __construct(string $path)
    {
        $path = realpath($path);

        if ($path === false) {
            throw new \RuntimeException('The cache path does not exist');
        }

        if (!is_dir($path)) {
            throw new \RuntimeException('The cache path is not a directory');
        }

        $this->path = $path;
    }

    /**
     * Return text from the cache
     */
    public function get(string $key, int $validity_interval = 24 * 60 * 60): ?string
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
     */
    public function save(string $key, string $text): bool
    {
        $cached_text_path = $this->path . '/' . $key;
        $compressed_text = @gzencode($text);
        $result = @file_put_contents($cached_text_path, $compressed_text);
        return $result !== false;
    }

    /**
     * Clear the cache of the given key.
     */
    public function remove(string $key): bool
    {
        $filepath = $this->path . '/' . $key;
        if (file_exists($filepath)) {
            return @unlink($filepath);
        } else {
            return true;
        }
    }

    /**
     * Returns a hash of the given string.
     */
    public static function hash(string $string): string
    {
        return hash('sha256', $string);
    }

    /**
     * Clean the cache
     */
    public function clean(int $validity_interval = 7 * 24 * 60 * 60): void
    {
        $files = scandir($this->path);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.keep') {
                continue;
            }

            $filepath = $this->path . '/' . $file;
            $mtime = @filemtime($filepath);
            if ($mtime === false || $mtime <= (time() - $validity_interval)) {
                @unlink($filepath);
            }
        }
    }
}
