<?php

namespace App\models\links;

use App\models\Collection;
use App\models\Link;
use App\models\UrlStatus;
use App\models\User;
use App\utils\OriginFormatter;

/**
 * Load in batch the data that a list of links would otherwise load one by one.
 *
 * The loaded values are pushed in the memoizer cache of the links, so that the
 * corresponding methods return them without querying the database again. A
 * list of links can then be rendered with a constant number of queries.
 *
 *     Preloader::for($links)
 *         ->sources()
 *         ->numberCollectionsFor($user);
 *
 * The methods taking a user accept a null one, so that they can be chained
 * without condition when the current user is optional.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preloader
{
    /**
     * @param Link[] $links
     */
    private function __construct(
        private array $links,
    ) {
    }

    /**
     * @param Link[] $links
     */
    public static function for(array $links): self
    {
        return new self($links);
    }

    /**
     * Preload the sources of the links.
     */
    public function sources(): self
    {
        $sources_by_ids = Collection::listSourcesByLinks($this->links);

        foreach ($this->links as $link) {
            $source = null;
            if ($link->source_id) {
                $source = $sources_by_ids[$link->source_id] ?? null;
            }

            $link->preloadSource($source);
        }

        return $this;
    }

    /**
     * Preload the collections containing the links.
     */
    public function collections(): self
    {
        $collections_by_link_ids = Collection::listByLinks($this->links);

        foreach ($this->links as $link) {
            $link->preloadCollections($collections_by_link_ids[$link->id] ?? []);
        }

        return $this;
    }

    /**
     * Preload the models designated by the origins of the links, as the given
     * user can view them.
     *
     * Contrary to the other values, the origins are memoized by the shared
     * OriginFormatter, which the views and Link::toJson() go through.
     */
    public function originsFor(?User $user): self
    {
        OriginFormatter::instance($user)->preloadOrigins($this->links);

        return $this;
    }

    /**
     * Preload the read, read later and dismissed statuses of the links for the
     * given user.
     */
    public function urlStatusesFor(?User $user): self
    {
        if (!$user) {
            return $this;
        }

        $url_statuses = UrlStatus::listOrBuildByLinks($user, $this->links);
        $user->preloadUrlStatuses($url_statuses);

        return $this;
    }

    /**
     * Preload the number of collections of the given user containing the links.
     */
    public function numberCollectionsFor(?User $user): self
    {
        if (!$user) {
            return $this;
        }

        $numbers_by_url_hashes = Link::countCollectionsForUserByLinks($user, $this->links);

        foreach ($this->links as $link) {
            $number = $numbers_by_url_hashes[$link->url_hash] ?? 0;
            $link->preloadNumberCollectionsForUser($user, $number);
        }

        return $this;
    }
}
