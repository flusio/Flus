<?php

namespace App\cli;

use Minz\Response;
use App\models;
use App\utils;

/**
 * @phpstan-import-type ResponseGenerator from Response
 *
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
     *
     * @return ResponseGenerator
     */
    public function clean(): mixed
    {
        $media_path = \App\Configuration::$application['media_path'];
        $path_covers = "{$media_path}/covers";

        $count_deleted = 0;

        yield Response::text(200, 'Scanning the media directories...');
        $subdir_names_depth_1 = scandir($path_covers, SCANDIR_SORT_NONE);

        if ($subdir_names_depth_1 === false) {
            throw new \Exception('Cannot read the media directories.');
        }

        $subdir_names_depth_1 = array_filter($subdir_names_depth_1, function (string $subdir_name): bool {
            // Exclude files or dirs starting with a dot.
            return $subdir_name[0] !== '.';
        });
        // Reset the index keys of the array so calculation of progress will be
        // correct.
        $subdir_names_depth_1 = array_values($subdir_names_depth_1);
        $number_subdirs = count($subdir_names_depth_1);
        yield Response::text(200, "{$number_subdirs} sub-directories found.");

        // Iterate over the first level of media subdirectories
        foreach ($subdir_names_depth_1 as $index => $subdir_name) {
            $progress_percent = round($index * 100 / $number_subdirs);
            yield Response::text(200, "Removing files under {$subdir_name}/... ({$progress_percent}%)");

            // get the full list of file names under the current subdirectory
            $subdir_path = "{$path_covers}/{$subdir_name}";
            $file_paths_on_fs = glob("{$subdir_path}/???/???/*", GLOB_NOSORT);

            if ($file_paths_on_fs === false) {
                yield Response::text(500, "Cannot read files under {$subdir_name}/...");
                continue;
            }

            $file_names_on_fs = array_map('basename', $file_paths_on_fs);

            // get the list of file names starting with the same 3 characters
            // in database
            $file_names_from_db = array_merge(
                models\Link::listImageFilenamesStartingWith($subdir_name),
                models\Collection::listImageFilenamesStartingWith($subdir_name),
            );

            // do the diff between those 2 arrays to get the list of files to
            // delete (i.e. if a filename exists on the filesystem, but not in
            // database, the file can be deleted)
            $file_names_to_delete = array_diff($file_names_on_fs, $file_names_from_db);

            // finally, delete the files under covers directory
            foreach ($file_names_to_delete as $file_name) {
                $sub_path = utils\Belt::filenameToSubpath($file_name);
                @unlink("{$path_covers}/{$sub_path}/{$file_name}");
                $count_deleted = $count_deleted + 1;
                yield Response::text(200, "Deleted file {$file_name}");
            }

            if (!$file_names_to_delete) {
                yield Response::text(200, "Nothing to delete under {$subdir_name}/.");
            }

            yield Response::text(200, "Cleaning empty dirs under {$subdir_name}/...");

            utils\FilesystemHelper::cleanTreeEmptyDirectories("{$path_covers}/{$subdir_name}");

            yield Response::text(200, "Cleaned empty dirs under {$subdir_name}/.");
        }

        if ($count_deleted > 0) {
            yield Response::text(200, "{$count_deleted} files deleted.");
        } else {
            yield Response::text(200, 'No files deleted.');
        }
    }
}
