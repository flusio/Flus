<?php

namespace flusio\migrations;

use flusio\models;
use flusio\utils;

class Migration202108310001InitNewDefaultCollections
{
    public function migrate()
    {
        $collections_columns = [];
        $collections_to_create = [];

        $support_email = \Minz\Configuration::$application['support_email'];

        $dao_user = new models\dao\User();
        $db_users = $dao_user->listAll();
        $db_support_user = $dao_user->findBy([
            'email' => utils\Email::sanitize($support_email),
        ]);
        $now = \Minz\Time::now();

        foreach ($db_users as $db_user) {
            if ($db_user['id'] === $db_support_user['id']) {
                continue;
            }

            utils\Locale::setCurrentLocale($db_user['locale']);

            $news = models\Collection::initNews($db_user['id']);
            $read_list = models\Collection::initReadList($db_user['id']);
            $news->created_at = $now;
            $read_list->created_at = $now;

            $db_news = $news->toValues();
            $db_read_list = $read_list->toValues();
            $collections_to_create = array_merge(
                $collections_to_create,
                array_values($db_news),
                array_values($db_read_list),
            );

            if (!$collections_columns) {
                $collections_columns = array_keys($db_news);
            }
        }

        if ($collections_to_create) {
            models\Collection::daoCall(
                'bulkInsert',
                $collections_columns,
                $collections_to_create
            );
        }

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM collections
            WHERE type = 'news' OR type = 'read';
        SQL);

        return true;
    }
}
