# Setup the development environment

For now, the development environment is managed with Docker by default. This is
to avoid installing the dependencies at a system level and to avoid (or at
least to reduce) issues with people not being able to setup the environment
(i.e. everyone run the same configured environment with the same dependencies).
If you don’t want to use Docker, I provide some information below to help you
to get started, but please don’t expect I’ll be able to help you: you know your
own setup better than me.

In both cases, you’ll need to download Flus with Git:

```console
$ git clone --recurse-submodules https://github.com/flusio/Flus.git
$ cd Flus
```

## With Docker

First, make sure to [install Docker Engine](https://docs.docker.com/engine/install/).

Then, install the dependencies with:

```console
$ make install
```

This command will run the `composer` and `npm` install commands to download the
PHP and JS dependencies.

Once this is done, you should start the services:

```console
$ make docker-start
```

This command calls `docker compose` with the file under the `docker/` folder.
The first time you call it, it will download the Docker images and build the
`php` one with the information from the `docker/Dockerfile.php` file.

The last step is to setup the environment with:

```console
$ make setup
```

It will copy the `env.sample` file to `.env` and call the Flus CLI to
configure the database. If you need to, you can change the environment
variables in the `.env` file. The `SMTP_` variables should be set to be used
with an existing email account.

Now, you should be able to access Flus at [localhost:8000](http://localhost:8000).

The containers can be stopped and cleaned with:

```console
$ make docker-clean
```

Most of the time, you can settle for <kbd>CTRL + C</kbd> (the Docker network
and containers aren’t deleted this way).

## Without Docker

Here are some indicators if you’re not using Docker.

First, make sure you have PHP 8.2+, Node 14 and a running PostgreSQL 13 with a
user being able to create and drop a database. You also must install [the PHP
`composer` dependency manager](https://getcomposer.org/).

You might want to configure [browscap](https://www.php.net/manual/fr/misc.configuration.php#ini.browscap).
This allows to detect the browser and the platform of a user correctly in order
to identify a session. Without browscap, all the sessions will be identified as
“Unknown browser on unknown platform”.
You’ll have to place the [`docker/lite_php_browscap.ini` file](/docker/lite_php_browscap.ini)
somewhere on your filesystem and set the `browscap` path to this file in your
`php.ini` file ([see an example](/docker/php-ext-browscap.ini)). If you need
an up-to-date `browscap.ini` file, you can download one on [browscap.org](https://browscap.org/).

Then, create and edit the `.env` file:

```console
$ cp env.sample .env
$ vim .env # or whatever text editor you use
```

The most important section is the `DB_*` configuration. You can verify the
access to your database with:

```console
$ php cli database status
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
$ php -t public/ -S localhost:8000 public/index.php
```

Then start the job worker in a different console:

```console
$ php cli jobs watch
```

And Parcel in another console:

```console
$ npm run watch
```

You can finally access Flus at [localhost:8000](http://localhost:8000).

Please note that some tests require the [mock\_server](/tests/mock_server.php)
to run:

```console
$ php -t . -S localhost:8001 tests/mock_server.php
```
