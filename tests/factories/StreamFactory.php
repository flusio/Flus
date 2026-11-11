<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Stream>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class StreamFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Stream::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn() => $faker->dateTime,

            'name' => fn() => $faker->words(3, true),

            'is_public' => fn() => $faker->boolean,

            'user_id' => fn() => UserFactory::create()->id,
        ];
    }
}
