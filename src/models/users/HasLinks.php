<?php

namespace App\models\users;

use App\models\Link;

/**
 * Add methods to manage the links owned by the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasLinks
{
    /**
     * Return whether the user owns the given link.
     */
    public function isOwningLink(Link $link): bool
    {
        return $link->user_id === $this->id;
    }

    /**
     * Return the links of the user.
     *
     * @see Link::listByUser
     *
     * @param array{
     *     'unshared'?: bool,
     *     'tag'?: string,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * @return Link[]
     */
    public function links(array $options = []): array
    {
        return Link::listByUser($this, $options);
    }

    /**
     * Return the number of links of the user.
     *
     * @see Link::countByUser
     *
     * @param array{
     *     'unshared'?: bool,
     *     'tag'?: string,
     * } $options
     */
    public function countLinks(array $options = []): int
    {
        return Link::countByUser(
            $this,
            $options
        );
    }

    /**
     * Return links owned by the user with the same URLs as the given ones.
     *
     * If a link is already owned, it's returned as it is. If a user already
     * has a link with the same URL, it's fetched from the database. Otherwise,
     * the link is copied to the user links but it's not saved in database
     * yet! (created_at will be null then)
     *
     * Order of links is not preserved, and only one link is returned per URL
     * (i.e. calculated from url_hash).
     *
     * @param Link[] $links
     *
     * @return Link[]
     */
    public function obtainLinks(array $links): array
    {
        // First, dispatch the links in two lists: owned and not owned links.
        // Both lists are indexed by url_hash so the same URL is not copied (or
        // returned) several times.
        $owned_links = [];
        $not_owned_links = [];
        foreach ($links as $link) {
            if ($this->isOwningLink($link)) {
                $owned_links[$link->url_hash] = $link;
                unset($not_owned_links[$link->url_hash]);
            } elseif (!isset($owned_links[$link->url_hash])) {
                $not_owned_links[$link->url_hash] ??= $link;
            }
        }

        if (!$not_owned_links) {
            // All the links are owned, so we have nothing more to do
            return array_values($owned_links);
        }

        // Complete the owned_links list with links owned by the user, from the
        // database.
        $urls = array_column($not_owned_links, 'url');
        $related_links = Link::listByUserAndUrls($this, $urls);
        $owned_links += $related_links;

        // The last not owned links must be copied to the current user. These
        // links must be saved in the database!
        foreach ($not_owned_links as $url_hash => $link) {
            $owned_links[$url_hash] ??= Link::copy($link, $this);
        }

        return array_values($owned_links);
    }

    /**
     * Return a link owned by the user with the same URL as the given one.
     *
     * @see self::obtainLinks
     */
    public function obtainLink(Link $link): Link
    {
        return $this->obtainLinks([$link])[0];
    }

    /**
     * Return a link with the given URL owned by the current user. Any matching
     * link that already exists in the database is returned.
     */
    public function findOrBuildLink(string $url): Link
    {
        return Link::findOrBuildByUrl($url, $this);
    }

    /**
     * Return a link owned by the user, with the same URL, if any.
     */
    public function correspondingOwnedLink(Link $link): ?Link
    {
        if ($this->isOwningLink($link)) {
            return $link;
        }

        return Link::findBy([
            'user_id' => $this->id,
            'url_hash' => $link->url_hash,
        ]);
    }

    /**
     * Return a list of suggested links for the user.
     *
     * Suggested links have the same URL as the given one, but are from
     * other users if they added notes to them.
     *
     * @return Link[]
     */
    public function suggestedLinksFor(Link $link): array
    {
        $suggested_links = Link::listSuggestedFor($this, $link);
        $owned_link = $this->correspondingOwnedLink($link);
        if ($owned_link && !$this->isOwningLink($link)) {
            array_unshift($suggested_links, $owned_link);
        }
        return $suggested_links;
    }
}
