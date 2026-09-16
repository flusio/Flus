# How to contribute to Flus

## Before you start

First of all, please note that Flus is a solo-dev project.
Managing a community is not my priority.
I’ll do my best to welcome help and contributions though.

Also, I prefer to be contacted through [the official contact page](https://flus.fr/contact) as it allows me to centralise requests.
I’ll do my best to answer on GitHub too, but it won’t be the official communication channel.

You can write to me in French or in English.

## Subscribe to the service

I try to make a living from this project.
So if you like Flus, you can fund the project by subscribing to [the official service](https://flus.fr) (in French).

## Talk about Flus

You’re very welcome to talk about Flus in a blog post, a podcast, a newsletter, etc.
I provide some resources in French at [flus.fr/kit-presse](https://flus.fr/kit-presse), but don’t hesitate to contact me if you need more information.

## Submit an idea

If you have an idea to improve Flus, please contact me so we can discuss the problem you want to solve.
You can either use the contact page or open [an issue on GitHub](https://github.com/flusio/Flus/issues).

Please note that I will most likely reject a pull request implementing a feature that we haven’t discussed beforehand.
This is because a feature always interacts with other features.
Introducing one properly requires a good understanding of the existing features and of the code base.
It can also conflict with what I’m currently working on.

Even a small contribution can require a lot of time to review, to comment on and to maintain.
So make sure I will accept it before you start coding!

## Report a bug

If you find a bug, I would really appreciate it if you reported it to me.
You can either use the contact page or open [an issue on GitHub](https://github.com/flusio/Flus/issues).

Please be as detailed as possible in your report:

- What is the bug?
- What are the steps to reproduce it?
- If you don’t know how to reproduce it, what were you doing when it happened?
- What’s your environment? (operating system, browser, versions, etc.)

If the bug has a security impact, please don’t open a public issue: report it through [the contact page](https://flus.fr/contact) instead.

## Fix a bug

If you find a bug and know how to fix it, you’re welcome to open a pull request.

However, if your fix is quite significant (more than ~100 changed lines), please contact me first to make sure you’re on the right track.

Before opening a pull request:

- [set up the development environment](/docs/development.md);
- make sure the linters pass with `make lint`;
- make sure the tests pass with `make test` (see [how to run the test suite](/docs/tests.md)), and add a test if it makes sense;
- prefix your commit messages as in the Git history (`fix:`, `feat:`, `doc:`, etc.);
- fill in the pull request template.

## Improve the documentation

I try to document the main aspects of the project in the [`docs/` folder](/docs).
However, as it’s a solo-dev project, not every part of it is documented.

If some information is missing, don’t hesitate to ask me.
Then, if it makes sense to document it, I would appreciate it if you added it to the documentation by opening a pull request.

## About translations

Translations are often the easiest contributions to make to open source software.

However, the way translations are handled in Flus doesn’t make them easy to contribute to, so I discourage you from contributing to translations.

For now, I’m using [the Pro version of Poedit](https://poedit.com/purchase/) to extract and translate the strings, as it is required to extract strings from the Twig templates.

I intend to improve how translations are handled to make contributions easier, but it’s not a priority yet.

In the meantime, if your pull request adds or changes some strings, write them in English only: I’ll take care of the French translation myself.
