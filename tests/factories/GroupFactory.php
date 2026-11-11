<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Group>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class GroupFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Group::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn() => $faker->dateTime,

            'name' => fn() => $faker->word,

            'user_id' => fn() => UserFactory::create()->id,
        ];
    }
}
