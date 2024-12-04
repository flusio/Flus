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
            'id' => function (): string {
                return \Minz\Random::timebased();
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'name' => function () use ($faker) {
                return $faker->word;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },
        ];
    }
}
