<?php

namespace flusio;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Messages
{
    /**
     * Delete a message
     *
     * @request_param string id
     * @request_param string redirect_to default is /
     *
     * @response 302 /login?redirect_to=:redirect_to if not connected
     * @response 404 if the message doesn’t exist or user hasn't access
     * @response 302 :redirect_to if csrf is invalid
     * @response 302 :redirect_to on success
     */
    public function delete($request)
    {
        $user = utils\CurrentUser::get();
        $message_id = $request->param('id');
        $redirect_to = $request->param('redirect_to', \Minz\Url::for('home'));

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $redirect_to]);
        }

        $message = models\Message::findBy([
            'id' => $message_id,
            'user_id' => $user->id,
        ]);
        if (!$message) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($redirect_to);
        }

        models\Message::delete($message->id);

        return Response::found($redirect_to);
    }
}
