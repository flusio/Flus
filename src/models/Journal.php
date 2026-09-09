<?php

namespace App\models;

use Minz\Database;

/**
 * The journal of a user: a system collection filled with the links published
 * in the collections that the user follows.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Journal extends Collection
{
    public function __construct(User $user)
    {
        parent::__construct();

        $this->name = _('Journal');
        $this->type = 'journal';
        $this->setOwner($user);
    }

    /**
     * Return the owner of the journal.
     */
    public function owner(): User
    {
        return $this->memoize('owner', function (): User {
            if ($this->user_id === null) {
                throw new \LogicException('A journal must have an owner');
            }

            return User::require($this->user_id);
        });
    }

    /**
     * Set the owner of the journal.
     */
    public function setOwner(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * Return whether there are links that can be added to the journal.
     *
     * @see Link::listCandidatesForJournal
     */
    public function hasCandidates(): bool
    {
        return Link::anyCandidateForJournal($this);
    }

    /**
     * Fill the journal with the candidate links and return the number of
     * links in the journal.
     *
     * @see Link::listCandidatesForJournal
     */
    public function fill(int $max): int
    {
        $links = $this->links();
        if (count($links) > 0) {
            return count($links);
        }

        $user = $this->owner();
        $links = Link::listCandidatesForJournal($this, $max);

        foreach ($links as $followed_link) {
            $link = $user->obtainLink($followed_link);
            $link->setSourceId($followed_link->source_id);

            // If the link has already an origin info, we want to keep it.
            // Otherwise, we use the initial source URL.
            if (!$link->origin) {
                $collection_url = \Minz\Url::absoluteFor('collection', [
                    'id' => $link->source_id,
                ]);
                $link->setOrigin($collection_url);
            }

            // Make sure to reset this value: it will be set to true later with
            // Link::groupLinksBySources
            $link->group_by_source = false;

            $link->save();

            // And don't forget to add the link to the journal!
            $link->addCollection(
                $this,
                at: $followed_link->published_at,
                sync_publication_frequency: false,
            );
        }

        Link::groupLinksBySources($this);

        return count($links);
    }

    /**
     * Remove the links having the same URLs as the given ones from the
     * journal.
     *
     * The given links don't have to be attached to the journal themselves
     * (e.g. they may be owned by another user): any link of the journal
     * sharing the same url_hash is detached.
     *
     * @param Link[] $links
     * @param positive-int $chunk_size
     */
    public function removeLinks(array $links, int $chunk_size = 500): void
    {
        $url_hashes = array_unique(array_column($links, 'url_hash'));

        foreach (array_chunk($url_hashes, $chunk_size) as $chunk_url_hashes) {
            $values_as_question_marks = array_fill(0, count($chunk_url_hashes), '?');
            $values_placeholder = implode(', ', $values_as_question_marks);

            $sql = <<<SQL
                DELETE FROM links_to_collections lc
                USING links l
                WHERE lc.link_id = l.id
                AND lc.collection_id = ?
                AND l.url_hash IN ({$values_placeholder})
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute([$this->id, ...$chunk_url_hashes]);
        }
    }
}
