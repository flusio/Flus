<?php

namespace tests\factories;

use flusio\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Collection>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Collection::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => function () {
                return \Minz\Random::timebased();
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'name' => function () use ($faker) {
                return $faker->words(3, true);
            },

            'type' => function () use ($faker) {
                return $faker->randomElement(models\Collection::VALID_TYPES);
            },

            'is_public' => function () use ($faker) {
                return $faker->boolean;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },
        ];
    }
}
