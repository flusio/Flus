<?php

namespace App\models\link_lists;

use App\models\Link;
use App\models\LinkToList;
use Minz\Database;

/**
 * Add the relation between a list and the links that it contains.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasLinks
{
    #[Database\Column(computed: true)]
    public ?int $number_links = null;

    /**
     * Return the links of the list.
     *
     * @see Link::listComputedByCollection
     *
     * @param string[] $selected_computed_props
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
    public function links(array $selected_computed_props = [], array $options = []): array
    {
        return Link::listComputedByCollection(
            $this,
            $selected_computed_props,
            $options
        );
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
     * @param Link[] $links
     */
    public function addLinks(array $links, ?\DateTimeImmutable $at = null): void
    {
        LinkToList::attach($links, [$this], $at);
    }
}
