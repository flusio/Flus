<?php

namespace tests\factories;

use App\http;
use Minz\Database;

/**
 * @extends Database\Factory<http\FetchError>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FetchErrorFactory extends Database\Factory
{
    public static function model(): string
    {
        return http\FetchError::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'url' => function () use ($faker) {
                return $faker->url;
            },

            'type' => function () use ($faker) {
                return $faker->randomElement(['link', 'feed', 'image']);
            },

            'code' => function () use ($faker) {
                return $faker->randomElement([0, 404, 500]);
            },

            'content' => function () use ($faker) {
                return $faker->sentence;
            },
        ];
    }
}
