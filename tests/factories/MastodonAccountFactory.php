<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\MastodonAccount>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MastodonAccountFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\MastodonAccount::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'mastodon_server_id' => function () {
                return MastodonServerFactory::create()->id;
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },

            'username' => '',

            'access_token' => '',

            'options' => [
                'prefill_with_notes' => true,
                'link_to_notes' => true,
                'post_scriptum' => '',
                'post_scriptum_in_all_posts' => false,
            ],
        ];
    }
}
