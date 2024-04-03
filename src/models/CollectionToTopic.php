<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collections_to_topics')]
class CollectionToTopic
{
    use dao\CollectionToTopic;
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $collection_id;

    #[Database\Column]
    public string $topic_id;
}
