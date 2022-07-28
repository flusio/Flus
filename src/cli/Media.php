<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Media
{
    /**
     * Clean the media files which are unused (i.e. by links, collections or
     * topics).
     *
     * Responses are yield because it can take a very long time to perform.
     *
     * @response 200
     */
    public function clean()
    {
        $media_path = \Minz\Configuration::$application['media_path'];
        $path_cards = "{$media_path}/cards";
        $path_covers = "{$media_path}/covers";
        $path_large = "{$media_path}/large";

        $count_deleted = 0;

        yield Response::text(200, 'Scanning the media directories...');
        $subdir_names_depth_1 = scandir($path_cards, SCANDIR_SORT_NONE);
        $number_subdirs = count($subdir_names_depth_1);
        yield Response::text(200, "{$number_subdirs} sub-directories found.");

        // Iterate over the first level of media subdirectories
        foreach ($subdir_names_depth_1 as $index => $subdir_name) {
            if ($subdir_name[0] === '.') {
                continue;
            }

            $progress_percent = round($index * 100 / count($subdir_names_depth_1));
            yield Response::text(200, "Removing files under {$subdir_name}/... ({$progress_percent}%)");

            // get the full list of file names under the current subdirectory
            $subdir_path = "{$path_cards}/{$subdir_name}";
            $file_paths_on_fs = glob("{$subdir_path}/???/???/*", GLOB_NOSORT);
            $file_names_on_fs = array_map('basename', $file_paths_on_fs);

            // get the list of file names starting with the same 3 characters
            // in database
            $file_names_from_db = array_merge(
                models\Link::daoCall('listImageFilenamesStartingWith', $subdir_name),
                models\Collection::daoCall('listImageFilenamesStartingWith', $subdir_name),
                models\Topic::daoCall('listImageFilenamesStartingWith', $subdir_name),
            );

            // do the diff between those 2 arrays to get the list of files to
            // delete (i.e. if a filename exists on the filesystem, but not in
            // database, the file can be deleted)
            $file_names_to_delete = array_diff($file_names_on_fs, $file_names_from_db);

            // finally, delete the files under cards, covers and large directories
            foreach ($file_names_to_delete as $file_name) {
                $sub_path = utils\Belt::filenameToSubpath($file_name);
                @unlink("{$path_cards}/{$sub_path}/{$file_name}");
                @unlink("{$path_covers}/{$sub_path}/{$file_name}");
                @unlink("{$path_large}/{$sub_path}/{$file_name}");
                $count_deleted = $count_deleted + 1;
                yield Response::text(200, "Deleted file {$file_name}");
            }

            if (!$file_names_to_delete) {
                yield Response::text(200, "Nothing to delete under {$subdir_name}/.");
            }
        }

        if ($count_deleted > 0) {
            yield Response::text(200, "{$count_deleted} files deleted.");
        } else {
            yield Response::text(200, 'No files deleted.');
        }
    }
}
