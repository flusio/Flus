<?php

namespace flusio\utils;

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
     *
     * @param string $user_agent
     *
     * @return string
     */
    public static function format($user_agent)
    {
        $browser_info = @get_browser($user_agent);
        if ($browser_info) {
            $browser = $browser_info->browser;
            $platform = $browser_info->platform;
        } else {
            \Minz\Log::notice('browscap seems to not be configured on the system.'); // @codeCoverageIgnore
            $browser = 'Default Browser'; // @codeCoverageIgnore
            $platform = 'unknown'; // @codeCoverageIgnore
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
