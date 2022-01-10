<?php

namespace flusio;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Router
{
    /**
     * Return the application router (doesn't contain CLI routes)
     *
     * @return \Minz\Router
     */
    public static function load()
    {
        $router = new \Minz\Router();

        $router->addRoute('get', '/', 'Pages#home', 'home');
        $router->addRoute('get', '/terms', 'Pages#terms', 'terms');
        $router->addRoute('get', '/app.webmanifest', 'Pages#webmanifest', 'webmanifest');
        $router->addRoute('get', '/.well-known/change-password', 'WellKnown#changePassword');

        $router->addRoute('get', '/support', 'Support#show', 'support');
        $router->addRoute('post', '/support', 'Support#create', 'call support');

        // Registration
        $router->addRoute('get', '/registration', 'Registrations#new', 'registration');
        $router->addRoute('post', '/registration', 'Registrations#create', 'create user');

        // Sessions
        $router->addRoute('get', '/login', 'Sessions#new', 'login');
        $router->addRoute('post', '/login', 'Sessions#create', 'create session');
        $router->addRoute('post', '/logout', 'Sessions#delete', 'logout');
        $router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');

        // Reset password
        $router->addRoute('get', '/password/forgot', 'Passwords#forgot', 'forgot password');
        $router->addRoute('post', '/password/forgot', 'Passwords#reset', 'reset password');
        $router->addRoute('get', '/password/edit', 'Passwords#edit', 'edit password');
        $router->addRoute('post', '/password/edit', 'Passwords#update', 'update password');

        // Onboarding
        $router->addRoute('get', '/onboarding', 'Onboarding#show', 'onboarding');
        $router->addRoute('post', '/onboarding/locale', 'Onboarding#updateLocale', 'onboarding update locale');

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
        $router->addRoute('post', '/importations/:id/delete', 'Importations#delete', 'delete importation');

        $router->addRoute('get', '/pocket', 'importations/Pocket#show', 'pocket');
        $router->addRoute('post', '/pocket', 'importations/Pocket#import', 'import pocket');
        $router->addRoute('post', '/pocket/request', 'importations/Pocket#requestAccess', 'request pocket access');
        $router->addRoute('get', '/pocket/auth', 'importations/Pocket#authorization', 'pocket auth');
        $router->addRoute('post', '/pocket/auth', 'importations/Pocket#authorize', 'authorize pocket');

        $router->addRoute('get', '/opml', 'importations/Opml#show', 'opml');
        $router->addRoute('post', '/opml', 'importations/Opml#import', 'import opml');

        // Exportations
        $router->addRoute('get', '/exportations', 'Exportations#show', 'exportation');
        $router->addRoute('post', '/exportations', 'Exportations#create', 'create exportation');
        $router->addRoute('get', '/exportations/download', 'Exportations#download', 'download exportation');

        // News page
        $router->addRoute('get', '/news', 'News#show', 'news');
        $router->addRoute('post', '/news', 'News#create', 'fill news');
        $router->addRoute('post', '/news/read', 'news/Read#create', 'mark news as read');
        $router->addRoute('post', '/news/read/later', 'news/Read#later', 'read news later');
        $router->addRoute('post', '/news/read/never', 'news/Read#never', 'mark news to never read');

        // Bookmarks
        $router->addRoute('get', '/bookmarks', 'Bookmarks#index', 'bookmarks');

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
        $router->addRoute('get', '/collections/:id/follow', 'collections/Followers#show', 'login to follow collection');
        $router->addRoute('post', '/collections/:id/follow', 'collections/Followers#create', 'follow collection');
        $router->addRoute('post', '/collections/:id/unfollow', 'collections/Followers#delete', 'unfollow collection');
        $router->addRoute('get', '/collections/:id/filter', 'collections/Filters#edit', 'edit collection filter');
        $router->addRoute('post', '/collections/:id/filter', 'collections/Filters#update', 'update collection filter');
        $router->addRoute('get', '/collections/:id/group', 'collections/Groups#edit', 'edit group collection');
        $router->addRoute('post', '/collections/:id/group', 'collections/Groups#update', 'update group collection');
        $router->addRoute('get', '/collections/:id/image', 'collections/Images#edit', 'edit image collection');
        $router->addRoute('post', '/collections/:id/image', 'collections/Images#update', 'update image collection');

        $router->addRoute('get', '/read', 'collections/Read#index', 'read list');

        // Links
        $router->addRoute('get', '/links', 'Links#index', 'links');
        $router->addRoute('get', '/links/new', 'Links#new', 'new link');
        $router->addRoute('post', '/links/new', 'Links#create', 'create link');
        $router->addRoute('get', '/links/search', 'links/Searches#show', 'show search link');
        $router->addRoute('post', '/links/search', 'links/Searches#create', 'search link');
        $router->addRoute('get', '/links/:id', 'Links#show', 'link');
        $router->addRoute('get', '/links/:id/feed.atom.xml', 'Links#show', 'link feed');
        $router->addRoute('get', '/links/:id/edit', 'Links#edit', 'edit link');
        $router->addRoute('post', '/links/:id/edit', 'Links#update', 'update link');
        $router->addRoute('post', '/links/:id/delete', 'Links#delete', 'delete link');
        $router->addRoute('post', '/links/:id/fetch', 'Links#fetch', 'fetch link');
        $router->addRoute('get', '/links/:id/obtain', 'links/Obtentions#new', 'obtaining link');
        $router->addRoute('post', '/links/:id/obtain', 'links/Obtentions#create', 'obtain link');
        $router->addRoute('post', '/links/:id/read', 'links/Read#create', 'mark link as read');
        $router->addRoute('post', '/links/:id/read/later', 'links/Read#later', 'read link later');
        $router->addRoute('post', '/links/:id/read/never', 'links/Read#never', 'mark link to never read');

        // Link collections
        $router->addRoute('get', '/links/:id/collections', 'links/Collections#index', 'link collections');
        $router->addRoute('post', '/links/:id/collections', 'links/Collections#update', 'update link collections');

        // Messages
        $router->addRoute('get', '/links/:link_id/messages', 'links/Messages#index', 'links/messages');
        $router->addRoute('post', '/links/:link_id/messages', 'links/Messages#create', 'links/create message');
        $router->addRoute('post', '/messages/:id/delete', 'Messages#delete', 'delete message');

        // Groups
        $router->addRoute('get', '/groups/:id/collections', 'groups/Collections#index', 'group collections');
        $router->addRoute('get', '/groups/:id/edit', 'Groups#edit', 'edit group');
        $router->addRoute('post', '/groups/:id/edit', 'Groups#update', 'update group');
        $router->addRoute('post', '/groups/:id/delete', 'Groups#delete', 'delete group');

        // Feeds
        $router->addRoute('get', '/feeds', 'Feeds#index', 'feeds');
        $router->addRoute('get', '/feeds/new', 'Feeds#new', 'new feed');
        $router->addRoute('post', '/feeds/new', 'Feeds#create', 'create feed');

        // Discovery
        $router->addRoute('get', '/discovery', 'Discovery#show', 'discovery');

        $router->addRoute('get', '/topics/:id', 'Topics#show', 'topic');

        // This should be used only for source mapping
        $router->addRoute('get', '/src/assets/*', 'Assets#show');

        return $router;
    }

