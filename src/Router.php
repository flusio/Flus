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

        $router->addRoute('GET', '/', 'Pages#home', 'home');
        $router->addRoute('GET', '/terms', 'Pages#terms', 'terms');
        $router->addRoute('GET', '/about', 'Pages#about', 'about');
        $router->addRoute('GET', '/about/new', 'Feeds#whatIsNew', 'what is new');
        $router->addRoute('GET', '/addons', 'Pages#addons', 'addons');
        $router->addRoute('GET', '/robots.txt', 'Pages#robots', 'robots.txt');
        $router->addRoute('GET', '/app.webmanifest', 'Pages#webmanifest', 'webmanifest');
        $router->addRoute('GET', '/.well-known/change-password', 'WellKnown#changePassword');

        $router->addRoute('GET', '/support', 'Support#show', 'support');
        $router->addRoute('POST', '/support', 'Support#create', 'call support');

        $router->addRoute('GET', '/showcases/:id', 'Showcases#show', 'showcase');

        // Registration
        $router->addRoute('GET', '/registration', 'Registrations#new', 'registration');
        $router->addRoute('POST', '/registration', 'Registrations#create', 'create user');

        // Sessions
        $router->addRoute('GET', '/login', 'Sessions#new', 'login');
        $router->addRoute('POST', '/login', 'Sessions#create', 'create session');
        $router->addRoute('POST', '/logout', 'Sessions#delete', 'logout');
        $router->addRoute('POST', '/sessions/locale', 'Sessions#changeLocale', 'change locale');

        // Reset password
        $router->addRoute('GET', '/password/forgot', 'Passwords#forgot', 'forgot password');
        $router->addRoute('POST', '/password/forgot', 'Passwords#reset', 'reset password');
        $router->addRoute('GET', '/password/edit', 'Passwords#edit', 'edit password');
        $router->addRoute('POST', '/password/edit', 'Passwords#update', 'update password');

        // Onboarding
        $router->addRoute('GET', '/onboarding', 'Onboarding#show', 'onboarding');
        $router->addRoute('POST', '/onboarding/locale', 'Onboarding#updateLocale', 'onboarding update locale');

        // Profile
        $router->addRoute('GET', '/p/:id', 'Profiles#show', 'profile');
        $router->addRoute('GET', '/p/:id/feed.atom.xml', 'profiles/Feeds#show', 'profile feed');
        $router->addRoute('GET', '/p/:id/feed', 'profiles/Feeds#alias');
        $router->addRoute('GET', '/p/:id/opml.xml', 'profiles/Opml#show', 'profile opml');
        $router->addRoute('GET', '/p/:id/opml', 'profiles/Opml#alias');
        $router->addRoute('GET', '/my/profile', 'my/Profile#edit', 'edit profile');
        $router->addRoute('POST', '/my/profile', 'my/Profile#update', 'update profile');
        $router->addRoute('POST', '/my/profile/avatar', 'my/Avatar#update', 'update avatar');

        // "My" section
        $router->addRoute('GET', '/my/info.json', 'my/Info#show', 'profile info');

        $router->addRoute('GET', '/my/security', 'my/Security#show', 'security');
        $router->addRoute('POST', '/my/security', 'my/Security#update', 'update security');
        $router->addRoute('POST', '/my/security/confirm', 'my/Security#confirmPassword', 'confirm password');

        $router->addRoute('GET', '/my/account', 'my/Account#show', 'account');
        $router->addRoute('GET', '/my/account/validation', 'my/Validation#show', 'account validation');
        $router->addRoute(
            'POST',
            '/my/account/validation/email',
            'my/Validation#resendEmail',
            'resend validation email'
        );
        $router->addRoute('GET', '/my/account/deletion', 'my/Account#deletion', 'account deletion');
        $router->addRoute('POST', '/my/account/deletion', 'my/Account#delete', 'delete account');

        $router->addRoute('GET', '/my/account/subscription', 'my/Subscription#redirect', 'subscription');
        $router->addRoute('POST', '/my/account/subscription', 'my/Subscription#create', 'create subscription account');

        $router->addRoute('GET', '/my/preferences', 'my/Preferences#edit', 'preferences');
        $router->addRoute('POST', '/my/preferences', 'my/Preferences#update', 'update preferences');

        // Importations
        $router->addRoute('POST', '/importations/:id/delete', 'Importations#delete', 'delete importation');

        $router->addRoute('GET', '/pocket', 'importations/Pocket#show', 'pocket');
        $router->addRoute('POST', '/pocket', 'importations/Pocket#import', 'import pocket');
        $router->addRoute('POST', '/pocket/request', 'importations/Pocket#requestAccess', 'request pocket access');
        $router->addRoute('GET', '/pocket/auth', 'importations/Pocket#authorization', 'pocket auth');
        $router->addRoute('POST', '/pocket/auth', 'importations/Pocket#authorize', 'authorize pocket');

        $router->addRoute('GET', '/opml', 'importations/Opml#show', 'opml');
        $router->addRoute('POST', '/opml', 'importations/Opml#import', 'import opml');

        // Exportations
        $router->addRoute('GET', '/exportations', 'Exportations#show', 'exportation');
        $router->addRoute('POST', '/exportations', 'Exportations#create', 'create exportation');
        $router->addRoute('GET', '/exportations/download', 'Exportations#download', 'download exportation');

        // News page
        $router->addRoute('GET', '/news', 'News#index', 'news');
        $router->addRoute('POST', '/news', 'News#create', 'fill news');

        // Collections
        $router->addRoute('GET', '/collections', 'Collections#index', 'collections');
        $router->addRoute('GET', '/collections/new', 'Collections#new', 'new collection');
        $router->addRoute('POST', '/collections/new', 'Collections#create', 'create collection');
        $router->addRoute('GET', '/collections/discover', 'collections/Discovery#show', 'discover collections');
        $router->addRoute('GET', '/collections/:id', 'Collections#show', 'collection');
        $router->addRoute('GET', '/collections/:id/links/new', 'collections/Links#new', 'new collection link');
        $router->addRoute('POST', '/collections/:id/links/new', 'collections/Links#create', 'create collection link');
        $router->addRoute('GET', '/collections/:id/feed.atom.xml', 'collections/Feeds#show', 'collection feed');
        $router->addRoute('GET', '/collections/:id/feed', 'collections/Feeds#alias');
        $router->addRoute('GET', '/collections/:id/edit', 'Collections#edit', 'edit collection');
        $router->addRoute('POST', '/collections/:id/edit', 'Collections#update', 'update collection');
        $router->addRoute('POST', '/collections/:id/delete', 'Collections#delete', 'delete collection');
        $router->addRoute('POST', '/collections/:id/follow', 'collections/Followers#create', 'follow collection');
        $router->addRoute('POST', '/collections/:id/unfollow', 'collections/Followers#delete', 'unfollow collection');
        $router->addRoute('GET', '/collections/:id/filter', 'collections/Filters#edit', 'edit collection filter');
        $router->addRoute('POST', '/collections/:id/filter', 'collections/Filters#update', 'update collection filter');
        $router->addRoute('GET', '/collections/:id/group', 'collections/Groups#edit', 'edit group collection');
        $router->addRoute('POST', '/collections/:id/group', 'collections/Groups#update', 'update group collection');
        $router->addRoute('GET', '/collections/:id/image', 'collections/Images#edit', 'edit image collection');
        $router->addRoute('POST', '/collections/:id/image', 'collections/Images#update', 'update image collection');
        $router->addRoute('POST', '/collections/:id/read', 'collections/Read#create', 'mark collection as read');
        $router->addRoute('POST', '/collections/:id/read/later', 'collections/Read#later', 'read collection later');
        $router->addRoute('POST', '/collections/:id/read/never', 'collections/Read#never', 'never read collection');
        $router->addRoute('GET', '/collections/:id/share', 'collections/Shares#index', 'collection shares');
        $router->addRoute('POST', '/collections/:id/share', 'collections/Shares#create', 'share collection');
        $router->addRoute(
            'POST',
            '/collections/shares/:id/delete',
            'collections/Shares#delete',
            'delete collection share'
        );

        $router->addRoute('GET', '/bookmarks', 'Bookmarks#index', 'bookmarks');
        $router->addRoute('GET', '/read', 'Read#index', 'read list');

        // Links
        $router->addRoute('GET', '/links', 'Links#index', 'links');
        $router->addRoute('GET', '/links/new', 'Links#new', 'new link');
        $router->addRoute('POST', '/links/new', 'Links#create', 'create link');
        $router->addRoute('GET', '/links/search', 'links/Searches#show', 'show search link');
        $router->addRoute('POST', '/links/search', 'links/Searches#create', 'search link');
        $router->addRoute('GET', '/links/:id', 'Links#show', 'link');
        $router->addRoute('GET', '/links/:id/feed.atom.xml', 'links/Feeds#show', 'link feed');
        $router->addRoute('GET', '/links/:id/feed', 'links/Feeds#alias');
        $router->addRoute('GET', '/links/:id/edit', 'Links#edit', 'edit link');
        $router->addRoute('POST', '/links/:id/edit', 'Links#update', 'update link');
        $router->addRoute('GET', '/links/:id/repair', 'links/Repairing#new', 'repairing link');
        $router->addRoute('POST', '/links/:id/repair', 'links/Repairing#create', 'repair link');
        $router->addRoute('POST', '/links/:id/delete', 'Links#delete', 'delete link');
        $router->addRoute('POST', '/links/:id/read', 'links/Read#create', 'mark link as read');
        $router->addRoute('POST', '/links/:id/read/later', 'links/Read#later', 'read link later');
        $router->addRoute('POST', '/links/:id/read/never', 'links/Read#never', 'mark link to never read');
        $router->addRoute('POST', '/links/:id/read/delete', 'links/Read#delete', 'mark link as unread');

        // Link collections
        $router->addRoute('GET', '/links/:id/collections', 'links/Collections#index', 'link collections');
        $router->addRoute('POST', '/links/:id/collections', 'links/Collections#update', 'update link collections');

        // Messages
        $router->addRoute('GET', '/links/:link_id/messages', 'links/Messages#index', 'links/messages');
        $router->addRoute('POST', '/links/:link_id/messages', 'links/Messages#create', 'links/create message');
        $router->addRoute('GET', '/messages/:id/edit', 'Messages#edit', 'edit message');
        $router->addRoute('POST', '/messages/:id/edit', 'Messages#update', 'update message');
        $router->addRoute('POST', '/messages/:id/delete', 'Messages#delete', 'delete message');

        // Groups
        $router->addRoute('GET', '/groups/:id/edit', 'Groups#edit', 'edit group');
        $router->addRoute('POST', '/groups/:id/edit', 'Groups#update', 'update group');
        $router->addRoute('POST', '/groups/:id/delete', 'Groups#delete', 'delete group');

        // Feeds
        $router->addRoute('GET', '/feeds', 'Feeds#index', 'feeds');
        $router->addRoute('GET', '/feeds/new', 'Feeds#new', 'new feed');
        $router->addRoute('POST', '/feeds/new', 'Feeds#create', 'create feed');

        $router->addRoute('GET', '/feeds.xsl', 'Feeds#xsl', 'feeds xsl');

        // Discovery
        $router->addRoute('GET', '/discovery', 'Discovery#show', 'discovery');

        $router->addRoute('GET', '/topics/:id', 'Topics#show', 'topic');

        // This should be used only for source mapping
        $router->addRoute('GET', '/src/assets/*', 'Assets#show');

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

        $router->addRoute('CLI', '/', 'Help#show');
        $router->addRoute('CLI', '/help', 'Help#show');

        $router->addRoute('CLI', '/system', 'System#show');
        $router->addRoute('CLI', '/system/secret', 'System#secret');
        $router->addRoute('CLI', '/database/status', 'Database#status');

        $router->addRoute('CLI', '/migrations', 'Migrations#index');
        $router->addRoute('CLI', '/migrations/setup', 'Migrations#setup');
        $router->addRoute('CLI', '/migrations/rollback', 'Migrations#rollback');
        $router->addRoute('CLI', '/migrations/create', 'Migrations#create');
        $router->addRoute('CLI', '/migrations/reset', 'Migrations#reset');

        $router->addRoute('CLI', '/media/clean', 'Media#clean');

        $router->addRoute('CLI', '/urls/show', 'Urls#show');
        $router->addRoute('CLI', '/urls/uncache', 'Urls#uncache');

        $router->addRoute('CLI', '/users', 'Users#index');
        $router->addRoute('CLI', '/users/create', 'Users#create');
        $router->addRoute('CLI', '/users/export', 'Users#export');
        $router->addRoute('CLI', '/users/validate', 'Users#validate');

        $router->addRoute('CLI', '/features', 'FeatureFlags#index');
        $router->addRoute('CLI', '/features/flags', 'FeatureFlags#flags');
        $router->addRoute('CLI', '/features/enable', 'FeatureFlags#enable');
        $router->addRoute('CLI', '/features/disable', 'FeatureFlags#disable');

        $router->addRoute('CLI', '/feeds', 'Feeds#index');
        $router->addRoute('CLI', '/feeds/add', 'Feeds#add');
        $router->addRoute('CLI', '/feeds/sync', 'Feeds#sync');
        $router->addRoute('CLI', '/feeds/reset-hashes', 'Feeds#resetHashes');

        $router->addRoute('CLI', '/topics', 'Topics#index');
        $router->addRoute('CLI', '/topics/create', 'Topics#create');
        $router->addRoute('CLI', '/topics/update', 'Topics#update');
        $router->addRoute('CLI', '/topics/delete', 'Topics#delete');

        $router->addRoute('CLI', '/jobs', 'Jobs#index');
        $router->addRoute('CLI', '/jobs/install', 'Jobs#install');
        $router->addRoute('CLI', '/jobs/watch', 'Jobs#watch');
        $router->addRoute('CLI', '/jobs/run', 'Jobs#run');
        $router->addRoute('CLI', '/jobs/show', 'Jobs#show');
        $router->addRoute('CLI', '/jobs/unfail', 'Jobs#unfail');
        $router->addRoute('CLI', '/jobs/unlock', 'Jobs#unlock');

        return $router;
    }
}
