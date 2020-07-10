<?php

namespace tests;

/**
 * Provide a fake method, calling the Faker library
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FakerHelper
{
    /** @var \Faker\Generator */
    private static $faker;

    /**
     * @beforeClass
     */
    public static function initializeFaker()
    {
        self::$faker = \Faker\Factory::create();
    }

    /**
     * Return the result of faker->$factory_name
     *
     * @see https://github.com/fzaninotto/Faker#faker
     *
     * @param string $factory_name
     * @param mixed $args,... Parameter to pass to the Faker factory
     *
     * @return mixed
     */
    public function fake($factory_name, ...$args)
    {
        return call_user_func_array([self::$faker, $factory_name], $args);
    }

    /**
     * Return the result of faker->unique()->$factory_name
     *
     * @see https://github.com/fzaninotto/Faker#faker
     *
     * @param string $factory_name
     * @param mixed $args,... Parameter to pass to the Faker factory
     *
     * @return mixed
     */
    public function fakeUnique($factory_name, ...$args)
    {
        $unique_generator = self::$faker->unique();
        return call_user_func_array([$unique_generator, $factory_name], $args);
    }
}
