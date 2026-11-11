<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Link>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Link::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn() => $faker->dateTime,

            'title' => fn() => $faker->words(3, true),

            'url' => fn() => $faker->url,

            'url_feeds' => [],

            'is_hidden' => fn() => $faker->boolean,

            'user_id' => fn() => UserFactory::create()->id,

            'source_type' => '',
        ];
    }
}
