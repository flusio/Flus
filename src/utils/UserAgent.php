<?php

namespace App\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserAgent
{
    public static function get(): string
    {
        // Include a link to the about page in the user agent
        $user_agent = \App\Configuration::$application['user_agent'];

        if (\App\Configuration::$environment === 'production') {
            $about_url = \Minz\Url::absoluteFor('about');
        } else {
            $about_url = 'https://github.com/flusio/Flus';
        }

        return "{$user_agent} ({$about_url})";
    }
}
