<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\User>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\User::class;
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

            'validated_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'subscription_expired_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'username' => function () use ($faker) {
                return $faker->name;
            },

            'email' => function () use ($faker) {
                return $faker->email;
            },

            'password_hash' => function () use ($faker): string {
                return models\User::passwordHash($faker->password);
            },

            // Force the value to facilitate the tests (i.e. we would have to
            // localize the tests as well, which would be painful)
            'locale' => 'en_GB',
        ];
    }
}
