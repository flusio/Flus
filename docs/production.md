# Deploy in production

Installing Flus on your own server is quite simple but still requires basic
notions in sysadmin. First, make sure you match with the following
requirements:

- git, Nginx, PHP 8.2+, [Composer](https://getcomposer.org/) and PostgreSQL 13+ are installed on your server;
- PHP requires `intl`, `gettext`, `pcntl`, `gd` (with at least WebP support), `pdo` and `pdo_pgsql` extensions;
- your PostgreSQL user must have the permission to create a database;
- Flus must be served over <abbr>HTTPS</abbr>.

**Other configurations might work but aren’t officialy supported.**

If you don’t know how to configure HTTPS, you should check if your server
provider doesn’t already configures one for you. If not, you can get one for
free with [Let’s Encrypt `certbot` client](https://certbot.eff.org/). This
documentation isn’t intended to teach you how to use it though.

## Download the code

First, start by getting the code with git (you might need to run the commands
as the `root` user):

```console
# cd /var/www/
# git clone https://github.com/flusio/Flus.git
# cd Flus
flus# git checkout TAG
```

You must replace the `TAG` argument with the latest version that you can find
on the [GitHub releases page](https://github.com/flusio/Flus/releases).

## Install the dependencies

Install the Composer dependencies with:

```console
flus$ composer install --no-dev --optimize-autoloader
```

## Configure the environment

You must now configure the environment by creating the `.env` file:

```console
flus# cp env.sample .env
flus# vim .env # or edit with nano or whatever editor you prefer
```

The environment file is commented so it should not be too complicated to setup
correctly.

You can check the database is correctly configured with:

```console
flus# sudu -u www-data php cli database status
Database status: OK
```

It’ll not tell you if the user has correct permissions though.

The SMTP information should be given to you by your email provider. If you
don’t have an address to send emails, just set `APP_MAILER` to `mail` and
`SMTP_FROM` with an address corresponding to your domain. The other lines can
be commented or deleted. This is not recommended though.

## Set files permissions

You should set the owner of the files to the user that runs Nginx. This is
often `www-data`:

```console
flus# chown -R www-data:www-data .
```

You should also change the permissions on the `.env` file to limit the risks of
credentials being stolen. The `www-data` user only needs `read` permission:

```console
flus# chmod 400 .env
```

## Setup the database

You must now load the SQL schema to your database. You can do it with:

```console
flus# sudo -u www-data php cli migrations setup --seed
```

If the permissions are correct, you should have a message to tell you the
system has been initialized. If an error should occur during this installation,
this is probably where it will happen!

## Configuring Nginx

First thing, **make sure to have your domain served over HTTPS by Nginx.**

Then, you must configure your Nginx server. Here’s an example:

```nginx
server {
    listen 80;
    listen [::]:80;

    # This must match the `APP_HOST` variable
    server_name localhost;

    # Redirect all HTTP requests to HTTPS with a 301 Moved Permanently response.
    # If you’re not sure of what you’re doing, you should settle for 302
    return 301 https://$host$request_uri;
}

server {
    # Configure HTTP2 to listen on HTTPS port for both IPv4 and IPv6
    # The port must match the `APP_PORT` variable
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    # This must match the `APP_HOST` variable
    server_name localhost;

    # Please note that we serve the public/ folder, it **must not** be set
    # directly to the Flus root folder!
    root /var/www/flus/public;
    index index.html index.php;

    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;

    location / {
        # This tries to serve the file under the public/ folder first, then if
        # it doesn’t exist, it redirects the request to the index.php file
        try_files $uri $uri/ /index.php$is_args$query_string;
    }

    location ~ index.php$ {
        # Please refer to the official Nginx documentation if you have any doubt
        # https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi.conf;
    }

    # Your HTTPS certificate paths provided either by your provider or certbot
    ssl_certificate /etc/letsencrypt/live/your-domain/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain/privkey.pem;
}
```

Obviously, you can adapt this file to your needs and add any optimization you
think that you might need of.

Let’s check that your configuration is valid with `nginx -t` and reload Nginx
with `systemctl reload nginx`.

If you’ve done everything right, you should now be able to access Flus at the
address you’ve configured, congratulations!

## Setup the job worker

A last step is required to install Flus on your server though. Some long
tasks (e.g. sending emails) are run in background via a job system. You’ll need
to start a worker to process them. For instance, you can create a systemd
service by creating a `/etc/systemd/system/flus-worker.service` file.

```systemd
[Unit]
Description=A job worker for Flus

[Service]
ExecStart=php /var/www/flus/cli jobs watch
User=www-data
Group=www-data
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Then, reload the systemd daemon and start the service:

```console
# systemctl daemon-reload
# systemctl enable flus-worker
# systemctl start flus-worker
```

You should obviously adapt the service files to your needs. Also, you might not
have permission on your server to create a new service. An alternative is to
setup a cron task:

```cron
* * * * * www-data php /var/www/flus/cli jobs watch --stop-after=1 >/dev/null 2>&1
```

It will find and run a single job every minute. It’s less efficient than a
service, but it should work.

## Optional: Create topics

Topics are used to categorize collections. They only can be created by the
administrator with the CLI for now:

```console
flus# sudo -u www-data php cli topics create --label=LABEL
```

You must change `LABEL` by the name of your topic (e.g. economics, politics,
health). You can pass an `image_url` param to set an illustration:

```console
flus# sudo -u www-data php cli topics create --label=LABEL --image_url=https://flus.fr/carnet/card.png
```

If you’ve made a mistake, you can update or delete a topic with:

```console
flus# sudo -u www-data php cli topics update --id=ID --label=NEW_LABEL
flus# # OR to delete
flus# sudo -u www-data php cli topics delete --id=ID
```

You must change `ID` by the id of an existing topic. You can find topic ids by
listing them:

```console
flus# sudo -u www-data php cli topics
```

## Optional: Configure Pocket

Flus allows users to import their data from Pocket. First, you have to
[create a Pocket app](https://getpocket.com/developer/apps/new). It will give
you a "consumer key". Set this key in the `APP_POCKET_CONSUMER_KEY` variable of
your `.env` file. That’s all!

## Optional: Configure Browscap

We use Browscap to identify the users’ sessions via their user agent. Flus
can work without Browscap but the sessions will be identified as `Unknown
browser on unknown platform`.

Browscap is configured at the PHP level. It can be done by downloading the
latest version of [one of the `browscap.ini` files for PHP](https://browscap.org/).
A file is already provided in this repository as [`docker/development/lite_php_browscap.ini`
file](/docker/development/lite_php_browscap.ini) but can be outdated.

Once you’ve placed this file on your system, you can add the following to your
`php.ini` file:

```ini
[browscap]
; This path must be absolute
browscap = /usr/local/etc/php/browscap.ini
```

Don’t forget to restart PHP:

```console
# systemctl restart php
```

You can find more information on Browscap at [php.net/browscap](https://php.net/browscap).

## Optional: Set a brand name

The generic brand name is “Flus”, but you might want to change it to
distinguish your instance from the other ones. This is pretty simple: uncomment
the `APP_BRAND` variable in your `.env` file, and set the name of your choice.
It’s recommended to choose a short name.

## Optional: Add terms of service

If your instance is opened, you may want to ask your users to accept the terms
of your service. For this, you must create the `policies/terms.html` file which
only accepts HTML. A checkbox should be added on the registration form then.

## Optional: Declare default feeds and bookmarks

When users register to the service, they can automatically subscribe to
defaults feeds or get default bookmarks.

Default feeds can be declared as an OPML file under the data folder:
`data/defaut-feeds.opml.xml`.

Default bookmarks can be declared as an Atom file under the data folder:
`data/defaut-bookmarks.atom.xml`.

To generate these files, the easiest is to create a trash account, subscribe to
the feeds and add bookmarks. Then, export your data via “Account & data”,
“Download your data”. You’ll find a `followed.opml.xml` and a
`bookmarks.atom.xml` files in the archive: that's the ones you’re looking for.

## Optional: Set a retention policy for the links in feeds

Over the time, the number of links in database will increase. Flus can handle
a lot of links, but you may want to keep the size of your DB under control. For
this, you have two (non-exclusive) options.

The most effective is to set the `FEEDS_LINKS_KEEP_MAXIMUM` variable in your
`.env` file. It takes a number greater or equal to 1. If set, the `Cleaner` job
will remove the links in excess every night (by removing the older ones). Note
that during the journey, Flus may create more links than the limit.

The second option is to purge the old links by setting the `FEEDS_LINKS_KEEP_PERIOD`
variable in your `.env` file. It takes a number of months, greater or equal to 1.
Links in feeds with an older publication date will then be purged every night.

If you want to keep a minimum number of links per feed (e.g. in case of a feed
which didn't publish for a long time), you can set `FEEDS_LINKS_KEEP_MINIMUM`
as well. Note that it should be smaller or equal to `FEEDS_LINKS_KEEP_MAXIMUM`.
On the contrary, the latter takes the priority.

Anytime you change these options, you should also consider to execute the
following command:

```console
flus# sudo -u www-data php cli feeds reset-hashes
```

This allows to force the synchronization of feeds that are unchanged to get
their old links in case you have loosened the retention policy.

## Optional: Close the registrations

You might want to setup a private instance of Flus. The registrations can be
closed by setting the environment variable `APP_OPEN_REGISTRATIONS` to `false`.
Thus, anonymous users will not be able to register. If you want to invite
friends or family on your instance, you’ll have to create their account via the
CLI:

```console
flus# sudo -u www-data php cli users create --username=Abby --email=email@example.com --password=secret
```

## Optional: Change the “What’s new?” feed

In the “Help & support” page, there’s a link named “What’s new?”. It
automatically redirects to the feed of the [Flus releases](https://github.com/flusio/Flus/releases)
on GitHub.

You can set a custom feed in your `.env` file (if you offer a service based on
Flus for instance, and that you have a blog explaining the latest changes):

```dotenv
APP_FEED_WHAT_IS_NEW=https://example.com/your/feed.xml
```

## Optional: Change CLI default locale

You can force the locale of CLI commands by setting the `CLI_LOCALE`
environment variable:

```env
CLI_LOCALE=fr_FR
```

Note the commands feedback are not translated. It can be used to choose the
locale of a user created via the CLI or seeds for instance.

## Optional: Configure a demo server

If you need to configure a demo server (this is probably NOT the case), you can
simply set the `APP_DEMO` variable to `true` in the `.env` file. It will add a
banner at the top of the screen to warn users that data are reset every night.
It will also consider the account with the `demo@flus.io` email as the demo
account. The reset is done through a scheduled job managed by Flus.

## Optional: Enable subscriptions

**This feature is not designed to be reused outside of the [flus.fr](https://flus.fr)
service.**

You can enable the subscription service by setting the `APP_SUBSCRIPTIONS_HOST`
and `APP_SUBSCRIPTIONS_PRIVATE_KEY` environment variables. The host must run
the code of [flusio/flus.fr](https://github.com/flusio/flus.fr).

A scheduled job run every 4 hours to sync the subscriptions.
