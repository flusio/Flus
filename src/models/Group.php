<?php

namespace App\models;

use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'groups')]
class Group
{
    use Database\Recordable;
    use Database\Resource;
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
    public string $name;

    #[Database\Column]
    public string $user_id;

    public function __construct(string $user_id, string $name)
    {
        $this->id = \Minz\Random::timebased();
        $this->name = trim($name);
        $this->user_id = $user_id;
    }

    #[Validable\Check]
    public function checkNameIsUnique(): void
    {
        $existing_group = self::findBy([
            'name' => $this->name,
            'user_id' => $this->user_id,
        ]);

        if ($existing_group && $existing_group->id !== $this->id) {
            $this->addError(
                'name',
                'unique',
                _('You already have a group with this name.'),
            );
        }
    }
}
