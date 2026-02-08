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
            'not_found_template' => 'errors/not_found.html.twig',
            'internal_server_error_template' => 'errors/internal_server_error.html.twig',
            'controller_namespace' => '\\App\\controllers',
        ]);

        // Automatically declare content types for these views files extensions
        \Minz\Output\Template::$extensions_to_content_types['.atom.xml'] = 'application/xml';
        \Minz\Output\Template::$extensions_to_content_types['.opml.xml'] = 'text/x-opml';
        \Minz\Output\Template::$extensions_to_content_types['.xsl'] = 'application/xslt+xml';

        // Register Twig extensions
        \Minz\Template\Twig::addAttributeExtension(twig\AuthExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\ConfigurationExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\FormsExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\FormattersExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\IconExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\LocaleExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\NavigationExtension::class);
        \Minz\Template\Twig::addAttributeExtension(twig\UrlExtension::class);
    }

    /**
     * Declare global template variables and execute a request.
     *
     * @return ResponseReturnable
     */
    public function run(\Minz\Request $request): mixed
    {
        utils\RequestHelper::setPreviousUrl($request);

        $session_token = $request->cookies->getString('session_token');
        if ($session_token) {
            auth\CurrentUser::authenticate($session_token, scope: 'browser');
        }

        $current_user = auth\CurrentUser::get();
        $locale = utils\Locale::DEFAULT_LOCALE;

        // Setup current localization
        if ($current_user) {
            $locale = $current_user->locale;
        } elseif (isset($_SESSION['locale']) && is_string($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
        } else {
            $http_accept_language = $request->headers->getString('Accept-Language', '');
            $locale = utils\Locale::best($http_accept_language);
        }

        utils\Locale::setCurrentLocale($locale);

        if ($current_user) {
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
        }

        \Minz\Template\Twig::addGlobals([
            'app' => [
                'brand' => \App\Configuration::$application['brand'],
                'version' => \App\Configuration::$application['version'],
                'user' => $current_user,
                'locale' => utils\Locale::currentLocale(),
                'user_agent' => utils\UserAgent::get(),
                'demo' => \App\Configuration::$application['demo'],
            ],
        ]);

        $response = \Minz\Engine::run($request);

        if ($response instanceof \Minz\Response) {
            $response->setHeader('Permissions-Policy', 'interest-cohort=()'); // @see https://cleanuptheweb.org/
            $response->setHeader('Referrer-Policy', 'same-origin');
            $response->setHeader('X-Content-Type-Options', 'nosniff');
            $response->setHeader('X-Frame-Options', 'deny');
            $response->addContentSecurityPolicy('style-src', "'self' 'unsafe-inline'");

            $plausible_url = \App\Configuration::$application['plausible_url'] ?? '';
            if ($plausible_url) {
                $response->addContentSecurityPolicy('connect-src', "'self' {$plausible_url}");
                $response->addContentSecurityPolicy('script-src', "'self' {$plausible_url}");
            }
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
