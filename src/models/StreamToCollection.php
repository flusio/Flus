<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'streams_to_collections')]
class StreamToCollection
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $stream_id;

    #[Database\Column]
    public string $collection_id;

    public function __construct(Stream $stream, Collection $collection)
    {
        $this->stream_id = $stream->id;
        $this->collection_id = $collection->id;
    }
}
