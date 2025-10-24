<?php

namespace App\forms\links;

use App\auth;
use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @extends BaseForm<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditLinkCollections extends BaseForm
{
    /** @var string[] */
    #[Form\Field(bind: false)]
    public array $collection_ids = [];

    /** @var string[] */
    #[Form\Field(bind: false, transform: '\App\forms\links\NewLink::trimArray')]
    public array $new_collection_names = [];

    #[Form\Field]
    public bool $is_hidden = false;

    #[Form\Field(bind: false)]
    public bool $mark_as_read = false;

    #[Form\Field(bind: false, transform: 'trim')]
    public string $content = '';

    #[Form\Field(bind: false)]
    public bool $share_on_mastodon = false;

    public int $collection_name_max_length = models\Collection::NAME_MAX_LENGTH;

    /** @var ?array<string, models\Collection[]> */
    private ?array $cache_collection_values = null;

    public function user(): models\User
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            throw new \LogicException('User must be connected.');
        }

        return $user;
    }

    /**
     * Return the collections to display in the select field of the form.
     *
     * Collections are grouped by their groups if any. Non-grouped collections
     * can be found with the empty string key.
     *
     * @return array<string, models\Collection[]>
     */
    public function collectionsValues(): array
    {
        if ($this->cache_collection_values !== null) {
            return $this->cache_collection_values;
        }

        $user = $this->user();

        $collection_values = [];

        $groups = models\Group::listBy(['user_id' => $user->id]);
        $groups = utils\Sorter::localeSort($groups, 'name');

        $bookmarks = $user->bookmarks();

        $collections = $user->collections();
        $collections = utils\Sorter::localeSort($collections, 'name');
        //$collections = array_merge([$bookmarks], $collections);
        $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

        $shared_collections = $user->sharedCollections(options: [
            'access_type' => 'write',
        ]);
        $shared_collections = utils\Sorter::localeSort($shared_collections, 'name');

        // Add the collections with no groups first.
        $collection_values[''] = $groups_to_collections[''] ?? [];

        // Add the collections with groups then
        foreach ($groups as $group) {
            if (isset($groups_to_collections[$group->id])) {
                $collection_values[$group->name] = $groups_to_collections[$group->id];
            }
        }

        // Finally, add the shared collections like it was a distinct group.
        if ($shared_collections) {
            $share_group_name = _('Shared with me');
            $collection_values[$share_group_name] = $shared_collections;
        }

        $this->cache_collection_values = $collection_values;

        return $collection_values;
    }

    /**
     * Find a collection by its id from the collections values.
     */
    public function findCollection(string $collection_id): ?models\Collection
    {
        $collection_values = $this->collectionsValues();

        foreach ($collection_values as $collections) {
            foreach ($collections as $collection) {
                if ($collection->id === $collection_id) {
                    return $collection;
                }
            }
        }

        return null;
    }

    /**
     * Return whether the given collection is selected or not.
     */
    public function isCollectionSelected(models\Collection $collection): bool
    {
        return in_array($collection->id, $this->collection_ids);
    }

    /**
     * Return the list of the selected collections from the collection_ids
     * attribute.
     *
     * @throw new \RuntimeException
     *     Raised if a selected collection_id doesn't match an existing collection.
     *
     * @return models\Collection[]
     */
    public function selectedCollections(): array
    {
        $selected_collections = [];

        foreach ($this->collection_ids as $collection_id) {
            $collection = $this->findCollection($collection_id);

            if (!$collection) {
                throw new \RuntimeException("Collection {$collection_id} does not exist.'");
            }

            $selected_collections[] = $collection;
        }

        return $selected_collections;
    }

    /**
     * Return the list of new collections (to create) from the
     * new_collection_names attribute.
     *
     * @return models\Collection[]
     */
    public function newCollections(): array
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            throw new \LogicException('User must be connected.');
        }

        $collections = [];
        foreach ($this->new_collection_names as $name) {
            $collections[] = models\Collection::init(
                $user->id,
                $name,
                description: '',
                is_public: false,
            );
        }

        return $collections;
    }

    /**
     * @return models\Collection[]
     */
    public function collectionsByOthers(): array
    {
        $user = $this->user();

        if ($this->model === null) {
            throw new \LogicException('collectionsByOthers() cannot be called with a null model.');
        }

        $collections_by_others = models\Collection::listWritableContainingNotOwnedLinkWithUrl(
            $user->id,
            $this->model->url_hash,
        );

        return utils\Sorter::localeSort($collections_by_others, 'name');
    }

    public function note(): ?models\Note
    {
        if ($this->model === null) {
            throw new \LogicException('note() cannot be called with a null model.');
        }

        if (!$this->content) {
            return null;
        }

        $user = $this->user();
        return new models\Note($user->id, $this->model->id, $this->content);
    }

    public function isMastodonConfigured(): bool
    {
        $user = $this->user();
        return models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);
    }

    public function shouldShareOnMastodon(): bool
    {
        return $this->isMastodonConfigured() && $this->share_on_mastodon;
    }

    /**
     * Trim a list of strings.
     *
     * @param string[] $strings
     * @return string[]
     */
    public static function trimArray(array $strings): array
    {
        return array_map('trim', $strings);
    }

    /**
     * Check that the selected collections are part of the collections values.
     */
    #[Validable\Check]
    public function checkSelectedCollections(): void
    {
        try {
            $this->selectedCollections();
        } catch (\RuntimeException $e) {
            $this->addError(
                'collection_ids',
                'invalidCollection',
                _('One of the associated collection doesn’t exist.'),
            );
        }
    }

    /**
     * Check that the new collections are valid (basically, that the names are
     * filled and not too long).
     */
    #[Validable\Check]
    public function checkNewCollections(): void
    {
        foreach ($this->newCollections() as $collection) {
            if (!$collection->validate()) {
                $errors = $collection->errors();
                $error = implode(' ', $errors);
                $this->addError('new_collection_names', 'invalidNewCollection', $error);

                // Stop after getting one error as the others are probably
                // similar.
                break;
            }
        }
    }
}
