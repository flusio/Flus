<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

/**
 * Service to import feeds from an OPML file.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportator
{
    /** @var \SpiderBits\Opml */
    private $opml;

    /**
     * @param string $opml_filepath
     *     The path to the OPML file to import
     *
     * @throws OpmlImportatorError
     *     If the file cannot be read, or if it cannot be parsed as an OPML
     *     file.
     */
    public function __construct($opml_filepath)
    {
        $opml_as_string = @file_get_contents($opml_filepath);

        if ($opml_as_string === false) {
            throw new OpmlImportatorError('Can’t read the OPML file.');
        }

        try {
            $opml = \SpiderBits\Opml::fromText($opml_as_string);
        } catch (\DomainException $e) {
            throw new OpmlImportatorError($e->getMessage());
        }

        $this->opml = $opml;
    }

    /**
     * Perform the importation.
     *
     * @param \flusio\models\User $user
     */
    public function importForUser($user)
    {
        $support_user = models\User::supportUser();

        $feed_urls_by_groups = $this->loadUrlsFromOutlines($this->opml->outlines, '');

        $collection_ids_by_feed_urls = models\Collection::daoCall('listFeedUrlsToIdsByUserId', $support_user->id);
        $collections_to_create = [];
        $followed_collections_to_create = [];

        foreach ($feed_urls_by_groups as $group_name => $feed_urls) {
            $group_id = null;
            if ($group_name) {
                // If there is a group name, we want to make sure it exists in
                // database, and get its id to attach it to the followed
                // collection.
                $group_name = utils\Belt::cut($group_name, models\Group::NAME_MAX_LENGTH);
                $group = models\Group::init($user->id, $group_name);
                $existing_group = models\Group::findBy([
                    'name' => $group->name,
                    'user_id' => $group->user_id,
                ]);
                if ($existing_group) {
                    $group_id = $existing_group->id;
                } else {
                    $group->save();
                    $group_id = $group->id;
                }
            }

            foreach ($feed_urls as $feed_url) {
                $feed_url = \SpiderBits\Url::sanitize($feed_url);

                if (isset($collection_ids_by_feed_urls[$feed_url])) {
                    $collection_id = $collection_ids_by_feed_urls[$feed_url];
                } else {
                    $collection = models\Collection::initFeed($support_user->id, $feed_url);
                    $collection->created_at = \Minz\Time::now();

                    $collections_to_create[] = $collection;

                    $collection_ids_by_feed_urls[$feed_url] = $collection->id;
                    $collection_id = $collection->id;
                }

                $followed_collections_to_create[] = $user->id;
                $followed_collections_to_create[] = $collection_id;
                $followed_collections_to_create[] = $group_id;
                $followed_collections_to_create[] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
            }
        }

        models\Collection::bulkInsert($collections_to_create);

        if ($followed_collections_to_create) {
            $followed_collections_dao = new models\dao\FollowedCollection();
            $followed_collections_dao->bulkInsert(
                ['user_id', 'collection_id', 'group_id', 'created_at'],
                $followed_collections_to_create
            );
        }
    }

    /**
     * Return the list of xmlUrl by group name of OPML outlines and their children.
     *
     * @param array $outlines
     * @param string $parent_group_name
     *
     * @return string[]
     */
    private function loadUrlsFromOutlines($outlines, $parent_group_name)
    {
        $urls_by_groups = [];

        foreach ($outlines as $outline) {
            // Get the urls from child outline (it may return several urls if
            // the outline is a group).
            $outline_urls_by_groups = $this->loadUrlsFromOutline($outline, $parent_group_name);

            // Then, we merge the initial array with the array returned by the
            // outline.
            foreach ($outline_urls_by_groups as $group_name => $urls) {
                if (!isset($urls_by_groups[$group_name])) {
                    $urls_by_groups[$group_name] = [];
                }

                $urls_by_groups[$group_name] = array_merge(
                    $urls_by_groups[$group_name],
                    $urls
                );
            }
        }

        return $urls_by_groups;
    }

    /**
     * Return the list of xmlUrl of an OPML outline and its children.
     *
     * @param array $outline
     * @param string $parent_group_name
     *
     * @return string[]
     */
    private function loadUrlsFromOutline($outline, $parent_group_name)
    {
        $urls_by_groups = [];

        if ($outline['outlines']) {
            // The outline has children, it's probably a new group
            if (!empty($outline['text'])) {
                $group_name = trim($outline['text']);
            } else {
                $group_name = $parent_group_name;
            }

            $urls_by_groups = $this->loadUrlsFromOutlines($outline['outlines'], $group_name);
        }

        if (!isset($urls_by_groups[$parent_group_name])) {
            $urls_by_groups[$parent_group_name] = [];
        }

        if (isset($outline['xmlUrl'])) {
            // The xmlUrl means it's a feed URL: we add it to the array
            $urls_by_groups[$parent_group_name][] = $outline['xmlUrl'];
        }

        return $urls_by_groups;
    }
}
