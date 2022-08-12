<h1 align="center">flusio</h1>

<p align="center">
    <strong>Aggregate, save and share links from all over the Web.</strong>
</p>

---

flusio brings together news feed aggregation and social bookmarking in a modern
way. It is designed as a simple, yet complete tool for organising the links you
gather around the Web. It comes with four main features:

- the **feeds aggregation** (<abbr>RSS</abbr> and Atom) to follow any website,
  podcast or video channel in one place;
- the **bookmarks and collections** to save your favourites articles for later
  and to organise them;
- the **news** to keep control over your newsfeed;
- the **profile** to share links with others.

You can try flusio for free at [demo.flus.fr](https://demo.flus.fr/).

It’s [free/libre software](https://en.wikipedia.org/wiki/Free_software)
(politically speaking) while being supported by a micro-enterprise to ensure
its viability. The main service is available to French people at [flus.fr](https://flus.fr).
You can help to fund the development by taking a subscription to the service.

flusio is licensed under [AGPL 3](/LICENSE.txt).

![Screenshot of the news page with 3 links](/public/static/screenshot.jpg)

## Credits and dependencies

flusio is built upon the work of many other people:

- the user experience and interface have been designed with the help of [Maiwann](https://www.maiwann.net/);
- the logo has been made by [Clara Chambon](https://www.clara-chambon.fr/);
- the font [Comfortaa](https://fonts.google.com/specimen/Comfortaa), by Johan Aakerlund;
- the font [Open Sans](https://fonts.google.com/specimen/Open+Sans), by Steve Matteson;
- the icons are from [the Clarity project](https://clarity.design/);
- the illustrations are from [the unDraw project](https://undraw.co), by [Katerina Limpitsouni](https://twitter.com/ninaLimpi);
- default cards illustrations are from [SVGBackgrounds](https://www.svgbackgrounds.com/).

It’s also based on other projects:

- [ClearURLs rules](https://clearurls.xyz/) to detect and remove trackers from
  URLs;
- [FakerPHP](https://fakerphp.github.io/) to generate fake data during tests;
- [Minz](https://github.com/flusio/Minz), a small and personal PHP framework;
- [Parcel](https://parceljs.org/), a build tool for JavaScript;
- [Parsedown](https://parsedown.org/) to render Markdown;
- [PHP\_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer),
  [Eslint](https://eslint.org/) and [Stylelint](https://stylelint.io/) to
  enforce PHP, JavaScript and CSS coding standards;
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) to send emails with PHP;
- [PHPUnit](https://phpunit.de/), a testing framework for PHP;
- [Stimulus](https://stimulus.hotwired.dev/), a modest JavaScript framework;
- [Turbo](https://turbo.hotwired.dev/) to bring speed of single-page
  applications to flusio.

## How to contribute?

I sincerely appreciate if you want to contribute. Here’s a few things you can
do:

- taking a subscription at [flus.fr](https://flus.fr) (French);
- reporting bugs or make feature requests [in issues](https://github.com/flusio/flusio/issues);
- writing blog posts to speak about the project.

I don’t accept Pull Requests on this project. A code contribution requires a
lot of time to review, to comment and to maintain. Even the smallest one can
require hours of my time. Also, code isn’t where I need help.

If you have any question, feel free to [send me a message](https://flus.fr/contact).

## Administrator guide

This guide is intended to people who want to install flusio on their own server.

1. [Deploy in production](/docs/production.md)
1. [How to update flusio](/docs/update.md)
1. [How to improve performance](/docs/performance.md)
1. [Enable experimental features](/docs/feature_flags.md)
1. [CHANGELOG](/CHANGELOG.md)

You also might be interested by the following:

1. [Technical stack overview](/docs/technical_stack.md)
1. [How is the CLI working](/docs/cli.md)

## Developer guide

If you plan to take a look at the code, these guides should be helpful to
understand how flusio is developed.

1. [Technical stack overview](/docs/technical_stack.md)
1. [Setup the development environment](/docs/development.md)
1. [How to update flusio](/docs/update.md)
1. [Getting started](/docs/getting_started.md)
1. [Working with Docker](/docs/docker.md)
1. [How are the users’ errors managed](/docs/errors.md)
1. [How is the CLI working](/docs/cli.md)
1. [How is the localization managed](/docs/localization.md)
1. [How are the assets bundled](/docs/assets.md)
1. [How to run the test suite](/docs/tests.md)

## Maintainer guide

This guide is intended to myself, as a maintainer of flusio.

1. [How to release a new version](/docs/release.md)
