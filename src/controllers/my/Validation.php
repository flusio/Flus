<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\mailers;
use App\models;
use App\services;
use Minz\Mailer;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Validation extends BaseController
{
    /**
     * Display the validation page.
     *
     * @response 200
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(Request $request): Response
    {
        auth\CurrentUser::require();

        return Response::ok('my/validation/show.html.twig', [
            'status' => \Minz\Flash::pop('status'),
            'error' => \Minz\Flash::pop('error'),
        ]);
    }

    /**
     * Display the validation form.
     *
     * @request_param string t
     *     The validation token.
     *
     * @response 200
     */
    public function new(Request $request): Response
    {
        $form = new forms\users\AccountValidation([
            't' => $request->parameters->getString('t', ''),
        ]);

        return Response::ok('my/validation/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Validate an account.
     *
     * @request_param string t
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /my/account/validation
     *     On success.
     */
    public function create(Request $request): Response
    {
        $form = new forms\users\AccountValidation();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/validation/new.html.twig', [
                'form' => $form,
            ]);
        }

        $user = $form->user();

        models\Token::delete($form->t);

        $user->validation_token = null;
        $user->validated_at = \Minz\Time::now();

        if (\App\Configuration::areSubscriptionsEnabled()) {
            $subscriptions_service = new services\Subscriptions();
            $subscriptions_service->initAccount($user);
        }

        $user->save();

        return Response::redirect('account validation');
    }

    /**
     * Resend a validation email.
     *
     * A new token is generated if the current one expires soon (i.e. <= 30
     * minutes).
     *
     * @request_param string csrf_token
     *
     * @response 302 /my/account/validation
     *     On success or if the user is already validated.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function resendEmail(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        if ($user->isValidated()) {
            // nothing to do, the user is already validated
            return Response::redirect('home');
        }

        $form = new forms\users\ResendValidationEmail();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::redirect('account validation');
        }

        $validation_token = $user->validation_token;

        if (!$validation_token) {
            // The user has no token? This should not happen, but maybe the
            // admin changed something in DB... who knows?
            $token = new models\Token(1, 'day', 16);
            $token->save();
            $user->validation_token = $token->token;
            $user->save();

            $validation_token = $token->token;
        }

        $token = models\Token::find($validation_token);
        if (!$token || $token->expiresIn(30, 'minutes') || $token->isInvalidated()) {
            // the token will expire soon, let's regenerate a new one
            $token = new models\Token(1, 'day', 16);
            $token->save();
            $user->validation_token = $token->token;
            $user->save();
        }

        $mailer_job = new Mailer\Job();
        $mailer_job->performAsap(mailers\Users::class, 'sendAccountValidationEmail', $user->id);

        \Minz\Flash::set('status', 'validation_email_sent');
        return Response::redirect('account validation');
    }
}
