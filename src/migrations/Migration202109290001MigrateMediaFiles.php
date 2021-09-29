<?php

namespace flusio\migrations;

use flusio\utils;

class Migration202109290001MigrateMediaFiles
{
    public function migrate()
    {
        $media_path = \Minz\Configuration::$application['media_path'];
        $media_folders = ['avatars', 'cards', 'covers', 'large'];

        foreach ($media_folders as $media_folder) {
            $media_folder_path = "{$media_path}/{$media_folder}";

            // make sure the media folder exists
            @mkdir($media_folder_path, 0755, true);

            // We iterate on all the files to move them in their new subfolder
            $filenames = array_diff(scandir($media_folder_path), ['.', '..']);
            foreach ($filenames as $filename) {
                $current_filepath = "{$media_folder_path}/{$filename}";
                if (is_dir($current_filepath)) {
                    // flusio might already fetched images with the new
                    // organization, we need to skip them
                    continue;
                }

                // get the new path and create it if needed
                $subpath = utils\Belt::filenameToSubpath($filename);
                $new_path = "{$media_folder_path}/{$subpath}";
                @mkdir($new_path, 0755, true);

                // move the file to its new location
                $new_filepath = "{$new_path}/{$filename}";
                rename($current_filepath, $new_filepath);
            }
        }

        return true;
    }

    public function rollback()
    {
        $media_path = \Minz\Configuration::$application['media_path'];
        $media_folders = ['avatars', 'cards', 'covers', 'large'];

        function delete_empty_dirs($directory)
        {
            $directory_is_empty = true;

            foreach (array_diff(scandir($directory), ['.', '..']) as $filename) {
                $filepath = "{$directory}/{$filename}";
                if (is_dir($filepath)) {
                    // if it's a dir, remove it by recursion
                    $subdirectory_is_empty = delete_empty_dirs($filepath);
                    $directory_is_empty = $directory_is_empty && $subdirectory_is_empty;
                } else {
                    // we don't want to delete the files! They should be moved
                    // before
                    $directory_is_empty = false;
                }
            }

            if ($directory_is_empty) {
                // all good, the directory is empty so we can delete the
                // current one as well
                rmdir($directory);
                return true;
            } else {
                // oops, a file exists in the current directory, do nothing
                return false;
            }
        }

        foreach ($media_folders as $media_folder) {
            $media_folder_path = "{$media_path}/{$media_folder}";

            // list all the files under their subfolders (3 levels of depth)
            $filepaths = glob("{$media_folder_path}/*/*/*/*");

            // move the files to their initial location
            foreach ($filepaths as $filepath) {
                $filename = basename($filepath);
                $new_filepath = "{$media_folder_path}/{$filename}";
                rename($filepath, $new_filepath);
            }

            // delete the subdirectories that have been created during the
            // migration
            delete_empty_dirs($media_folder_path);

            // just make sure to keep the media folder if it was empty (it
            // should not happen in production, but it happened during
            // development).
            @mkdir($media_folder_path, 0755, true);
        }

        return true;
    }
}
