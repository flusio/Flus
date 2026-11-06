<?php

namespace App\models;

use App\models\database\Pivot;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collections_to_topics')]
class CollectionToTopic
{
    use Database\Recordable;
    use Pivot;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $collection_id;

    #[Database\Column]
    public string $topic_id;

    public static function pivotColumns(): array
    {
        return ['collection_id', 'topic_id'];
    }
}
