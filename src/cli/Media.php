<?php

namespace App\cli;

use App\images;
use App\models;
use App\utils;
use Minz\Response;

/**
 * @phpstan-import-type ResponseGenerator from Response
 * @phpstan-import-type MediaType from images\Storage
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Media
{
    /**
     * Clean the media files which are unused (i.e. by links, sources,
     * streams, follows or users).
     *
     * Responses are yield because it can take a very long time to perform.
     *
     * @response 200
     *
     * @return ResponseGenerator
     */
    public function clean(): mixed
    {
        $count_deleted = 0;

        $count_deleted += yield from $this->cleanType('covers', fn(string $prefix): array => array_merge(
            models\Link::listImageFilenamesStartingWith($prefix),
            models\Source::listImageFilenamesStartingWith($prefix),
            models\Stream::listImageFilenamesStartingWith($prefix),
            models\Follow::listImageFilenamesStartingWith($prefix),
        ));

        $count_deleted += yield from $this->cleanType('avatars', fn(string $prefix): array => (
            models\User::listAvatarFilenamesStartingWith($prefix)
        ));

        if ($count_deleted > 0) {
            yield Response::text(200, "{$count_deleted} files deleted.");
        } else {
            yield Response::text(200, 'No files deleted.');
        }
    }

    /**
     * Delete the files of the given media type which are not returned by
     * $list_filenames_starting_with.
     *
     * Nothing is done if the media directory doesn't exist.
     *
     * @param MediaType $type
     * @param callable(string): string[] $list_filenames_starting_with
     *
     * @return \Generator<int, Response, mixed, int>
     *     The generator returns the number of deleted files.
     */
    private function cleanType(string $type, callable $list_filenames_starting_with): \Generator
    {
        $media_path = \App\Configuration::$application['media_path'];
        $type_path = "{$media_path}/{$type}";

        if (!is_dir($type_path)) {
            return 0;
        }

        $count_deleted = 0;

        yield Response::text(200, "Scanning the {$type} directory...");
        $subdir_names_depth_1 = scandir($type_path, SCANDIR_SORT_NONE);

        if ($subdir_names_depth_1 === false) {
            throw new \Exception("Cannot read the {$type} directory.");
        }

        $subdir_names_depth_1 = array_filter(
            $subdir_names_depth_1,
            // Exclude files or dirs starting with a dot.
            fn(string $subdir_name): bool => $subdir_name[0] !== '.'
        );
        // Reset the index keys of the array so calculation of progress will be
        // correct.
        $subdir_names_depth_1 = array_values($subdir_names_depth_1);
        $number_subdirs = count($subdir_names_depth_1);
        yield Response::text(200, "{$number_subdirs} sub-directories found.");

        // Iterate over the first level of media subdirectories
        foreach ($subdir_names_depth_1 as $index => $subdir_name) {
            $progress_percent = round($index * 100 / $number_subdirs);
            yield Response::text(200, "Removing files under {$type}/{$subdir_name}/... ({$progress_percent}%)");

            // get the full list of file names under the current subdirectory
            $subdir_path = "{$type_path}/{$subdir_name}";
            $file_paths_on_fs = glob("{$subdir_path}/???/???/*", GLOB_NOSORT);

            if ($file_paths_on_fs === false) {
                yield Response::text(500, "Cannot read files under {$type}/{$subdir_name}/...");
                continue;
            }

            $file_names_on_fs = array_map(basename(...), $file_paths_on_fs);

            // get the list of file names starting with the same 3 characters
            // in database
            $file_names_from_db = $list_filenames_starting_with($subdir_name);

            // do the diff between those 2 arrays to get the list of files to
            // delete (i.e. if a filename exists on the filesystem, but not in
            // database, the file can be deleted)
            $file_names_to_delete = array_diff($file_names_on_fs, $file_names_from_db);

            foreach ($file_names_to_delete as $file_name) {
                @unlink(images\Storage::filepath($type, $file_name));
                $count_deleted = $count_deleted + 1;
                yield Response::text(200, "Deleted file {$file_name}");
            }

            if (!$file_names_to_delete) {
                yield Response::text(200, "Nothing to delete under {$type}/{$subdir_name}/.");
            }

            yield Response::text(200, "Cleaning empty dirs under {$type}/{$subdir_name}/...");

            utils\FilesystemHelper::cleanTreeEmptyDirectories($subdir_path);

            yield Response::text(200, "Cleaned empty dirs under {$type}/{$subdir_name}/.");
        }

        return $count_deleted;
    }
}
