<?php

namespace App\models;

use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'topics')]
class Topic
{
    use dao\Topic;
    use Database\Recordable;
    use Database\Resource;
    use Validable;

    public const LABEL_MAX_SIZE = 30;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The label is required.'),
    )]
    #[Validable\Length(
        max: self::LABEL_MAX_SIZE,
        message: new Translatable('The label must be less than {max} characters.'),
    )]
    public string $label;

    #[Database\Column]
    public ?string $image_filename;

    public function __construct(string $label)
    {
        $this->id = \Minz\Random::timebased();
        $this->label = trim($label);
    }

    /**
     * Return the number of public collections attached to this topic
     */
    public function countPublicCollections(): int
    {
        return Collection::countPublicByTopicId($this->id);
    }
}
