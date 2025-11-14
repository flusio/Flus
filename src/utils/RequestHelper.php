<?php

namespace App\utils;

use Minz\Request;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RequestHelper
{
    /**
     * Return the URI which initiated the request.
     *
     * If it's a POST request, or if the request requests a modal rendering, it
     * returns the "Referer". Otherwise, it returns the "self URI" of the
     * request.
     *
     * In case the "Referer" header doesn't exist, it fallbacks on the session
     * _previous_url variable, set at the beginning of the Application::run()
     * method.
     *
     * The returned URI is always redirectable. It means that it's always a
     * a "GET"-able internal URI, so the user can be safely be redirected to
     * it. When no redirectable URI is calculated, the home path ("/") is
     * returned.
     */
    public static function from(Request $request): string
    {
        $self_uri = $request->selfUri();

        $previous_url = $_SESSION['_previous_url'] ?? null;
        if (!is_string($previous_url)) {
            $previous_url = $self_uri;
        }

        $referer = $request->headers->getString('Referer', $previous_url);

        $is_post = $request->method() === 'POST';
        $modal_requested = $request->headers->getString('Turbo-Frame') === 'modal-content';

        if ($is_post || $modal_requested) {
            $from = $referer;
        } else {
            $from = $self_uri;
        }

        $router = \Minz\Engine::router();
        if ($router->isRedirectable($from)) {
            return $from;
        } else {
            return \Minz\Url::for('home');
        }
    }

    /**
     * Store the current URL in the session _previous_url variable.
     *
     * The URL is stored only on GET requests and if it doesn't request a modal
     * rendering.
     */
    public static function setPreviousUrl(Request $request): void
    {
        $self_uri = $request->selfUri();

        $is_get = $request->method() === 'GET';
        $modal_requested = $request->headers->getString('Turbo-Frame') === 'modal-content';

        if ($is_get && !$modal_requested) {
            $_SESSION['_previous_url'] = $self_uri;
        }
    }
}
