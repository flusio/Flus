<?php

namespace flusio;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Routes
{
    /**
     * Load the routes into the router (doesn't contain CLI routes)
     *
     * @param \Minz\Router $router
     */
    public static function load($router)
    {
        $router->addRoute('get', '/', 'Pages#home', 'home');
        $router->addRoute('get', '/terms', 'Pages#terms', 'terms');
        $router->addRoute('get', '/app.webmanifest', 'Pages#webmanifest', 'webmanifest');
        $router->addRoute('get', '/.well-known/change-password', 'WellKnown#changePassword');

        // Registration
        $router->addRoute('get', '/registration', 'Registrations#new', 'registration');
        $router->addRoute('post', '/registration', 'Registrations#create', 'create user');

        // Sessions
        $router->addRoute('get', '/login', 'Sessions#new', 'login');
        $router->addRoute('post', '/login', 'Sessions#create', 'create session');
        $router->addRoute('post', '/logout', 'Sessions#delete', 'logout');
        $router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');

        // Onboarding
        $router->addRoute('get', '/onboarding', 'Onboarding#show', 'onboarding');
        $router->addRoute('post', '/onboarding/locale', 'Onboarding#updateLocale', 'onboarding update locale');
        $router->addRoute('post', '/onboarding/topics', 'Onboarding#updateTopics', 'onboarding update topics');

        // "My" section
        $router->addRoute('get', '/my/profile', 'my/Profile#show', 'profile');
        $router->addRoute('post', '/my/profile', 'my/Profile#update', 'update profile');
        $router->addRoute('post', '/my/profile/avatar', 'my/Avatar#update', 'update avatar');

        $router->addRoute('get', '/my/info.json', 'my/Info#show', 'profile info');

        $router->addRoute('get', '/my/security', 'my/Security#show', 'security');
        $router->addRoute('post', '/my/security', 'my/Security#update', 'update security');
        $router->addRoute('post', '/my/security/confirm', 'my/Security#confirmPassword', 'confirm password');

        $router->addRoute('get', '/my/account', 'my/Account#show', 'account');
        $router->addRoute('get', '/my/account/validation', 'my/Validation#show', 'account validation');
        $router->addRoute(
            'post',
            '/my/account/validation/email',
            'my/Validation#resendEmail',
            'resend validation email'
        );
        $router->addRoute('get', '/my/account/deletion', 'my/Account#deletion', 'account deletion');
        $router->addRoute('post', '/my/account/deletion', 'my/Account#delete', 'delete account');

        $router->addRoute('get', '/my/account/subscription', 'my/Subscription#redirect', 'subscription');
        $router->addRoute('post', '/my/account/subscription', 'my/Subscription#create', 'create subscription account');

        // Importations
        $router->addRoute('post', '/importations/:id/delete', 'importations/Importations#delete', 'delete importation');

        $router->addRoute('get', '/pocket', 'importations/Pocket#show', 'pocket');
        $router->addRoute('post', '/pocket', 'importations/Pocket#import', 'import pocket');
        $router->addRoute('post', '/pocket/request', 'importations/Pocket#requestAccess', 'request pocket access');
        $router->addRoute('get', '/pocket/auth', 'importations/Pocket#authorization', 'pocket auth');
        $router->addRoute('post', '/pocket/auth', 'importations/Pocket#authorize', 'authorize pocket');

        $router->addRoute('get', '/opml', 'importations/Opml#show', 'opml');
        $router->addRoute('post', '/opml', 'importations/Opml#import', 'import opml');

        // News page
        $router->addRoute('get', '/news', 'News#show', 'news');
        $router->addRoute('post', '/news', 'News#create', 'fill news');
        $router->addRoute('get', '/news/preferences', 'news/Preferences#show', 'news preferences');
        $router->addRoute('post', '/news/preferences', 'news/Preferences#update', 'update news preferences');
        $router->addRoute('get', '/news/:id/add', 'news/Links#new', 'adding news');
        $router->addRoute('post', '/news/:id/add', 'news/Links#create', 'add news');
        $router->addRoute('post', '/news/:id/mark-as-read', 'news/Links#markAsRead', 'mark news as read');
        $router->addRoute('post', '/news/:id/read-later', 'news/Links#readLater', 'read news later');
        $router->addRoute('post', '/news/:id/remove', 'news/Links#delete', 'remove news');

        // Collections
        $router->addRoute('get', '/collections', 'Collections#index', 'collections');
        $router->addRoute('get', '/collections/new', 'Collections#new', 'new collection');
        $router->addRoute('post', '/collections/new', 'Collections#create', 'create collection');
        $router->addRoute('get', '/collections/discover', 'collections/Discovery#show', 'discover collections');
        $router->addRoute('get', '/collections/:id', 'Collections#show', 'collection');
        $router->addRoute('get', '/collections/:id/feed.atom.xml', 'Collections#show', 'collection feed');
        $router->addRoute('get', '/collections/:id/edit', 'Collections#edit', 'edit collection');
        $router->addRoute('post', '/collections/:id/edit', 'Collections#update', 'update collection');
        $router->addRoute('post', '/collections/:id/delete', 'Collections#delete', 'delete collection');
        $router->addRoute('post', '/collections/:id/follow', 'collections/Followers#create', 'follow collection');
        $router->addRoute('post', '/collections/:id/unfollow', 'collections/Followers#delete', 'unfollow collection');

        $router->addRoute('get', '/bookmarks', 'collections/Bookmarks#show', 'bookmarks');

        // Links
        $router->addRoute('get', '/links/new', 'Links#new', 'new link');
        $router->addRoute('post', '/links/new', 'Links#create', 'create link');
        $router->addRoute('get', '/links/search', 'links/Searches#show', 'show search link');
        $router->addRoute('post', '/links/search', 'links/Searches#create', 'search link');
        $router->addRoute('get', '/links/:id', 'Links#show', 'link');
        $router->addRoute('get', '/links/:id/feed.atom.xml', 'Links#show', 'link feed');
        $router->addRoute('get', '/links/:id/edit', 'Links#edit', 'edit link');
        $router->addRoute('post', '/links/:id/edit', 'Links#update', 'update link');
        $router->addRoute('post', '/links/:id/delete', 'Links#delete', 'delete link');
        $router->addRoute('get', '/links/:id/fetch', 'links/Fetches#show', 'show fetch link');
        $router->addRoute('post', '/links/:id/fetch', 'links/Fetches#create', 'fetch link');
        $router->addRoute('get', '/links/:id/obtain', 'links/Obtentions#new', 'obtaining link');
        $router->addRoute('post', '/links/:id/obtain', 'links/Obtentions#create', 'obtain link');
        $router->addRoute('post', '/links/:id/mark-as-read', 'Links#markAsRead', 'mark link as read');

        // Link collections
        $router->addRoute('get', '/links/:id/collections', 'links/Collections#index', 'link collections');
        $router->addRoute('post', '/links/:id/collections', 'links/Collections#update', 'update link collections');

        // Messages
        $router->addRoute('get', '/links/:link_id/messages', 'links/Messages#index', 'links/messages');
        $router->addRoute('post', '/links/:link_id/messages', 'links/Messages#create', 'links/create message');
        $router->addRoute('post', '/messages/:id/delete', 'Messages#delete', 'delete message');

        // This should be used only for source mapping
        $router->addRoute('get', '/src/assets/*', 'Assets#show');

        $router->addRoute('get', '/design', 'Pages#design', 'design');
    }