    /**
     * Return the CLI router (containing app routes)
     *
     * @return \Minz\Router
     */
    public static function loadCli()
    {
        $router = self::load();

        $router->addRoute('cli', '/', 'Help#show');
        $router->addRoute('cli', '/help', 'Help#show');

        $router->addRoute('cli', '/system', 'System#show');
        $router->addRoute('cli', '/system/secret', 'System#secret');
        $router->addRoute('cli', '/system/setup', 'System#setup');
        $router->addRoute('cli', '/database/status', 'Database#status');

        $router->addRoute('cli', '/migrations', 'Migrations#index');
        $router->addRoute('cli', '/migrations/create', 'Migrations#create');
        $router->addRoute('cli', '/migrations/apply', 'Migrations#apply');
        $router->addRoute('cli', '/migrations/rollback', 'Migrations#rollback');

        $router->addRoute('cli', '/users', 'Users#index');
        $router->addRoute('cli', '/users/create', 'Users#create');
        $router->addRoute('cli', '/users/export', 'Users#export');

        $router->addRoute('cli', '/features', 'FeatureFlags#index');
        $router->addRoute('cli', '/features/flags', 'FeatureFlags#flags');
        $router->addRoute('cli', '/features/enable', 'FeatureFlags#enable');
        $router->addRoute('cli', '/features/disable', 'FeatureFlags#disable');

        $router->addRoute('cli', '/feeds', 'Feeds#index');
        $router->addRoute('cli', '/feeds/add', 'Feeds#add');
        $router->addRoute('cli', '/feeds/sync', 'Feeds#sync');

        $router->addRoute('cli', '/topics', 'Topics#index');
        $router->addRoute('cli', '/topics/create', 'Topics#create');
        $router->addRoute('cli', '/topics/update', 'Topics#update');
        $router->addRoute('cli', '/topics/delete', 'Topics#delete');

        $router->addRoute('cli', '/jobs', 'JobsWorker#index');
        $router->addRoute('cli', '/jobs/run', 'JobsWorker#run');
        $router->addRoute('cli', '/jobs/unlock', 'JobsWorker#unlock');
        $router->addRoute('cli', '/jobs/watch', 'JobsWorker#watch');

        if (\Minz\Configuration::$application['subscriptions_enabled']) {
            $router->addRoute('cli', '/subscriptions', 'Subscriptions#index');
        }

        return $router;
    }
}
