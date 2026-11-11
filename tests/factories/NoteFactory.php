<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Note>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NoteFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Note::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::hex(32),

            'created_at' => fn() => $faker->dateTime,

            'content' => fn() => $faker->paragraphs(3, true),

            'link_id' => fn() => LinkFactory::create()->id,

            'user_id' => fn() => UserFactory::create()->id,
        ];
    }
}
