<?php

namespace flusio\services;

use flusio\jobs;
use flusio\models;
use flusio\utils;

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
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @throws \flusio\services\UserCreatorError
     *     If username, email or password is invalid, or if a user already
     *     exists with this email.
     *
     * @return \flusio\models\User
     */
    public static function create($username, $email, $password)
    {
        $user = models\User::init($username, $email, $password);
        $user->locale = utils\Locale::currentLocale();

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
        $user->bookmarks();
        $user->news();
        $user->readList();
        $user->neverList();

        $favourites = models\Collection::init($user->id, _('My favourites'), '', false);
        $favourites->save();

        $shares = models\Collection::init($user->id, _('My shares'), '', true);
        $shares->save();

        // Load default feeds
        $default_opml_filepath = \Minz\Configuration::$data_path . '/default-feeds.opml.xml';
        if (file_exists($default_opml_filepath)) {
            $importations_filepath = \Minz\Configuration::$data_path . '/importations';
            if (!file_exists($importations_filepath)) {
                @mkdir($importations_filepath);
            }

            $user_opml_filepath = "{$importations_filepath}/opml_{$user->id}.xml";
            $copy_is_ok = copy($default_opml_filepath, $user_opml_filepath);
            if (!$copy_is_ok) {
                \Minz\Log::error("Default OPML file can’t be copied to {$user_opml_filepath}");
                return $user;
            }

            $importation = models\Importation::init('opml', $user->id, [
                'opml_filepath' => $user_opml_filepath,
            ]);
            $importation->save();
            $importator_job = new jobs\OpmlImportator();
            $importator_job->perform($importation->id);
            models\Importation::delete($importation->id);
        }

        return $user;
    }
}
