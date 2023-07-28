<?php

namespace tests\factories;

use flusio\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\PocketAccount>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class PocketAccountFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\PocketAccount::class;
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
        ];
    }
}
