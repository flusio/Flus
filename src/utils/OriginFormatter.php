<?php

namespace App\utils;

use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OriginFormatter
{
    use utils\Memoizer;

    private static ?self $instance = null;

    public function __construct(
        private ?models\User $context_user,
    ) {
    }

    /**
     * Return the formatter shared by the whole request.
     *
     * The origins are memoized by the formatter, so the callers must share the
     * same instance to benefit from the preloading (@see preloadOrigins).
     *
     * A new instance is returned as soon as the context user changes, as the
     * memoized origins depend on what this user can view.
     */
    public static function instance(?models\User $context_user): self
    {
        if (!self::$instance || self::$instance->context_user?->id !== $context_user?->id) {
            self::$instance = new self($context_user);
        }

        return self::$instance;
    }

    /**
     * Forget the shared formatter.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Return the label of the origin.
     *
     * The label is identical to the origin, except if the origin is an URL:
     *
     * - if the URL corresponds to a collection, a link, or a user, the
     *   corresponding name is returned;
     * - otherwise, the host of the URL is returned.
     */
    public function labelFromOrigin(string $origin): string
    {
        if (!\SpiderBits\Url::isValid($origin)) {
            return $origin;
        }

        $model = $this->modelFromOrigin($origin);

        if ($model instanceof models\User) {
            return $model->username;
        } elseif ($model instanceof models\Link) {
            return $model->title;
        } elseif ($model instanceof models\Collection) {
            return $model->name();
        } else {
            return utils\Belt::host($origin);
        }
    }

    /**
     * Return the owner of the origin, if the origin is a collection.
     */
    public function ownerFromOrigin(string $origin): ?models\User
    {
        $model = $this->modelFromOrigin($origin);

        if (!($model instanceof models\Collection) || !$model->isCollection()) {
            return null;
        }

        return $model->owner();
    }

    /**
     * Return the origin if the origin is a valid URL.
     */
    public function urlFromOrigin(string $origin): string
    {
        if (\SpiderBits\Url::isValid($origin)) {
            return $origin;
        } else {
            return '';
        }
    }

    /**
     * Return the (deprecated) source from the origin.
     *
     * @deprecated Can be removed in version 3.0.0.
     */
    public function sourceFromOrigin(string $origin): ?string
    {
        $model = $this->modelFromOrigin($origin);

        if (!$model) {
            return null;
        }

        $source_type = match ($model::class) {
            models\User::class => 'user',
            models\Collection::class => 'collection',
            default => '',
        };

        if (!$source_type) {
            return null;
        }

        return "{$source_type}#{$model->id}";
    }

    /**
     * Load the models matching with the origins of the given links, in a
     * constant number of queries.
     *
     * Without this, rendering a list of links costs 2 queries per distinct
     * origin (its existence, then the model itself), plus one per link to load
     * the owner of the origin.
     *
     * @param models\Link[] $links
     */
    public function preloadOrigins(array $links): void
    {
        $origins = array_column($links, 'origin');
        $origins = array_filter($origins);
        $origins = array_unique($origins);

        // Parse the origins and build the initial lookup arrays of types and ids.
        $ids_by_types = [];
        $types_and_ids_by_origins = [];

        foreach ($origins as $origin) {
            list($origin_type, $origin_id) = utils\OriginHelper::parseFromPath($origin);

            if (!$origin_type || !$origin_id) {
                // The origin doesn't reference a model of the application: its
                // label is built from the origin itself.
                $this->memoizeValue($origin, null);
                continue;
            }

            $ids_by_types[$origin_type][] = $origin_id;
            $types_and_ids_by_origins[$origin] = [$origin_type, $origin_id];
        }

        // Load the models from the database: 1 request by type, no matter the number of ids.
        $models_by_types = [];

        foreach ($ids_by_types as $origin_type => $origin_ids) {
            $origin_ids = array_values(array_unique($origin_ids));

            $models = match ($origin_type) {
                'collection' => models\Collection::listBy(['id' => $origin_ids]),
                'link' => models\Link::listBy(['id' => $origin_ids]),
                'user' => models\User::listBy(['id' => $origin_ids]),
            };

            $models_by_types[$origin_type] = array_column($models, null, 'id');
        }

        $collections_by_owner_ids = [];

        // Check the access permissions and memoize the model values.
        foreach ($types_and_ids_by_origins as $origin => list($origin_type, $origin_id)) {
            $model = $models_by_types[$origin_type][$origin_id] ?? null;

            // The access is checked here, so the memoized model is the one
            // that modelFromOrigin() would have returned. It costs no query
            // for a public collection or a visible link.
            $must_check_access = ($model instanceof models\Link) || ($model instanceof models\Collection);
            if ($must_check_access && !auth\Access::can($this->context_user, 'view', $model)) {
                $model = null;
            }

            if ($model instanceof models\Collection && $model->isCollection() && $model->user_id) {
                $collections_by_owner_ids[$model->user_id][] = $model;
            }

            $this->memoizeValue($origin, $model);
        }

        // Don't forget to preload the owner values for the collections
        $this->preloadOwners($collections_by_owner_ids);
    }

    /**
     * Set the owners of the given collections, indexed by their user ids.
     *
     * @param array<string, models\Collection[]> $collections_by_owner_ids
     */
    private function preloadOwners(array $collections_by_owner_ids): void
    {
        if (!$collections_by_owner_ids) {
            return;
        }

        $users = models\User::listBy([
            'id' => array_keys($collections_by_owner_ids),
        ]);
        $owners = array_column($users, null, 'id');

        foreach ($collections_by_owner_ids as $owner_id => $collections) {
            foreach ($collections as $collection) {
                $collection->preloadOwner($owners[$owner_id] ?? null);
            }
        }
    }

    /**
     * Return the model (User, Link, or Collection) matching with the origin if any.
     */
    private function modelFromOrigin(string $origin): models\User|models\Link|models\Collection|null
    {
        return $this->memoize($origin, function () use ($origin): models\User|models\Link|models\Collection|null {
            list($origin_type, $origin_id) = utils\OriginHelper::parseFromPath($origin);

            $model = null;

            if ($origin_type === 'user' && $origin_id) {
                $model = models\User::find($origin_id);
            } elseif ($origin_type === 'link' && $origin_id) {
                $model = models\Link::find($origin_id);
            } elseif ($origin_type === 'collection' && $origin_id) {
                $model = models\Collection::find($origin_id);
            }

            $must_check_access = ($model instanceof models\Link) || ($model instanceof models\Collection);
            if ($must_check_access && !auth\Access::can($this->context_user, 'view', $model)) {
                $model = null;
            }

            return $model;
        });
    }
}
