<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\mailers;
use App\services;
use Minz\Mailer;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support extends BaseController
{
    /**
     * Show the support form.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\Support();

        return Response::ok('support/show.html.twig', [
            'form' => $form,
            'message_sent' => \Minz\Flash::pop('message_sent'),
        ]);
    }

    /**
     * Send the email to support email
     *
     * @request_param string subject
     * @request_param string message
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 500
     *     If sending the message to Bileto failed.
     * @response 302 /support
     * @flash message_sent
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\Support();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('support/show.html.twig', [
                'form' => $form,
                'message_sent' => false,
            ]);
        }

        $bileto = new services\Bileto();

        if ($bileto->isEnabled()) {
            $result = $bileto->sendMessage($user, $form->subject, $form->message);
        } else {
            $mailer_job = new Mailer\Job();
            $mailer_job->performAsap(
                mailers\Support::class,
                'sendMessage',
                $user->id,
                $form->subject,
                $form->message,
            );

            $mailer_job = new Mailer\Job();
            $mailer_job->performAsap(
                mailers\Support::class,
                'sendNotification',
                $user->id,
                $form->subject,
            );

            $result = true;
        }

        if (!$result) {
            $form->addError(
                '@base',
                'server_error',
                _('The message could not be sent due to a server-side problem.'),
            );

            return Response::internalServerError('support/show.html.twig', [
                'form' => $form,
                'message_sent' => false,
            ]);
        }

        \Minz\Flash::set('message_sent', true);

        return Response::redirect('support');
    }
}
