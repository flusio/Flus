<?php

namespace tests\factories;

use flusio\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Session>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SessionFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Session::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => function () {
                return \Minz\Random::hex(32);
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'name' => function () use ($faker) {
                return "{$faker->word} on {$faker->word}";
            },

            'ip' => function () use ($faker) {
                return $faker->ipv6;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },

            'token' => function () {
                return TokenFactory::create()->token;
            },
        ];
    }
}
