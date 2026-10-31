<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Feed>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Feed::class;
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

            'name' => function () use ($faker) {
                return $faker->words(3, true);
            },

            'feed_url' => function () use ($faker) {
                return $faker->url;
            },

            'is_public' => true,
        ];
    }
}
