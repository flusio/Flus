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
     * @see Collection::listComputedByUser
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'private'?: bool,
     *     'count_hidden'?: bool,
     * } $options
     *
     * @return Collection[]
     */
    public function collections(array $selected_computed_props = [], array $options = []): array
    {
        return Collection::listComputedByUser(
            $this,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections shared to the user.
     *
     * @see Collection::listComputedSharedToUser
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * @return Collection[]
     */
    public function sharedCollections(array $selected_computed_props = [], array $options = []): array
    {
        return Collection::listComputedSharedToUser(
            $this,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return whether the user owns the given collection.
     */
    public function isOwningCollection(Collection $collection): bool
    {
        return $collection->user_id === $this->id;
    }
}
