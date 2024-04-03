<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Token>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class TokenFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Token::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'token' => function () {
                return \Minz\Random::hex(32);
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'expired_at' => function () use ($faker) {
                return $faker->dateTime;
            },
        ];
    }
}
