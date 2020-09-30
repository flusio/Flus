# Deploy in production

Installing flusio on your own server is quite simple but still requires basic
notions in sysadmin. First, make sure you match with the following
requirements:

- git, Nginx, PHP 7.3+ and PostgreSQL are installed on your server;
- PHP requires `intl`, `gettext`, `pdo` and `pdo_pgsql` extensions;
- your PostgreSQL user must have the permission to create a database;
- flusio must be served over <abbr>HTTPS</abbr>.

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
# git clone --recurse-submodules https://github.com/flusio/flusio.git
# cd flusio
flusio# git checkout TAG
```

You must replace the `TAG` argument with the latest version that you can find
on the [GitHub releases page](https://github.com/flusio/flusio/releases).

Contrary to development, there’re nothing more to install: all the dependencies
are already here (at least if you didn’t forget the `--recurse-submodules`
argument) and assets are bundled for you so you don’t need to install Node and
its dependencies (this is only true if you didn’t forget to checkout to a
version tag).

## Configure the environment

You must now configure the environment by creating the `.env` file:

```console
flusio# cp env.sample .env
flusio# vim .env # or edit with nano or whatever editor you prefer
```

The environment file is commented so it should not be too complicated to setup
correctly.

You can check the database is correctly configured with:

```console
flusio# php ./cli --request /database/status
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
flusio# chown -R www-data:www-data .
```

You should also change the permissions on the `.env` file to limit the risks of
credentials being stolen. The `www-data` user only needs `read` permission:

```console
flusio# chmod 400 .env
```

## Setup the database

You must now load the SQL schema to your database. You can do it with:

```console
flusio# su - www-data -s /bin/bash -c 'php ./cli --request /system/setup'
flusio# # OR via make
flusio# su - www-data -s /bin/bash -c 'NO_DOCKER=true make setup'
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
    # directly to the flusio root folder!
    root /var/www/flusio/public;
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

If you’ve done everything right, you should now be able to access flusio at the
address you’ve configured, congratulations!

## Bonus: Create topics

Topics are used to categorize collections. They only can be created by the
administrator with the CLI for now:

```console
flusio# php ./cli --request /topics/create -plabel=LABEL
```

You must change `LABEL` by the name of your topic (e.g. economics, politics,
health). If you’ve made a mistake, you can delete a topic with:

```console
flusio# php ./cli --request /topics/delete -pid=ID
```

You must change `ID` by the id of an existing topic. You can find topic ids by
listing them:

```console
flusio# php ./cli --request /topics
```

## Bonus: Configure Browscap

We use Browscap to identify the users’ sessions via their user agent. flusio
can work without Browscap but the sessions will be identified as `Unknown
browser on unknown platform`.

Browscap is configured at the PHP level. It can be done by downloading the
latest version of [one of the `browscap.ini` files for PHP](https://browscap.org/).
A file is already provided in this repository as [`docker/lite_php_browscap.ini`
file](/docker/lite_php_browscap.ini) but can be outdated.

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

## Bonus: Set a brand name

The generic brand name is “flusio”, but you might want to change it to
distinguish your instance from the other ones. This is pretty simple: uncomment
the `APP_BRAND` variable in your `.env` file, and set the name of your choice.
It’s recommended to choose a short name.

## Bonus: Close the registrations

You might want to setup a private instance of flusio. The registrations can be
closed by setting the environment variable `APP_OPEN_REGISTRATIONS` to `false`.
Thus, anonymous users will not be able to register. If you want to invite
friends or family on your instance, you’ll have to create their account via the
CLI:

```console
flusio# php ./cli --request /users/create -pusername=Abby -pemail=email@example.com -ppassword=secret
```

## Bonus: Configure a demo server

If you need to configure a demo server (this is probably NOT the case), you can
simply set the `APP_DEMO` variable to `true` in the `.env` file. It will add a
banner at the top of the screen to warn users that data are reset every night.
It will also consider the account with the `demo@flus.io` email as the demo
account. The reset itself can be done with the following command:

```console
flusio# make reset-demo NO_DOCKER=true
```

It can be configured via a cron job.
