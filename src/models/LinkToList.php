<?php

namespace App\models;

use App\models\database\Pivot;
use Minz\Database;

/**
 * Connect links to the lists containing them (collections, feeds and
 * journals).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links_to_collections')]
class LinkToList
{
    use dao\BulkQueries;
    use Database\Recordable;
    use Pivot;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $collection_id;

    public function __construct(Link $link, LinkList $link_list)
    {
        $this->link_id = $link->id;
        $this->collection_id = $link_list->id;
    }

    public static function pivotColumns(): array
    {
        return ['link_id', 'collection_id'];
    }
}
