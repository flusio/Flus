<?php

namespace App\utils;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SourceHelper
{
    /**
     * Return the source type and resource id from a URL or a path.
     *
     * For instance:
     *
     * - For the path `/collections/1234567890`, ['collection', '1234567890']
     *   will be returned (if the collection exists in db)
     * - For the path `/p/1234567890`, ['user', '1234567890'] will be
     *   returned (if the user exists in db)
     * - For other paths, ['', null] will be returned
     *
     * The method also handles URLs starting with the base URL of the application.
     *
     * @return array{'collection'|'user', string}|array{'', null}
     * }
     */
    public static function extractFromPath(string $url_or_path): array
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

        $result = preg_match('#^/collections/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            $collection_id = $matches['id'];

            if (!models\Collection::exists($collection_id)) {
                return ['', null];
            }

            return ['collection', $collection_id];
        }

        $result = preg_match('#^/p/(?P<id>\d+)/?#', $path, $matches);
        if (isset($matches['id'])) {
            $user_id = $matches['id'];

            if (!models\User::exists($user_id)) {
                return ['', null];
            }

            return ['user', $user_id];
        }

        return ['', null];
    }
}
