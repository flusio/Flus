<?php

namespace flusio\migrations;

use flusio\models;
use flusio\utils;

class Migration202108310001InitNewDefaultCollections
{
    public function migrate(): bool
    {
        $collections_to_create = [];

        $users = models\User::listAll();
        $support_user = models\User::supportUser();

        $now = \Minz\Time::now();

        foreach ($users as $user) {
            if ($user->id === $support_user->id) {
                continue;
            }

            utils\Locale::setCurrentLocale($user->locale);

            $news = models\Collection::initNews($user->id);
            $read_list = models\Collection::initReadList($user->id);
            $news->created_at = $now;
            $read_list->created_at = $now;

            $collections_to_create[] = $news;
            $collections_to_create[] = $read_list;
        }

        if ($collections_to_create) {
            models\Collection::bulkInsert($collections_to_create);
        }

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM collections
            WHERE type = 'news' OR type = 'read';
        SQL);

        return true;
    }
}
