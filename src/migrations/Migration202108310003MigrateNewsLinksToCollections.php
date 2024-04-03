<?php

namespace App\migrations;

use App\models;

class Migration202108310003MigrateNewsLinksToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->beginTransaction();

        $statement = $database->query(<<<'SQL'
            SELECT * FROM news_links;
        SQL);
        $db_news_links = $statement->fetchAll();

        $cache_users = [];
        $links_to_collections_to_create = [];

        foreach ($db_news_links as $db_news_link) {
            // Get user info (news and read list ids) and cache these info
            $user_id = $db_news_link['user_id'];
            if (isset($cache_users[$user_id])) {
                list($read_list_id, $news_id) = $cache_users[$user_id];
            } else {
                $user = models\User::find($user_id);

                assert($user !== null);

                $read_list_id = $user->readList()->id;
                $news_id = $user->news()->id;
                $cache_users[$user_id] = [$read_list_id, $news_id];
            }

            // Get a link with the same URL as the news link
            $url = $db_news_link['url'];
            $existing_link = models\Link::findBy([
                'user_id' => $user_id,
                'url' => $url,
            ]);
            if ($existing_link) {
                $link = $existing_link;
            } else {
                $link = new models\Link($url, $user_id, false);
            }

            // Update the new "via_*" info of the link
            // $link->via_type = $db_news_link['via_type'];
            // $resource_id = $db_news_link['link_id'];
            // if (!$resource_id) {
            //     $resource_id = $db_news_link['via_collection_id'];
            // }
            // $link->via_resource_id = $resource_id;
            $link->save();

            // And attach the link to the corresponding collection:
            // - read list collection if the news link was read or removed
            // - news collection if the news link was still in the news
            // We also get the appropriate date to keep the history correct.
            $removed_at = $db_news_link['removed_at'];
            $read_at = $db_news_link['read_at'];
            $published_at = $db_news_link['published_at'];
            $created_at = $db_news_link['created_at'];

            if ($read_at) {
                $at = $read_at;
                $collection_id = $read_list_id;
            } elseif ($removed_at) {
                $at = $removed_at;
                $collection_id = $read_list_id;
            } elseif ($published_at) {
                $at = $published_at;
                $collection_id = $news_id;
            } else {
                $at = $created_at;
                $collection_id = $news_id;
            }

            $at = \DateTimeImmutable::createFromFormat(\Minz\Database\Column::DATETIME_FORMAT, $at);

            assert($at !== false);

            $link_to_collection = new models\LinkToCollection($link->id, $collection_id);
            $link_to_collection->created_at = $at;

            $links_to_collections_to_create[] = $link_to_collection;
        }

        if ($links_to_collections_to_create) {
            models\LinkToCollection::bulkInsert($links_to_collections_to_create);
        }

        return $database->commit();
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
