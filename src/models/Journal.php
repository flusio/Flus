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

            // If the link has already a source info, we want to keep it (it
            // might have been get via a followed collection, and put in the
            // bookmarks then)
            if (!$link->source_type && $news_link->source_news_type !== null) {
                $link->source_type = $news_link->source_news_type;
                $link->source_resource_id = $news_link->source_news_resource_id;
            }

            // Make sure to reset this value: it will be set to true later with
            // Link::groupLinksBySources
            $link->group_by_source = false;

            $link->save();

            // And don't forget to add the link to the news collection!
            $link->addCollection($news, at: $news_link->published_at);
        }

        Link::groupLinksBySources($news->id);

        return count($links);
    }
}
