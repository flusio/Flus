<?php

namespace App\models;

use App\models\database\BulkQueries;
use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * A list of links stored in the collections table.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collections')]
#[Database\Inheritance(column: 'type', classes: [
    'collection' => Collection::class,
    'feed' => Feed::class,
    'journal' => Journal::class,
])]
abstract class LinkList
{
    use BulkQueries;
    use Database\Recordable;
    use Database\Resource;
    use link_lists\HasLinks;
    use utils\Memoizer;
    use Validable;

    public const NAME_MAX_LENGTH = 100;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The name is required.'),
    )]
    #[Validable\Length(
        max: self::NAME_MAX_LENGTH,
        message: new Translatable('The name must be less than {max} characters.'),
    )]
    public string $name = '';

    #[Database\Column]
    public string $type;

    public function __construct()
    {
        $this->id = \Minz\Random::timebased();
    }

    /**
     * Return the name of the list.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:collections/{$this->id}";
    }
}
