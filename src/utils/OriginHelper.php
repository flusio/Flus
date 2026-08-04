<?php

namespace App\utils;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OriginHelper
{
    /**
     * Return the origin type and origin id from a URL or a path, if the
     * corresponding model exists in database.
     *
     * For instance:
     *
     * - For the path `/collections/1234567890`, ['collection', '1234567890']
     *   will be returned (if the collection exists in db)
     * - For the path `/links/1234567890`, ['link', '1234567890'] will be
     *   returned (if the link exists in db)
     * - For the path `/p/1234567890`, ['user', '1234567890'] will be returned
     *   (if the user exists in db)
     * - For other paths, ['', null] will be returned
     *
     * The method also handles URLs starting with the base URL of the application.
     *
     * Prefer parseFromPath() if the model is loaded afterwards: it doesn't
     * query the database.
     *
     * @return array{'collection'|'link'|'user', string}|array{'', null}
     * }
     */
    public static function extractFromPath(string $url_or_path): array
    {
        list($origin_type, $origin_id) = self::parseFromPath($url_or_path);

        if (!$origin_type || !$origin_id) {
            return ['', null];
        }

        $exists = match ($origin_type) {
            'collection' => models\Collection::exists($origin_id),
            'link' => models\Link::exists($origin_id),
            'user' => models\User::exists($origin_id),
        };

        if (!$exists) {
            return ['', null];
        }

        return [$origin_type, $origin_id];
    }

    /**
     * Return the origin type and origin id from a URL or a path.
     *
     * Contrary to extractFromPath(), the existence of the corresponding model
     * is not verified: no query is executed.
     *
     * @return array{'collection'|'link'|'user', string}|array{'', null}
     * }
     */
    public static function parseFromPath(string $url_or_path): array
    {
        $base_url = \Minz\Url::baseUrl();

        if (str_starts_with($url_or_path, $base_url)) {
            $path = substr($url_or_path, strlen($base_url));
        } else {
            $path = $url_or_path;
        }

        $query_position = strpos($path, '?');
        if ($query_position !== false) {
            $path = substr($path, 0, $query_position);
        }

        $matches = [];

        preg_match('#^/collections/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            return ['collection', $matches['id']];
        }

        preg_match('#^/links/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            return ['link', $matches['id']];
        }

        preg_match('#^/p/(?P<id>\d+)/?#', $path, $matches);
        if (isset($matches['id'])) {
            return ['user', $matches['id']];
        }

        return ['', null];
    }
}
