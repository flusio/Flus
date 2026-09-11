<h1 align="center">Flus</h1>

<p align="center">
    <strong>Collect, organise, annotate and share links from around the Web.</strong>
</p>

---

Flus brings together feed aggregation, read-later lists and social bookmarking so you don’t have to juggle between multiple tools.
It lets you:

- **follow** sources such as blogs, podcasts or video channels (RSS/Atom/JSON feeds) and collections shared on Flus, organised into thematic streams;
- **read** at your own pace with the “Journal”, which suggests links from the sources you follow, and with the “To read” and “Links read” lists;
- **keep** your links in collections to easily find them again, and annotate them with your thoughts and tags;
- **share** your links, collections and streams publicly on your profile so other people can follow you, or privately to collaborate with specific people.

It also comes with many other features:

- search and privacy: a powerful search engine, detection of tracking parameters in URLs…
- interoperability: sharing via Atom feeds and Mastodon, OPML import, full data export, an API for developers…
- tools: a web app you can install on your phone, a browser extension for Firefox, Firefox Mobile and Chrome (or a bookmarklet).

You can try Flus for free at [demo.flus.fr](https://demo.flus.fr/).

It’s [free/libre software](https://en.wikipedia.org/wiki/Free_software) (politically speaking) while being supported by a micro-enterprise to ensure its viability.
The main service is available to French people at [flus.fr](https://flus.fr).
You can help to fund the development by subscribing to the service.
You can also host and operate Flus by yourself: [it’s all documented](#administrator-guide).

Flus is licensed under [AGPL 3](/LICENSE.txt).

![Screenshot of the “Web accessibility” stream, showing links from several sources grouped by day.](/public/static/screenshot.webp)

## Credits and dependencies

Flus is built upon the work of many other people:

- the user experience and interface have been designed with the help of [Maiwann](https://www.maiwann.net/) and [Elina Bufferne](https://fr.linkedin.com/in/elina-bufferne);
- the logo has been made by [Clara Chambon](https://www.clara-chambon.fr/);
- the font [Raleway](https://www.theleagueofmoveabletype.com/raleway), by Matt McInerney, Pablo Impallari, Rodrigo Fuenzalida
- the icons comes from [Lucide](https://lucide.dev/) and [Simple Icons](https://simpleicons.org);
- the illustrations are from [the unDraw project](https://undraw.co), by [Katerina Limpitsouni](https://ninalimpi.com/);
- default cards illustrations comes from [Pattern Monster](https://pattern.monster/).

It’s also based on other projects:

- [ClearURLs rules](https://clearurls.xyz/) to detect and remove trackers from URLs;
- [esbuild](https://esbuild.github.io/), a build tool for JavaScript;
- [FakerPHP](https://fakerphp.github.io/) to generate fake data during tests;
- [Minz](https://github.com/flusio/Minz), a small and personal PHP framework;
- [Parsedown](https://parsedown.org/) to render Markdown;
- [PHP\_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer),
  [Eslint](https://eslint.org/) and [Stylelint](https://stylelint.io/) to enforce PHP, JavaScript and CSS coding standards;
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) to send emails with PHP;
- [PHPUnit](https://phpunit.de/), a testing framework for PHP;
- [Stimulus](https://stimulus.hotwired.dev/), a modest JavaScript framework;
- [Turbo](https://turbo.hotwired.dev/) to bring speed of single-page applications to Flus.

## How to contribute?

I sincerely appreciate if you want to contribute.
Here’s a few things you can do:

- subscribing to the service at [flus.fr](https://flus.fr) (French);
- reporting bugs or make feature requests [in issues](https://github.com/flusio/Flus/issues);
- writing blog posts to speak about the project.

I don’t accept Pull Requests on this project.
A code contribution requires a lot of time to review, to comment and to maintain.
Even the smallest one can require hours of my time.
Also, code isn’t where I need help.

If you have any question, feel free to [send me a message](https://flus.fr/contact).

## Administrator guide

This guide is intended to people who want to install Flus on their own server.

1. [Deploy in production](/docs/production.md)
1. [How to update Flus](/docs/update.md)
1. [How to improve performance](/docs/performance.md)
1. [Enable experimental features](/docs/feature_flags.md)
1. [CHANGELOG](/CHANGELOG.md)

You also might be interested by the following:

1. [Technical stack overview](/docs/technical_stack.md)
1. [How is the CLI working](/docs/cli.md)

## Developer guide

If you plan to take a look at the code, these guides should be helpful to understand how Flus is developed.

1. [Technical stack overview](/docs/technical_stack.md)
1. [Setup the development environment](/docs/development.md)
1. [How to update Flus](/docs/update.md)
1. [Getting started](/docs/getting_started.md)
1. [REST API guide](/docs/api/README.md)
1. [Working with Docker](/docs/docker.md)
1. [How is the CLI working](/docs/cli.md)
1. [How is the localization managed](/docs/localization.md)
1. [How are the assets bundled](/docs/assets.md)
1. [How to run the test suite](/docs/tests.md)

## Maintainer guide

This guide is intended to myself, as a maintainer of Flus.

1. [How to release a new version](/docs/release.md)
