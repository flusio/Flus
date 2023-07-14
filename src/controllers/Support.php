<?php

namespace flusio\controllers;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\jobs;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support
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
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('support'),
            ]);
        }

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
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('support'),
            ]);
        }

        $subject = trim($request->param('subject', ''));
        $message = trim($request->param('message', ''));
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('support/show.phtml', [
                'subject' => $subject,
                'message' => $message,
                'message_sent' => null,
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
                'message_sent' => null,
                'errors' => $errors,
            ]);
        }

        $mailer_job = new jobs\Mailer();
        $mailer_job->performAsap('Support', 'sendMessage', $user->id, $subject, $message);

        $mailer_job = new jobs\Mailer();
        $mailer_job->performAsap('Support', 'sendNotification', $user->id, $subject);

        \Minz\Flash::set('message_sent', true);

        return Response::redirect('support');
    }
}
