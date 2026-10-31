<?php

namespace App\models;

use Minz\Database;

/**
 * A list of links that users can follow.
 *
 * @phpstan-import-type ShareType from CollectionShare
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
abstract class Source extends LinkList
{
    use dao\MediaQueries;
    use link_lists\Followable;
    use link_lists\InStreams;
    use link_lists\Publishing;

    #[Database\Column]
    public string $description = '';

    #[Database\Column]
    public bool $is_public = false;

    #[Database\Column]
    public ?string $image_filename = null;

    /**
     * Return the URL of the feed to subscribe to the source.
     */
    abstract public function feedUrl(bool $direct = false): string;

    /**
     * Return the description as HTML.
     */
    abstract public function descriptionAsHtml(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function toJson(User $context_user): array;

    public function isFeed(): bool
    {
        return $this instanceof Feed;
    }

    public function isCollection(): bool
    {
        return $this instanceof Collection;
    }

    /**
     * Return the owner of the source, if any.
     */
    public function owner(): ?User
    {
        return null;
    }

    /**
     * Return the list of users with write access.
     *
     * @return User[]
     */
    public function publishers(): array
    {
        return [];
    }

    /**
     * Return the shares attached to the source.
     *
     * @param array{
     *     'access_type'?: ShareType|'any',
     * } $options
     *
     * @return CollectionShare[]
     */
    public function shares(array $options = []): array
    {
        return [];
    }

    /**
     * Return the topics attached to the source.
     *
     * @return Topic[]
     */
    public function topics(): array
    {
        return [];
    }

    /**
     * Return whether the source is shared with the given user.
     *
     * @param ShareType|'any' $access_type
     */
    public function sharedWith(User $user, string $access_type = 'any'): bool
    {
        return false;
    }

    /**
     * Add the links to the source.
     *
     * @param Link[] $links
     */
    public function addLinks(
        array $links,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        parent::addLinks($links, $at);

        if ($sync_publication_frequency) {
            $this->syncPublicationFrequencyPerYear();
            $this->save();
        }
    }

    /**
     * Return the sources of the given links, indexed by their ids.
     *
     * The links without any source, and the sources that don't exist anymore,
     * are absent from the returned array.
     *
     * @param Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self>
     */
    public static function listFromLinks(array $links, int $chunk_size = 1000): array
    {
        $source_ids = array_column($links, 'source_id');
        $source_ids = array_filter($source_ids);
        $source_ids = array_unique($source_ids);
        $source_ids = array_values($source_ids);

        $sources_by_ids = [];

        foreach (array_chunk($source_ids, $chunk_size) as $chunk_source_ids) {
            $sources = self::listBy(['id' => $chunk_source_ids]);

            foreach ($sources as $source) {
                $sources_by_ids[$source->id] = $source;
            }
        }

        return $sources_by_ids;
    }

    /**
     * Return the clause limiting the sources (aliased as "c") to those that the
     * given user can view, or to the public ones if no user is given.
     *
     * The :user_id parameter must be bound when a user is given.
     *
     * @see \App\auth\LinkListsAccess::canView
     */
    private static function buildVisibilityClause(?User $user): string
    {
        if (!$user) {
            return <<<SQL
                AND c.type IN ('collection', 'feed')
                AND c.is_public = true
            SQL;
        }

        return <<<SQL
            AND c.type IN ('collection', 'feed')
            AND (
                c.is_public = true
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )
        SQL;
    }
}
