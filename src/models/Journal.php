<?php

namespace App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Journal
{
    public function __construct(
        private User $user,
    ) {
    }

    /**
     * Fill the journal with entries and return the number of links in the journal.
     */
    public function fill(int $max): int
    {
        $news = $this->user->news();

        $links = $news->links();
        if (count($links) > 0) {
            return count($links);
        }

        $links = Link::listFromFollowedCollections($this->user->id, $max);

        foreach ($links as $news_link) {
            $link = $this->user->obtainLink($news_link);

            // If the link has already an origin info, we want to keep it.
            // Otherwise, we use the initial collection URL.
            if (!$link->origin) {
                $collection_url = \Minz\Url::absoluteFor('collection', [
                    'id' => $news_link->initial_collection_id,
                ]);
                $link->setOrigin($collection_url);
            }

            // Make sure to reset this value: it will be set to true later with
            // Link::groupLinksBySources
            $link->group_by_source = false;

            $link->save();

            // And don't forget to add the link to the news collection!
            $link->addCollection(
                $news,
                at: $news_link->published_at,
                sync_publication_frequency: false,
            );
        }

        Link::groupLinksBySources($news->id);

        return count($links);
    }
}