    /**
     * Load the CLI routes into the router (contain app routes)
     *
     * @param \Minz\Router $router
     */
    public static function loadCli($router)
    {
        $router->addRoute('cli', '/', 'System#usage');

        $router->addRoute('cli', '/system/secret', 'System#secret');
        $router->addRoute('cli', '/system/setup', 'System#setup');
        $router->addRoute('cli', '/system/rollback', 'System#rollback');
        $router->addRoute('cli', '/database/status', 'Database#status');

        $router->addRoute('cli', '/users', 'Users#index');
        $router->addRoute('cli', '/users/create', 'Users#create');
        $router->addRoute('cli', '/users/clean', 'Users#clean');

        $router->addRoute('cli', '/features', 'FeatureFlags#index');
        $router->addRoute('cli', '/features/flags', 'FeatureFlags#flags');
        $router->addRoute('cli', '/features/enable', 'FeatureFlags#enable');
        $router->addRoute('cli', '/features/disable', 'FeatureFlags#disable');

        $router->addRoute('cli', '/feeds', 'Feeds#index');
        $router->addRoute('cli', '/feeds/add', 'Feeds#add');
        $router->addRoute('cli', '/feeds/sync', 'Feeds#sync');

        $router->addRoute('cli', '/topics', 'Topics#index');
        $router->addRoute('cli', '/topics/create', 'Topics#create');
        $router->addRoute('cli', '/topics/delete', 'Topics#delete');

        $router->addRoute('cli', '/links/refresh', 'Links#refresh');

        $router->addRoute('cli', '/jobs', 'JobsWorker#index');
        $router->addRoute('cli', '/jobs/run', 'JobsWorker#run');
        $router->addRoute('cli', '/jobs/watch', 'JobsWorker#watch');
        $router->addRoute('cli', '/jobs/clear', 'JobsWorker#clear');

        self::load($router);
    }
}
