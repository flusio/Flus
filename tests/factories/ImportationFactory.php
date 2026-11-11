<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Importation>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ImportationFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Importation::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => fn() => $faker->dateTime,

            'type' => fn() => $faker->randomElement(models\Importation::VALID_TYPES),

            'status' => fn() => $faker->randomElement(models\Importation::VALID_STATUSES),

            'options' => [],

            'user_id' => fn() => UserFactory::create()->id,
        ];
    }
}
