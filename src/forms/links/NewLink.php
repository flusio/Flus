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
class NewLink extends BaseForm
{
    #[Form\Field(transform: '\SpiderBits\Url::sanitize')]
    public string $url = '';

    /** @var string[] */
    #[Form\Field(bind: false)]
    public array $collection_ids = [];

    /** @var string[] */
    #[Form\Field(bind: false, transform: '\App\forms\links\NewLink::trimArray')]
    public array $new_collection_names = [];

    #[Form\Field]
    public bool $is_hidden = false;

    public int $collection_name_max_length = models\Collection::NAME_MAX_LENGTH;

    /** @var ?array<string, models\Collection[]> */
    private ?array $cache_collection_values = null;

    /**
     * @param array<string, mixed> $default_values
     */
    public function __construct(array $default_values = [], ?models\Link $model = null)
    {
        if ($model) {
            $default_collection_ids = $default_values['collection_ids'] ?? [];
            $model_collection_ids = array_column($model->collections(), 'id');

            if (!is_array($default_collection_ids)) {
                throw new \LogicException('collection_ids must be an array.');
            }

            $default_values['collection_ids'] = array_merge(
                $default_collection_ids,
                $model_collection_ids
            );
        }

        parent::__construct($default_values, $model);
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

        $user = auth\CurrentUser::get();

        if (!$user) {
            throw new \LogicException('User must be connected.');
        }

        $collection_values = [];

        $groups = models\Group::listBy(['user_id' => $user->id]);
        $groups = utils\Sorter::localeSort($groups, 'name');

        $bookmarks = $user->bookmarks();

        $collections = $user->collections();
        $collections = utils\Sorter::localeSort($collections, 'name');
        $collections = array_merge([$bookmarks], $collections);
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
     * Check that at least one collection is selected or created.
     */
    #[Validable\Check]
    public function checkAtLeastOneCollection(): void
    {
        if (
            count($this->collection_ids) === 0 &&
            count($this->new_collection_names) === 0
        ) {
            $this->addError(
                'collection_ids',
                'collectionRequired',
                _('The link must be associated to a collection.'),
            );
        }
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
