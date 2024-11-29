<?php

namespace App;

use Minz\Request;

/**
 * This is the central class of the application. It declares routes and
 * executes a Request to return a Response.
 *
 * It is called from the public/index.php file which is the entrypoint from a
 * Web browser.
 *
 * Example:
 *
 * $request = new \Minz\Request('get', '/');
 * $application = new \App\Application();
 * $response = $application->run($request);
 * echo $response->render();
 *
 * @phpstan-import-type ResponseReturnable from \Minz\Response
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /**
     * Setup the Engine.
     */
    public function __construct()
    {
        // Initialize the engine
        $router = Router::load();
        \Minz\Engine::init($router, [
            'start_session' => true,
            'not_found_view_pointer' => 'not_found.phtml',
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
            'controller_namespace' => '\\App\\controllers',
        ]);

        // Automatically declare content types for these views files extensions
        \Minz\Output\View::$extensions_to_content_types['.atom.xml.php'] = 'application/xml';
        \Minz\Output\View::$extensions_to_content_types['.opml.xml.php'] = 'text/x-opml';
        \Minz\Output\View::$extensions_to_content_types['.xsl.php'] = 'application/xslt+xml';

        // Include a link to the about page in the user agent
        $user_agent = \App\Configuration::$application['user_agent'];
        if (\App\Configuration::$environment === 'production') {
            $about_url = \Minz\Url::absoluteFor('about');
        } else {
            $about_url = 'https://github.com/flusio/Flus';
        }
        \App\Configuration::$application['user_agent'] = "{$user_agent} ({$about_url})";
    }

    /**
     * Declare global View variables and execute a request.
     *
     * @return ResponseReturnable
     */
    public function run(\Minz\Request $request): mixed
    {
        /** @var ?string */
        $session_token = $request->cookie('session_token');

        if (
            !$session_token &&
            auth\CurrentUser::sessionToken() &&
            // We should probably make sure to pass the session token in the
            // tests, but it would be a lot of changes. I prefer to bypass this
            // security in the tests for now.
            \App\Configuration::$environment !== 'test'
        ) {
            auth\CurrentUser::reset();
        } elseif (
            $session_token &&
            auth\CurrentUser::sessionToken() !== $session_token
        ) {
            auth\CurrentUser::setSessionToken($session_token);
        }

        $current_user = auth\CurrentUser::get();
        $beta_enabled = false;
        $locale = utils\Locale::DEFAULT_LOCALE;
        $autoload_modal_url = null;

        // Setup current localization
        if ($current_user) {
            $locale = $current_user->locale;
        } elseif (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            /** @var string */
            $http_accept_language = $request->header('HTTP_ACCEPT_LANGUAGE', '');
            $locale = utils\Locale::best($http_accept_language);
        }
        utils\Locale::setCurrentLocale($locale);

        if ($current_user) {
            // A malicious user succeeded to logged in as the support user? He
            // should not pass.
            if ($current_user->isSupportUser()) {
                $session = auth\CurrentUser::session();

                assert($session !== null);

                models\Session::delete($session->id);
                auth\CurrentUser::reset();

                $response = \Minz\Response::redirect('login');
                $response->removeCookie('session_token');
                return $response;
            }

            // Redirect the user if she didn't validated its account after the
            // first day.
            if ($this->mustRedirectToValidation($request, $current_user)) {
                return \Minz\Response::redirect('account validation');
            }

            // Redirect the user if its subscription is overdue
            if ($this->mustRedirectToAccount($request, $current_user)) {
                return \Minz\Response::redirect('account');
            }

            // Track the last activity of the user
            $changed = $current_user->refreshLastActivity();
            if ($changed) {
                $current_user->save();
            }

            $beta_enabled = models\FeatureFlag::isEnabled('beta', $current_user->id);

            if ($current_user->autoload_modal === 'showcase navigation') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'navigation']);
            } elseif ($current_user->autoload_modal === 'showcase link') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'link']);
            } elseif ($current_user->autoload_modal === 'showcase contact') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'contact']);
            } elseif ($current_user->autoload_modal === 'showcase reading') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'reading']);
            }

            // Force CSRF token to avoid weird issues when user did nothing for a while
            \Minz\Csrf::set($current_user->csrf);
        }

        $errors = \Minz\Flash::pop('errors', []);
        $error = \Minz\Flash::pop('error');
        $status = \Minz\Flash::pop('status');

        $app_conf = \App\Configuration::$application;
        \Minz\Output\View::declareDefaultVariables([
            'csrf_token' => \Minz\Csrf::generate(),
            'errors' => $errors,
            'error' => $error,
            'status' => $status,
            'available_locales' => utils\Locale::availableLocales(),
            'current_user' => $current_user,
            'beta_enabled' => $beta_enabled,
            'autoload_modal_url' => $autoload_modal_url,
            'now' => \Minz\Time::now(),
            'javascript_configuration' => json_encode(include('utils/javascript_configuration.php')),
            'modal_requested' => $request->header('HTTP_TURBO_FRAME') === 'modal-content',
        ]);

        $response = \Minz\Engine::run($request);

        if ($response instanceof \Minz\Response) {
            $response->setHeader('Permissions-Policy', 'interest-cohort=()'); // @see https://cleanuptheweb.org/
            $response->setHeader('Referrer-Policy', 'same-origin');
            $response->setHeader('X-Content-Type-Options', 'nosniff');
            $response->setHeader('X-Frame-Options', 'deny');
            $response->addContentSecurityPolicy('style-src', "'self' 'unsafe-inline'");

            if ($app_conf['plausible_url']) {
                /** @var string */
                $plausible_url = $app_conf['plausible_url'];
                $response->addContentSecurityPolicy('connect-src', "'self' {$plausible_url}");
                $response->addContentSecurityPolicy('script-src', "'self' {$plausible_url}");
            }
        }

        if (
            $current_user &&
            $current_user->autoload_modal !== '' &&
            $response instanceof \Minz\Response &&
            $response->code() === 200
        ) {
            $current_user->autoload_modal = '';
            $current_user->save();
        }

        return $response;
    }

    /**
     * Return true if the user must validate its account (i.e. when not
     * validated after its first day).
     */
    public function mustRedirectToValidation(Request $request, models\User $user): bool
    {
        if (!$user->mustValidateEmail()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            str_starts_with($path, '/my/') ||
            str_starts_with($path, '/exportation') ||
            str_starts_with($path, '/logout') ||
            str_starts_with($path, '/terms') ||
            str_starts_with($path, '/about') ||
            str_starts_with($path, '/addons') ||
            str_starts_with($path, '/onboarding') ||
            str_starts_with($path, '/support') ||
            str_starts_with($path, '/src/assets')
        );
        return !$path_is_authorized;
    }

    /**
     * Return true if the user must renew its subscription
     */
    public function mustRedirectToAccount(Request $request, models\User $user): bool
    {
        $app_conf = \App\Configuration::$application;
        if (!$app_conf['subscriptions_enabled']) {
            return false;
        }

        if (!$user->isSubscriptionOverdue()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            str_starts_with($path, '/my/') ||
            str_starts_with($path, '/exportation') ||
            str_starts_with($path, '/logout') ||
            str_starts_with($path, '/terms') ||
            str_starts_with($path, '/about') ||
            str_starts_with($path, '/addons') ||
            str_starts_with($path, '/onboarding') ||
            str_starts_with($path, '/support') ||
            str_starts_with($path, '/src/assets')
        );
        return !$path_is_authorized;
    }
}
