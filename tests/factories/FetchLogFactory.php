<?php

namespace tests\factories;

use App\http;
use Minz\Database;

/**
 * @extends Database\Factory<http\FetchLog>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FetchLogFactory extends Database\Factory
{
    public static function model(): string
    {
        return http\FetchLog::class;
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

            'host' => function () use ($faker) {
                return $faker->domainName;
            },

            'type' => function () use ($faker) {
                return $faker->randomElement(['link', 'feed', 'image']);
            }
        ];
    }
}
