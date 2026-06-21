<?php

namespace App\migrations;

use App\models;
use App\utils;

class Migration202108310001InitNewDefaultCollections
{
    public function migrate(): bool
    {
        $collections_to_create = [];

        $users = models\User::listAll();

        $now = \Minz\Time::now();

        foreach ($users as $user) {
            utils\Locale::setCurrentLocale($user->locale);

            $news = models\Collection::initNews($user->id);
            $news->created_at = $now;

            $collections_to_create[] = $news;
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
