<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserService
{
    /**
     * Initialize a user with default data.
     */
    public static function initializeData(models\User $user): void
    {
        // Init default collections
        $bookmarks = $user->bookmarks();
        $user->news();
        $user->readList();
        $user->neverList();

        $favourites = models\Collection::init($user->id, _('My favourites'), '', false);
        $favourites->save();

        $shares = models\Collection::init($user->id, _('My shares'), '', true);
        $shares->save();

        // Load default feeds
        $default_opml_filepath = \App\Configuration::$data_path . '/default-feeds.opml.xml';
        if (file_exists($default_opml_filepath)) {
            try {
                $opml_importator_service = new OpmlImportator($default_opml_filepath);
                $opml_importator_service->importForUser($user);
            } catch (OpmlImportatorError $e) {
                \Minz\Log::error("Error while importing default feeds for user {$user->id}: {$e->getMessage()}");
                // Don't pass the error to the parent as it's a "minor" issue
                // (the user actually exists and is functional)
            }
        }

        // Load default bookmarks
        $default_bookmarks_filepath = \App\Configuration::$data_path . '/default-bookmarks.atom.xml';
        if (file_exists($default_bookmarks_filepath)) {
            try {
                $atom_importator_service = new AtomImportator($default_bookmarks_filepath);
                $atom_importator_service->importForCollection($bookmarks);
            } catch (AtomImportatorError $e) {
                \Minz\Log::error("Error while importing default bookmarks for user {$user->id}: {$e->getMessage()}");
                // Don't pass the error to the parent as it's a "minor" issue
                // (the user actually exists and is functional)
            }
        }
    }
}
