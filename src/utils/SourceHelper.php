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
     * Set the source_* properties of a link if possible.
     */
    public static function setLinkSource(models\Link $link, string $from): void
    {
        list($source_type, $source_resource_id) = self::extractFromPath($from);
        if ($source_type) {
            $link->source_type = $source_type;
            $link->source_resource_id = $source_resource_id;
        }
    }

    /**
     * Return the source type and resource id from a path.
     *
     * For instance:
     *
     * - For the path `/collections/1234567890`, ['collection', '1234567890']
     *   will be returned (if the collection exists in db)
     * - For the path `/p/1234567890`, ['user', '1234567890'] will be
     *   returned (if the user exists in db)
     * - For other paths, ['', null] will be returned
     *
     * @return array{
     *     ''|'collection'|'user',
     *     ?string
     * }
     */
    public static function extractFromPath(string $path): array
    {
        $matches = [];

        $result = preg_match('#^/collections/(?P<id>\d+)$#', $path, $matches);
        if (isset($matches['id'])) {
            $collection_id = $matches['id'];

            if (!models\Collection::exists($collection_id)) {
                return ['', null];
            }

            return ['collection', $collection_id];
        }

        $result = preg_match('#^/p/(?P<id>\d+)$#', $path, $matches);
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
