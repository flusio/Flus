<?php

namespace tests\factories;

use flusio\models;
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
            'id' => function () {
                return \Minz\Random::timebased();
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'title' => function () use ($faker) {
                return $faker->words(3, true);
            },

            'url' => function () use ($faker) {
                return $faker->url;
            },

            'url_feeds' => [],

            'is_hidden' => function () use ($faker) {
                return $faker->boolean;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },

            'source_type' => '',
        ];
    }
}
