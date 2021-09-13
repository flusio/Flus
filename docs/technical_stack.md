# Technical stack overview

flusio is using [PHP](https://www.php.net/) 7.3+. The version isn’t strongly
fixed and can change. While I’ll do my best to keep things simple, remember
that easy installation is not my main priority. If I absolutely need a specific
feature from PHP 7.4 for instance, I’ll not hesitate. Some PHP dependencies in
development are managed with [Composer](https://getcomposer.org/).

It’s using a personal PHP framework: [Minz](https://github.com/flusio/Minz).
The framework is directly integrated in the source code (as a submodule) since
it’s intended to evolve with the application.

The only supported database is [PostgreSQL](https://www.postgresql.org/)
because I plan to use specific features from it. Please do not ask support for
other kind of databases: even if it might work, this would add complexity to
maintain the whole software.

The asynchronous jobs, such as emails sending, are handled by a PHP script. The
worker should be configured as a OS service (e.g. systemd service), or can be
handled via a CRON task (but with less efficiency).

The assets (CSS and JS) are bundled with [Parcel](https://parceljs.org/). The
JavaScript relies on both [Stimulus](https://stimulus.hotwired.dev/) (“a modest
JavaScript framework”) and [Turbo](https://turbo.hotwired.dev/) (used to speed
up navigation on the application). The dependencies are managed with
[NPM](https://www.npmjs.com/), the default Node package manager. NPM is not
needed in production because I bundle assets and add them in the repository at
each new release.

The test suite is runned over [GitHub Actions](https://github.com/features/actions).
It’s using [PHPUnit 9](https://phpunit.de/) as a testing framework and
[PHP\_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) as a linter
for the PHP files. JS files are linted with [ESLint](https://eslint.org/docs/rules/semi)
and CSS files with [stylelint](https://stylelint.io/).

The development environment is powered by Docker and docker-compose setting up
4 containers: a PHP development server, a job worker, a PostgreSQL database and
a Node container running Parcel in watch mode. See the [`docker/` folder](/docker/)
for more information.
