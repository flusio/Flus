<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the current session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions
{
    /**
     * Change the current locale.
     *
     * @request_param string csrf
     * @request_param string locale
     * @request_param string back An action pointer to redirect to (optional, default is `home`)
     *
     * @response 302 Always redirect to the `back` param, or `home` (default)
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function changeLocale($request)
    {
        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::redirect($request->param('back', 'home'));
        }

        $locale = $request->param('locale');
        $available_locales = utils\Locale::availableLocales();
        if (isset($available_locales[$locale])) {
            $_SESSION['locale'] = $locale;
        } else {
            \Minz\Log::warning(
                "[Sessions#changeLocale] Tried to set invalid `{$locale}` locale."
            );
        }

        return Response::redirect($request->param('back', 'home'));
    }
}
