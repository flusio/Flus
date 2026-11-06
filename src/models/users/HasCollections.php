<?php

namespace App\models\users;

use App\models\Collection;

/**
 * Add methods to access the collections owned by or shared to the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasCollections
{
    public function initCollection(): Collection
    {
        return Collection::init($this);
    }

    /**
     * Return the collections of the user.
     *
     * @see Collection::listByUser
     *
     * @param array{
     *     'private'?: bool,
     *     'with_visible_links'?: bool,
     * } $options
     *
     * @return Collection[]
     */
    public function collections(array $options = []): array
    {
        return Collection::listByUser($this, $options);
    }

    /**
     * Return the collections shared to the user.
     *
     * @see Collection::listSharedToUser
     *
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * @return Collection[]
     */
    public function sharedCollections(array $options = []): array
    {
        return Collection::listSharedToUser($this, $options);
    }

    /**
     * Return whether the user owns the given collection.
     */
    public function isOwningCollection(Collection $collection): bool
    {
        return $collection->user_id === $this->id;
    }
}
