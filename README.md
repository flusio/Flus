<h1 align="center">flusio</h1>

<p align="center">
    <strong>A citizen social media to soothe our online relationship with the news.</strong>
</p>

---

flusio is another yet social media. It brings together social bookmarking and
Web syndication to allow communities to stay up to date with the news and to
enhance their knowledge.

It’s [free/libre software](https://en.wikipedia.org/wiki/Free_software)
(politically speaking) while being supported by a micro-enterprise to ensure
its viability. The main service will be available to French people at
[flus.fr](https://flus.fr).

For now, the software doesn’t exist yet.

**The rest of this document is intended to potential developers.**

## How to contribute?

There’s no process to contribute yet, but feel free to [reach me by email](mailto://marien@flus.io).

## What’s the technical stack

flusio is using [PHP](https://www.php.net/) 7.3+. The version isn’t strongly
fixed and can change. While I’ll do my best to keep things simple, remember
that easy installation is not my main priority. If I absolutely need a specific
feature from PHP 7.4 for instance, I’ll not hesitate. The PHP dependencies are
managed with [Composer](https://getcomposer.org/).

It’s using a personal PHP framework: [Minz](https://github.com/flusio/Minz).
The framework is directly integrated in the source code (as a submodule) since
it’s intended to evolve with the application.

The only supported database is [PostgreSQL](https://www.postgresql.org/)
because I plan to use specific features from it. Please do not ask support for
other kind of databases: even if it might work, this would add complexity to
maintain the whole software.

The assets (CSS and JS) are bundled with [Parcel](https://parceljs.org/). The
JavaScript relies on both [Stimulus](https://github.com/stimulusjs/stimulus) (a
“modest” framework) and [Turbolinks](https://github.com/turbolinks/turbolinks)
(used to speed up navigation on the application). The dependencies are managed
with [NPM](https://www.npmjs.com/), the default Node package manager.

The test suite is runned over [GitHub Actions](https://github.com/features/actions).
It’s using [PHPUnit 9](https://phpunit.de/) as a testing framework and
[PHP\_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) as a linter
for the PHP files. JS files are linted with [ESLint](https://eslint.org/docs/rules/semi)
and CSS files with [stylelint](https://stylelint.io/).

The development environment is powered by Docker and docker-compose setting up
3 containers: a PHP development server, a PostgreSQL database and a Node
container running Parcel in watch mode. See the [`docker/` folder](./docker/)
for more information.

## How to setup the development environment?

For now, the development environment is managed with Docker by default. This is
to avoid installing the dependencies at a system level and to avoid (or at
least to reduse) issues with people not being able to setup the environment
(i.e. everyone run the same configured environment with the same dependencies).
If you don’t want to use Docker, I provide some information below to help you
to get started, but please don’t expect I’ll be able to help you: you know your
own setup better than me.

In both cases, you’ll need to download flusio with Git:

```console
$ git clone https://github.com/flusio/flusio.git
$ cd flusio
```

### With Docker

First, make sure to [install Docker](https://docs.docker.com/get-docker/) and
[Docker Compose](https://docs.docker.com/compose/install/).

Then, install the dependencies with:

```console
$ make install
```

This command will run the `composer` and `npm` install commands to download the
PHP and JS dependencies.

Once this is done, you must setup the environment with:

```console
$ make setup
```

It will copy the `env.sample` file to `.env` and call the flusio CLI to
configure the database. If you need to, you can change the environment
variables in the `.env`. The `SMTP_` variables should be set to be used with an
existing email account.

The last step is to start the server:

```console
$ make start
```

This command calls `docker-compose` with the file under the `docker/` folder.
The first time you call it, it will download the Docker images and build the
`php` one with the information from the `docker/Dockerfile.php` file.

Once the containers are running, you should be able to access flusio at
[localhost:8000](http://localhost:8000).

The containers can be stopped and cleaned with:

```console
$ make stop
```

Most of the time, you can settle for <kbd>CTRL + C</kbd> (the Docker network
and containers aren’t deleted this way).

### Without Docker

Here are some indicators if you’re not using Docker.

First, make sure you have PHP 7.3+, Node 14 and a running PostgreSQL 12 with a
user being able to create and drop a database. You also must install [the PHP
`composer` dependency manager](https://getcomposer.org/).

Then, create and edit the `.env` file:

```console
$ cp env.sample .env
$ vim .env # or whatever text editor you use
```

The most important section is the `DB_*` configuration. You can verify the
access to your database with:

```console
$ php ./cli --request /database/status
Database status: OK
```

If the status is not “OK”, you should fix the error that is shown.

You should now install the dependencies and setup the database with:

```console
$ export NO_DOCKER=true # tell the `make` commands to use native commands
$ make install
$ make setup
```

You’re all good now, just start a PHP development server:

```console
$ php -t public/ -S localhost:8000
```

And Parcel in another console:

```console
$ npm run watch
```

You can finally access flusio at [localhost:8000](http://localhost:8000).

## How to run basic commands with this Docker environment?

You might ask how to run basic commands such as `php` or `npm` since they are
only available in the Docker containers. One solution would be to execute them
with `docker exec`, but it would quickly become annoying. This is the reason why
I added some useful scripts under the [`docker/bin/` folder](./docker/bin/):

```console
$ ./docker/bin/php
$ ./docker/bin/npm
$ ./docker/bin/composer
$ ./docker/bin/cli
```

They only delegate the commands to their respective containers via `docker-compose`.
Just take a look at the files to understand!

## How to update flusio?

This is quite simple, but there are some important things to note. First,
ALWAYS check if there are some migration notes in the [changelog](./CHANGELOG.md).
This is critical: you might miss important tasks to do otherwise and
potentially lose your data. Also, always make a backup of your data before
performing an update.

Then, you can pull the new code from GitHub:

```console
$ git status # check that you didn't make any change in your working directory
$ git pull
```

Apply the migrations:

```console
$ make update
```

That’s all!

Obviously, if you made changes in your own working directory, things might not
go so easily. Please always check the current status of the Git repository.

## How to run the test suite?

Obviously, you should make sure to have a running development environment
first.

The tests can be simply executed with:

```console
$ make test
```

You also can run the linters with:

```console
$ make lint
$ # or, to fix errors detected by the linters
$ make lint-fix
```

The test suite is automatically executed on pull requests with [GitHub
Actions](https://github.com/flusio/flusio/actions). You can learn more by
having a look at the [workflow file](.github/workflows/ci.yml).

## How to get started?

If you want to understand the structure of the application, the first thing to
do is to understand [Minz](https://github.com/flusio/Minz/). Unfortunately, I
didn’t write its documentation yet so I’m going to give you some indications
here.

First thing, the entrypoint for the browser is the [`public/index.php`
file](./public/index.php). In fact, a correctly configured Web server must
never give access to another folder than `public/`. Otherwise, you have a
serious security breach in your system!

The index file creates a bunch of objects among which the two most important
are:

- the `Application` class (see [`src/Application.php`](./src/Application.php)),
  this is where the different accessible routes of the app are initialized and
  some other useful things as well;
- a `Request` object, which represents the request by the browser.

The `Application` passes the `Request` to an `Engine` which is able to execute
the correct action based on the routes. Actions are methods in controllers,
which are declared under [the `src/` folder](./src) as well.

To understand routing, just take this example:

```php
$router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');
```

It means a POST request to the `/sessions/locale` URL will execute the
`changeLocale` method of the [`Sessions` controller](./src/Sessions.php). The
last parameter allows us to reference this route more easily (e.g. in a view
file, we can output its URL with `<?= url('change locale') ?>`). Otherwise, we
would reference it by its action pointer (i.e. `Sessions#changeLocale`).

The last thing to know is an action ALWAYS returns a `Response`. A `Response`
has a HTTP code (e.g. 200, 302, 404) and a content. Most of the time, the
content comes from a view file that you can find under [the `src/views/`
folder](./src/views/). It also can declare headers to return to the browser.
The `Response` is returned to the index file which is responsible to output its
content and headers.

## How is the localization managed?

To localize flusio, I use the “old-but-still-good” [`gettext()` function](https://www.php.net/manual/function.gettext)
which is aliased by `_()` (you might have seen it from time to time).

Gettext allows me to write the code in full English (instead of some sort of
key string). It means that I can write directly, for instance `<?= _('Login') ?>`
instead of `<?= _('loginPage.form.submit') ?>` for a submit button in a login
form. It also means English is the default language of flusio.

For the other languages (only French at the moment), the locale files are
placed under the [`locales/` folder](./locales/). Each language defines at
least three files. For French:

- `locales/fr_FR/metadata.json` declares (only) the language in a
  human-readable way (“Français”);
- `locales/fr_FR/LC_MESSAGES/main.po` is where the strings are translated
- `locales/fr_FR/LC_MESSAGES/main.mo` is the compiled version of this file,
  used by PHP.

I don’t manipulate the two last files directly, but I use [Poedit](https://poedit.net/),
a translation editor which is able to manipulate `po` and `mo` files.

PHP is told where to find these files with the [`bindtextdomain()`](https://www.php.net/manual/function.bindtextdomain.php)
function and the language to use is set with [`setlocale()`](https://www.php.net/manual/function.setlocale.php).
All this happens in the [`src/Application.php` file](./src/Application.php).

## How are the assets bundled?

I use [Parcel](https://parceljs.org/) to bundle the assets. Be aware I use the
version 2 which is still [in alpha version](https://github.com/parcel-bundler/parcel/issues/3377).
I really like Parcel, but unfortunately version 1 is buggy with the setup I want…

Either if you started Parcel via docker-compose or NPM, it will look at two
files: [`src/assets/stylesheets/application.css`](./src/assets/stylesheets/application.css)
and [`src/assets/javascripts/application.js`](./src/assets/javascripts/application.js).
These files are the entrypoints and load the other CSS and JS files, which are
monitored by Parcel.

Each time Parcel detects a change in one of these files, it bundles the files
altogether and puts the bundled files under [the `public/static/` folder](./public/static/).
The files are finally loaded in the application [by the layout](./src/views/_layouts/base.phtml).

Please note you must never change directly the files under the `public/static/`
folder since they will be erased at the next build.

## License

flusio is licensed under [AGPL 3](LICENSE.txt).
