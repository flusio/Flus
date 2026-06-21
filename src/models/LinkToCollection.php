<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links_to_collections')]
class LinkToCollection
{
    use dao\LinkToCollection;
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $collection_id;

    public function __construct(string $link_id, string $collection_id)
    {
        $this->link_id = $link_id;
        $this->collection_id = $collection_id;
    }

    /**
     * Attach the collections to the given link and remove old ones if any.
     *
     * This method detaches the link from only collections of type 'collection',
     * even if their ids are missing from $collection_ids. This is because only
     * these collections appear in the collections selector, when a user
     * changes the collections of a link.
     *
     * @param string[] $collection_ids
     */
    public static function setCollections(
        string $link_id,
        array $collection_ids,
        ?\DateTimeImmutable $at = null
    ): bool {
        $previous_attachments = self::listBy(['link_id' => $link_id]);
        $previous_collection_ids = array_column($previous_attachments, 'collection_id');
        $ids_to_attach = array_diff($collection_ids, $previous_collection_ids);
        $ids_to_detach = array_diff($previous_collection_ids, $collection_ids);

        $database = \Minz\Database::get();
        $database->beginTransaction();

        if ($ids_to_attach) {
            self::attach([$link_id], $ids_to_attach, $at);
        }

        if ($ids_to_detach) {
            self::detachCollections([$link_id], $ids_to_detach);
        }

        return $database->commit();
    }
}
