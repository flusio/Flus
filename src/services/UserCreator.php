<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * This service helps to create users with default data.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserCreator
{
    /**
     * Create a user with default data.
     *
     * @throws UserCreatorError
     *     If username, email or password is invalid, or if a user already
     *     exists with this email.
     */
    public static function create(string $username, string $email, string $password): models\User
    {
        $user = new models\User($username, $email, $password);
        $user->locale = utils\Locale::currentLocale();

        /** @var array<string, string> */
        $errors = $user->validate();
        if ($errors) {
            throw new UserCreatorError($errors);
        }

        if (models\User::findBy(['email' => $user->email])) {
            throw new UserCreatorError([
                'email' => _('An account already exists with this email address.'),
            ]);
        }

        $user->save();

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

        return $user;
    }
}
