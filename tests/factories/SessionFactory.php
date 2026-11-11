<?php

namespace tests\factories;

use App\models;
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
            'id' => fn(): string => \Minz\Random::hex(32),

            'created_at' => fn() => $faker->dateTime,

            'name' => fn(): string => "{$faker->word} on {$faker->word}",

            'ip' => fn() => $faker->ipv6,

            'user_id' => fn() => UserFactory::create()->id,

            'token' => fn() => TokenFactory::create()->token,
        ];
    }
}
