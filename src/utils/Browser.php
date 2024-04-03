<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Browser
{
    /**
     * Format a user agent into a human-readable string.
     *
     * If browscap isn't configured, it will always return “Unknown browser on
     * unknown platform”.
     *
     * @see https://www.php.net/manual/function.get-browser.php
     */
    public static function format(string $user_agent): string
    {
        /** @var array<string, mixed>|false */
        $browser_info = @get_browser($user_agent, return_array: true);

        $browser = 'Default Browser';
        $platform = 'unknown';

        if ($browser_info === false) {
            \Minz\Log::notice('browscap seems to not be configured on the system.'); // @codeCoverageIgnore
        } else {
            if (isset($browser_info['browser'])) {
                /** @var string */
                $browser = $browser_info['browser'];
            }
            if (isset($browser_info['platform'])) {
                /** @var string */
                $platform = $browser_info['platform'];
            }
        }

        if ($browser === 'Default Browser') {
            $browser = _('Unknown browser');
        }
        if ($platform === 'unknown') {
            $platform = _('unknown platform');
        }

        return vsprintf(_('%s on %s'), [$browser, $platform]);
    }
}
