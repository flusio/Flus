<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Message>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MessageFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Message::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => function () {
                return \Minz\Random::hex(32);
            },

            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'content' => function () use ($faker) {
                return $faker->paragraphs(3, true);
            },

            'link_id' => function () {
                return LinkFactory::create()->id;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },
        ];
    }
}
