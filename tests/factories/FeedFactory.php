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
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn() => $faker->dateTime,

            'name' => fn() => $faker->words(3, true),

            'feed_url' => fn() => $faker->url,

            'is_public' => true,
        ];
    }
}
