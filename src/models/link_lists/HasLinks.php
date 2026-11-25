<?php

namespace App\models\link_lists;

use App\models\Link;
use App\models\LinkToList;
use App\models\User;

/**
 * Add the relation between a list and the links that it contains.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasLinks
{
    /**
     * Return the links of the list.
     *
     * @see Link::listByList
     *
     * @param array{
     *     'published_date'?: ?\DateTimeImmutable,
     *     'source'?: ?string,
     *     'hidden'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * @return Link[]
     */
    public function links(array $options = []): array
    {
        return Link::listByList($this, $options);
    }

    /**
     * Return whether the link is in the list.
     */
    public function hasLink(Link $link): bool
    {
        return LinkToList::existsBy([
            'link_id' => $link->id,
            'collection_id' => $this->id,
        ]);
    }

    /**
     * Add the links to the list.
     *
     * The given user is the person who adds the links to the list. It is null
     * only for feeds (no one add links to them).
     *
     * @param Link[] $links
     */
    public function addLinks(array $links, ?User $by, ?\DateTimeImmutable $at = null): void
    {
        LinkToList::attach($links, [$this], [
            'created_at' => $at,
            'user_id' => $by?->id,
        ]);
    }

    /**
     * Return the user who added the link to the list.
     */
    public function linkAddedBy(Link $link): ?User
    {
        $link_to_list = LinkToList::findBy([
            'link_id' => $link->id,
            'collection_id' => $this->id,
        ]);

        if (!$link_to_list || !$link_to_list->user_id) {
            return null;
        }

        return User::require($link_to_list->user_id);
    }
}
