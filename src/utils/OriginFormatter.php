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

    public function __construct(
        private ?models\User $context_user,
    ) {
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
     * Return the model (User, Link, or Collection) matching with the origin if any.
     */
    private function modelFromOrigin(string $origin): models\User|models\Link|models\Collection|null
    {
        return $this->memoize($origin, function () use ($origin): models\User|models\Link|models\Collection|null {
            list($origin_type, $origin_id) = utils\OriginHelper::extractFromPath($origin);

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
