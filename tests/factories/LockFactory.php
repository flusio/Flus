<?php

namespace tests\factories;

use App\services;
use Minz\Database;

/**
 * @extends Database\Factory<services\Lock>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LockFactory extends Database\Factory
{
    public static function model(): string
    {
        return services\Lock::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'key' => function (): string {
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
