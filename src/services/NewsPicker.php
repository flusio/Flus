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
        $links = models\Link::listFromFollowedCollectionsForNews($this->user->id);
        $links = $this->mergeByUrl($links);
        return array_slice($links, 0, $this->options['number_links']);
    }

    /**
     * Removes duplicated links urls.
     *
     * @param models\Link[] $links
     *
     * @return models\Link[]
     */
    private function mergeByUrl(array $links): array
    {
        $by_url = [];
        foreach ($links as $link) {
            $by_url[$link->url] = $link;
        }
        return array_values($by_url);
    }
}
