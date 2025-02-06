<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FilesystemHelper
{
    /**
     * Delete a file or a directory recursively.
     */
    public static function recursiveUnlink(string $directory, bool $keep_current = false): bool
    {
        if (!is_dir($directory) && !$keep_current) {
            return unlink($directory);
        }

        $filenames = scandir($directory, SCANDIR_SORT_NONE);

        if ($filenames === false) {
            $filenames = [];
        }

        foreach ($filenames as $filename) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $filepath = "{$directory}/{$filename}";
            if (is_dir($filepath)) {
                self::recursiveUnlink($filepath);
            } else {
                unlink($filepath);
            }
        }

        if ($keep_current) {
            return true;
        }

        return rmdir($directory);
    }

    /**
     * Remove all the empty directories under this directory.
     *
     * Return true if the directory was deleted, false otherwise.
     */
    public static function cleanTreeEmptyDirectories(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $files = scandir($directory);

        if ($files === false) {
            return false;
        }

        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            $filepath = "{$directory}/{$file}";

            if (is_dir($filepath)) {
                $can_delete_dir = self::cleanTreeEmptyDirectories($filepath);
            } else {
                $can_delete_dir = false;
            }

            if (!$can_delete_dir) {
                return false;
            }
        }

        rmdir($directory);

        return true;
    }
}
