<h1 align="center">flusio</h1>

<p align="center">
    <strong>A social media to soothe our online relationship with the news.</strong>
</p>

---

flusio is another yet social media. It brings together social bookmarking and
Web syndication to allow communities to stay up to date with the news and to
enhance their knowledge.

It’s [free/libre software](https://en.wikipedia.org/wiki/Free_software)
(politically speaking) while being supported by a micro-enterprise to ensure
its viability. The main service is available to French people at [flus.fr](https://flus.fr).
You can help to fund the development, by taking a subscription to the service.

flusio comes with three main features:

- the **Bookmarks** to save the articles to read later;
- the **News** to read within your available time;
- the thematic **Collections** to organise the information.

More is coming such as feed aggregation and community discussions.

flusio is licensed under [AGPL 3](/LICENSE.txt).

![Screenshot of the news page with 3 links](/public/static/screenshot.jpg)

## Credits

flusio is built upon the work of many other people:

- the user experience and interface have been designed with the help of [Maiwann](https://www.maiwann.net/);
- the logo has been made by [Clara Chambon](https://www.clara-chambon.fr/);
- the font [Comfortaa](https://fonts.google.com/specimen/Comfortaa), by Johan Aakerlund;
- the font [Open Sans](https://fonts.google.com/specimen/Open+Sans), by Steve Matteson;
- the icons are from [the Clarity project](https://clarity.design/);
- the illustrations are from [the unDraw project](https://undraw.co), by [Katerina Limpitsouni](https://twitter.com/ninaLimpi);
- default cards illustrations are from [SVGBackgrounds](https://www.svgbackgrounds.com/).

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
