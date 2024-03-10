<?php

namespace flusio\controllers\my;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preferences
{
    /**
     * Show the preferences page.
     *
     * @response 302 /login?redirect_to=/my/preferences
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('preferences'),
            ]);
        }

        return Response::ok('my/preferences/edit.phtml', [
            'locale' => $user->locale,
            'option_compact_mode' => $user->option_compact_mode,
            'accept_contact' => $user->accept_contact,
            // Don't name it "beta_enabled" because there's already a global
            // view variable named like this.
            'is_beta_enabled' => models\FeatureFlag::isEnabled('beta', $user->id),
        ]);
    }

    /**
     * Update the preferences of the current user.
     *
     * @request_param string csrf
     * @request_param string locale
     * @request_param bool option_compact_mode
     * @request_param bool accept_contact
     * @request_param bool beta_enabled
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected
     * @response 400
     *     If the CSRF or locale are invalid
     * @response 302 :from
     *     On success
     */
    public function update(Request $request): Response
    {
        $locale = $request->param('locale', '');
        $option_compact_mode = $request->paramBoolean('option_compact_mode');
        $accept_contact = $request->paramBoolean('accept_contact');
        $beta_enabled = $request->paramBoolean('beta_enabled');
        $csrf = $request->param('csrf', '');
        $from = $request->param('from', '');

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('my/preferences/edit.phtml', [
                'locale' => $locale,
                'option_compact_mode' => $option_compact_mode,
                'accept_contact' => $accept_contact,
                'is_beta_enabled' => $beta_enabled,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_locale = $user->locale;
        $user->locale = trim($locale);
        $user->option_compact_mode = $option_compact_mode;
        $user->accept_contact = $accept_contact;

        $errors = $user->validate();
        if ($errors) {
            $user->locale = $old_locale;
            return Response::badRequest('my/preferences/edit.phtml', [
                'locale' => $locale,
                'option_compact_mode' => $option_compact_mode,
                'accept_contact' => $accept_contact,
                'is_beta_enabled' => $beta_enabled,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        $user->save();
        utils\Locale::setCurrentLocale($locale);

        if ($beta_enabled) {
            models\FeatureFlag::enable('beta', $user->id);
        } else {
            models\FeatureFlag::disable('beta', $user->id);
        }

        return Response::found($from);
    }
}
