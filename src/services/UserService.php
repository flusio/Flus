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
        $user->news();

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

        // Load default read later links
        $default_read_later_filepath = \App\Configuration::$data_path . '/default-read-later.atom.xml';
        if (file_exists($default_read_later_filepath)) {
            try {
                $atom_importator_service = new AtomImportator($default_read_later_filepath);
                $atom_importator_service->importReadLater($user);
            } catch (AtomImportatorError $e) {
                \Minz\Log::error("Error while importing default read later for user {$user->id}: {$e->getMessage()}");
                // Don't pass the error to the parent as it's a "minor" issue
                // (the user actually exists and is functional)
            }
        }
    }
}
