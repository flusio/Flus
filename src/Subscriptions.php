<?php

namespace flusio;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    /** @var boolean */
    private $enabled;

    public function __construct()
    {
        $app_conf = \Minz\Configuration::$application;
        $this->enabled = $app_conf['subscriptions_enabled'];
    }

    /**
     * Show the subscription page for the current user.
     *
     * @response 404
     *     If subscriptions are not enabled (need a host and a key)
     * @response 302 /login?redirect_to=/subscription
     *     If the user is not connected
     * @response 200
     *     On success
     */
    public function show($request)
    {
        if (!$this->enabled) {
            return Response::notFound('not_found.phtml');
        }

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('subscription'),
            ]);
        }

        return Response::ok('subscriptions/show.phtml');
    }
}
