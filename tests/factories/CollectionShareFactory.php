<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\CollectionShare>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionShareFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\CollectionShare::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },

            'collection_id' => function () {
                return CollectionFactory::create()->id;
            },

            'type' => function () use ($faker) {
                return $faker->randomElement(models\CollectionShare::VALID_TYPES);
            },
        ];
    }
}
