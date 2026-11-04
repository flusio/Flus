<?php

namespace App\models\users;

use App\models\Stream;
use App\utils;

/**
 * Add methods to access the streams owned by or shared to the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasStreams
{
    public function initStream(): Stream
    {
        return new Stream($this);
    }

    /**
     * Return the list of streams owned by the user.
     *
     * @see Stream::listByUser
     *
     * @param array{
     *     'is_private'?: bool,
     * } $options
     *
     * @return Stream[]
     */
    public function streams(array $options = []): array
    {
        $is_private = $options['is_private'] ?? true;

        $key = sprintf('streams_%d', $is_private);

        return $this->memoize($key, function () use ($options): array {
            $streams = Stream::listByUser($this, $options);

            return utils\Sorter::localeSort($streams, 'name');
        });
    }

    /**
     * Return the list of streams shared with the user.
     *
     * @see Stream::listSharedToUser
     *
     * @return Stream[]
     */
    public function sharedStreams(): array
    {
        return $this->memoize('shared_streams', function (): array {
            $streams = Stream::listSharedToUser($this);

            return utils\Sorter::localeSort($streams, 'name');
        });
    }
}
