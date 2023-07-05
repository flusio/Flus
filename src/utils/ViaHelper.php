<?php

namespace flusio\utils;

use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ViaHelper
{
    /**
     * Set the via_* properties of a link if possible.
     */
    public static function setLinkVia(models\Link $link, string $from): void
    {
        list($via_type, $via_resource_id) = self::extractFromPath($from);
        if ($via_type) {
            $link->via_type = $via_type;
            $link->via_resource_id = $via_resource_id;
        }
    }

    /**
     * Return the via type and resource id from a path.
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
