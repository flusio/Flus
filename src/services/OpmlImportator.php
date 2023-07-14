<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

/**
 * Service to import feeds from an OPML file.
 *
 * @phpstan-import-type Outline from \SpiderBits\Opml
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportator
{
    private \SpiderBits\Opml $opml;

    /**
     * @throws OpmlImportatorError
     *     If the file cannot be read, or if it cannot be parsed as an OPML
     *     file.
     */
    public function __construct(string $opml_filepath)
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
     */
    public function importForUser(models\User $user): void
    {
        $support_user = models\User::supportUser();

        $feed_urls_by_groups = $this->loadUrlsFromOutlines($this->opml->outlines, '');

        $collection_ids_by_feed_urls = models\Collection::listFeedUrlsToIdsByUserId($support_user->id);
        $collections_to_create = [];
        $followed_collections_to_create = [];

        foreach ($feed_urls_by_groups as $group_name => $feed_urls) {
            $group_id = null;
            if ($group_name) {
                // If there is a group name, we want to make sure it exists in
                // database, and get its id to attach it to the followed
                // collection.
                $group_name = utils\Belt::cut($group_name, models\Group::NAME_MAX_LENGTH);
                $group = new models\Group($user->id, $group_name);
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

                $followed_collection = new models\FollowedCollection($user->id, $collection_id);
                $followed_collection->group_id = $group_id;
                $followed_collection->created_at = \Minz\Time::now();

                $followed_collections_to_create[] = $followed_collection;
            }
        }

        models\Collection::bulkInsert($collections_to_create);
        models\FollowedCollection::bulkInsert($followed_collections_to_create);
    }

    /**
     * Return the list of xmlUrl by group name of OPML outlines and their children.
     *
     * @param Outline[] $outlines
     * @param string $parent_group_name
     *
     * @return array<string, string[]>
     */
    private function loadUrlsFromOutlines(array $outlines, string $parent_group_name): array
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
     * @param Outline $outline
     * @param string $parent_group_name
     *
     * @return array<string, string[]>
     */
    private function loadUrlsFromOutline(array $outline, string $parent_group_name): array
    {
        $urls_by_groups = [];

        if ($outline['outlines']) {
            // The outline has children, it's probably a new group
            $text = $outline['text'] ?? '';
            if (is_string($text) && !empty($text)) {
                $group_name = trim($text);
            } else {
                $group_name = $parent_group_name;
            }

            /** @var Outline[] */
            $outlines = $outline['outlines'];
            $urls_by_groups = $this->loadUrlsFromOutlines($outlines, $group_name);
        }

        if (!isset($urls_by_groups[$parent_group_name])) {
            $urls_by_groups[$parent_group_name] = [];
        }

        if (is_string($outline['xmlUrl'] ?? null)) {
            // The xmlUrl means it's a feed URL: we add it to the array
            $urls_by_groups[$parent_group_name][] = $outline['xmlUrl'];
        }

        return $urls_by_groups;
    }
}
