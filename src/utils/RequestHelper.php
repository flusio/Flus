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
     * The returned URI is always redirectable. It means that it's always a
     * a "GET"-able internal URI, so the user can be safely be redirected to
     * it. When no redirectable URI is calculated, the home path ("/") is
     * returned.
     */
    public static function from(Request $request): string
    {
        $self_uri = $request->selfUri();
        $referer = $request->headers->getString('Referer', $self_uri);

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
}
