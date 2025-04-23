<?php

namespace App\controllers\my;

use Minz\Mailer;
use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\mailers;
use App\models;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Validation extends BaseController
{
    /**
     * Display the validation page.
     *
     * @response 302 /my/account/validation?t=:t
     *     If the url was called with a token (temporary while migrating to
     *     the new system).
     * @response 302 /login?redirect_to=/my/account/validation
     *     If the user is not connected
     * @response 200
     *     If the current user is not yet validated
     */
    public function show(Request $request): Response
    {
        $token = $request->param('t');
        if ($token) {
            return Response::redirect('new account validation', [
                't' => $token,
            ]);
        }

        $current_user = auth\CurrentUser::get();

        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account validation'),
            ]);
        }

        return Response::ok('my/validation/show.phtml');
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
        $form = new forms\AccountValidation([
            't' => $request->param('t', ''),
        ]);

        return Response::ok('my/validation/new.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Validate an account.
     *
     * @request_param string t
     * @request_param string csrf
     *
     * @response 400
     *     If the token or if the csrf token are invalid.
     * @response 302 /my/account/validation
     *     If the account has been validated.
     */
    public function create(Request $request): Response
    {
        $form = new forms\AccountValidation();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/validation/new.phtml', [
                'form' => $form,
            ]);
        }

        $user = $form->getUser();

        models\Token::delete($form->t);

        $user->validation_token = null;
        $user->validated_at = \Minz\Time::now();

        $sub_enabled = \App\Configuration::$application['subscriptions_enabled'];
        if ($sub_enabled) {
            $sub_host = \App\Configuration::$application['subscriptions_host'];
            $sub_private_key = \App\Configuration::$application['subscriptions_private_key'];
            $subscriptions_service = new services\Subscriptions(
                $sub_host,
                $sub_private_key,
            );
            $account = $subscriptions_service->account($user->email);
            if ($account) {
                $user->subscription_account_id = $account['id'];
                $user->subscription_expired_at = $account['expired_at'];
            } else {
                \Minz\Log::error("Can’t get a subscription account for user {$user->id}."); // @codeCoverageIgnore
            }
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
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/my/account/validation
     *     If the user is not connected
     * @response 302 /my/account/validation
     */
    public function resendEmail(Request $request): Response
    {
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('account validation'));

        if ($user->validated_at) {
            // nothing to do, the user is already validated
            return Response::redirect('home');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
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
