<?php

namespace App\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserAgent
{
    /**
     * @return non-empty-string
     */
    public static function get(): string
    {
        $app_version = \App\Configuration::$app_version;
        $user_agent = "flus/{$app_version}";

        if (\App\Configuration::$environment === 'production') {
            $about_url = \Minz\Url::absoluteFor('about');
        } else {
            $about_url = 'https://github.com/flusio/Flus';
        }

        return "{$user_agent} ({$about_url})";
    }
}
