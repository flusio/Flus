<?php

namespace App\controllers;

use Minz\Mailer;
use Minz\Request;
use Minz\Response;
use App\auth;
use App\mailers;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support extends BaseController
{
    /**
     * Show the support form
     *
     * @response 302 /login?redirect_to=/support
     *     if not connected
     * @response 200
     */
    public function show(): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('support'));

        return Response::ok('support/show.phtml', [
            'subject' => '',
            'message' => '',
            'message_sent' => \Minz\Flash::pop('message_sent'),
        ]);
    }

    /**
     * Send the email to support email
     *
     * @request_param string csrf
     * @request_param string subject
     * @request_param string message
     *
     * @response 302 /login?redirect_to=/support
     *     if not connected
     * @response 400
     *     if the csrf, title or message are invalid
     * @response 302 /support
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('support'));

        $subject = trim($request->param('subject', ''));
        $message = trim($request->param('message', ''));
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('support/show.phtml', [
                'subject' => $subject,
                'message' => $message,
                'message_sent' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $errors = [];
        if (strlen($subject) <= 0) {
            $errors['subject'] = _('The subject is required.');
        }
        if (strlen($message) <= 0) {
            $errors['message'] = _('The message is required.');
        }

        if ($errors) {
            return Response::badRequest('support/show.phtml', [
                'subject' => $subject,
                'message' => $message,
                'message_sent' => false,
                'errors' => $errors,
            ]);
        }

        $bileto = new services\Bileto();

        if ($bileto->isEnabled()) {
            $result = $bileto->sendMessage($user, $subject, $message);
        } else {
            $mailer_job = new Mailer\Job();
            $mailer_job->performAsap(mailers\Support::class, 'sendMessage', $user->id, $subject, $message);

            $mailer_job = new Mailer\Job();
            $mailer_job->performAsap(mailers\Support::class, 'sendNotification', $user->id, $subject);

            $result = true;
        }

        if (!$result) {
            return Response::internalServerError('support/show.phtml', [
                'subject' => $subject,
                'message' => $message,
                'message_sent' => false,
                'errors' => [
                    'message' => _('The message could not be sent due to a server-side problem.'),
                ],
            ]);
        }

        \Minz\Flash::set('message_sent', true);

        return Response::redirect('support');
    }
}
