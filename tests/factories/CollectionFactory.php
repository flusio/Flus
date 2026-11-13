<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Collection>
 *
 * @phpstan-import-type ModelValues from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Collection::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn(): \DateTimeImmutable => \DateTimeImmutable::createFromInterface($faker->dateTime),

            'name' => fn() => $faker->words(3, true),

            'is_public' => fn() => $faker->boolean,

            'owner_id' => fn(): string => UserFactory::create()->owner()->id,
        ];
    }

    /**
     * @param ModelValues $values
     *
     * @return ModelValues
     */
    protected static function beforeBuild(array $values): array
    {
        if (!array_key_exists('user_id', $values)) {
            $owner_id = $values['owner_id'];
            assert(is_string($owner_id));

            $values['user_id'] = models\UserOwner::require($owner_id)->user_id;
        }

        return $values;
    }
}
