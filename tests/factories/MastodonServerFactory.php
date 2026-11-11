<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\MastodonServer>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MastodonServerFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\MastodonServer::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => fn() => $faker->dateTime,

            'host' => fn(): string => 'https://' . $faker->domainName,

            'client_id' => fn() => $faker->sha256,

            'client_secret' => fn() => $faker->sha256,
        ];
    }
}
