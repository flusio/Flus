<?php

namespace flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Icon
{
    /** @var string[] */
    private static $cache = [];

    /**
     * Return the SVG icon corresponding to the given name.
     *
     * @param string $icon_name
     *
     * @throws \DomainException if the icon doesn’t exist
     *
     * @return string
     */
    public static function get($icon_name)
    {
        if (!isset(self::$cache[$icon_name])) {
            $icons_path = \Minz\Configuration::$app_path . '/src/assets/icons';
            $icon_path = "{$icons_path}/{$icon_name}.svg";

            $icon = @file_get_contents($icon_path);
            if ($icon === false) {
                throw new \DomainException("The icon {$icon_name} doesn't exist.");
            }

            self::$cache[$icon_name] = $icon;
        }

        return self::$cache[$icon_name];
    }
}
