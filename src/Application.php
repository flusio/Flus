<?php

namespace flusio;

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
 * $application = new \flusio\Application();
 * $response = $application->run($request);
 * echo $response->render();
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    /**
     * Setup a Router and declare its routes.
     */
    public function __construct()
    {
        // This provides utility functions to be used in the Views
        include_once('utils/view_helpers.php');

        $router = Router::load();
        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);

        \Minz\Output\View::$extensions_to_content_types['.atom.xml.php'] = 'application/xml';
        \Minz\Output\View::$extensions_to_content_types['.opml.xml.php'] = 'text/x-opml';
        \Minz\Output\View::$extensions_to_content_types['.turbo_stream.phtml'] = 'text/vnd.turbo-stream.html';
        \Minz\Output\View::$extensions_to_content_types['.xsl.php'] = 'application/xslt+xml';
    }

    /**
     * Declare global View variables and execute a request.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function run($request)
    {
        if (!auth\CurrentUser::sessionToken()) {
            auth\CurrentUser::setSessionToken($request->cookie('flusio_session_token'));
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
            $http_accept_language = $request->header('HTTP_ACCEPT_LANGUAGE');
            $locale = utils\Locale::best($http_accept_language);
        }
        utils\Locale::setCurrentLocale($locale);

        if ($current_user) {
            // A malicious user succeeded to logged in as the support user? He
            // should not pass.
            if ($current_user->isSupportUser()) {
                $current_session_token = auth\CurrentUser::sessionToken();
                $session = models\Session::findBy(['token' => $current_session_token]);
                models\Session::delete($session->id);
                auth\CurrentUser::reset();

                $response = \Minz\Response::redirect('login');
                $response->removeCookie('flusio_session_token');
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

            $beta_enabled = models\FeatureFlag::isEnabled('beta', $current_user->id);

            if ($current_user->autoload_modal === 'showcase navigation') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'navigation']);
            } elseif ($current_user->autoload_modal === 'showcase link') {
                $autoload_modal_url = \Minz\Url::for('showcase', ['id' => 'link']);
            }

            // Force CSRF token to avoid weird issues when user did nothing for a while
            \Minz\CSRF::set($current_user->csrf);
        }

        $errors = utils\Flash::pop('errors', []);
        $error = utils\Flash::pop('error');
        $status = utils\Flash::pop('status');

        $response = $this->engine->run($request, [
            'not_found_view_pointer' => 'not_found.phtml',
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
            'controller_namespace' => '\\flusio\\controllers',
        ]);

        $app_conf = \Minz\Configuration::$application;
        \Minz\Output\View::declareDefaultVariables([
            'environment' => \Minz\Configuration::$environment,
            'brand' => $app_conf['brand'],
            'csrf_token' => \Minz\CSRF::generate(),
            'errors' => $errors,
            'error' => $error,
            'status' => $status,
            'available_locales' => utils\Locale::availableLocales(),
            'current_locale' => $locale,
            'current_user' => $current_user,
            'beta_enabled' => $beta_enabled,
            'autoload_modal_url' => $autoload_modal_url,
            'now' => \Minz\Time::now(),
            'javascript_configuration' => json_encode(include('utils/javascript_configuration.php')),
            'turbo_frame' => $request->header('HTTP_TURBO_FRAME'),
            'demo' => $app_conf['demo'],
            'registrations_opened' => $app_conf['registrations_opened'],
        ]);

        $response->setContentSecurityPolicy('style-src', "'self' 'unsafe-inline'");
        $response->setHeader('Permissions-Policy', 'interest-cohort=()'); // @see https://cleanuptheweb.org/
        $response->setHeader('Referrer-Policy', 'same-origin');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'deny');

        if (
            $current_user &&
            $current_user->autoload_modal !== '' &&
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
     *
     * @param \Minz\Request $request
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function mustRedirectToValidation($request, $user)
    {
        if (!$user->mustValidateEmail()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            utils\Belt::startsWith($path, '/my/') ||
            utils\Belt::startsWith($path, '/exportation') ||
            utils\Belt::startsWith($path, '/logout') ||
            utils\Belt::startsWith($path, '/terms') ||
            utils\Belt::startsWith($path, '/about') ||
            utils\Belt::startsWith($path, '/addons') ||
            utils\Belt::startsWith($path, '/onboarding') ||
            utils\Belt::startsWith($path, '/support') ||
            utils\Belt::startsWith($path, '/src/assets')
        );
        return !$path_is_authorized;
    }

    /**
     * Return true if the user must renew its subscription
     *
     * @param \Minz\Request $request
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function mustRedirectToAccount($request, $user)
    {
        $app_conf = \Minz\Configuration::$application;
        if (!$app_conf['subscriptions_enabled']) {
            return false;
        }

        if (!$user->isSubscriptionOverdue()) {
            return false;
        }

        $path = $request->path();
        $path_is_authorized = (
            utils\Belt::startsWith($path, '/my/') ||
            utils\Belt::startsWith($path, '/exportation') ||
            utils\Belt::startsWith($path, '/logout') ||
            utils\Belt::startsWith($path, '/terms') ||
            utils\Belt::startsWith($path, '/about') ||
            utils\Belt::startsWith($path, '/addons') ||
            utils\Belt::startsWith($path, '/onboarding') ||
            utils\Belt::startsWith($path, '/support') ||
            utils\Belt::startsWith($path, '/src/assets')
        );
        return !$path_is_authorized;
    }
}
