<?php

namespace App\services;

use App\models;

/**
 * The NewsPicker service is a sort of basic artificial intelligence. Its
 * purpose is to select a bunch of links relevant for a given user.
 *
 * @phpstan-type Options array{
 *     'number_links': int,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsPicker
{
    private const DEFAULT_OPTIONS = [
        'number_links' => 9,
    ];

    private models\User $user;

    /** @var Options */
    private array $options;

    /**
     * @param array{
     *     'number_links'?: int,
     * } $options
     */
    public function __construct(models\User $user, array $options = [])
    {
        $this->user = $user;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * Pick and return a set of links relevant for news.
     *
     * @return models\Link[]
     */
    public function pick(): array
    {
        $excluded_hashes = models\Link::listHashesExcludedFromNews($this->user->id);
        $links_from_followed = models\Link::listFromFollowedCollections($this->user->id);

        $links = [];

        foreach ($links_from_followed as $link) {
            $hash = $link->url_hash;

            if (isset($excluded_hashes[$hash])) {
                continue;
            }

            $links[$hash] = $link;

            if (count($links) >= $this->options['number_links']) {
                break;
            }
        }

        return array_values($links);
    }
}
