# Getting started

If you want to understand the structure of the application, the first thing to do is to understand [Minz](https://github.com/flusio/Minz/).
This document provides an overview of how the application runs on Minz.

You can take a look at the whole structure of the project with:

```console
$ make tree
```

It will show you all the folders and files of the project, except the dependencies, cache files, or downloaded medias.

Then, you should know that the entrypoint for the browser is the [`public/index.php` file](/public/index.php).
In fact, a correctly configured Web server must never give access to another folder than `public/`.
Otherwise, you have a serious security breach in your system!

The index file creates a bunch of objects among which the two most important are:

- a `Request` object, which represents the request made by the browser;
- the `Application` class (see [`src/Application.php`](/src/Application.php)), this is where the different accessible routes of the app are initialized and some other useful things as well.

The `Application` passes the `Request` to an `Engine` which is able to execute the correct action based on the routes.
Actions are methods in controllers, which are declared under [the `src/controllers` folder](/src/controllers).

To understand routing, just take this example:

```php
$router->addRoute('post', '/sessions/locale', 'Sessions#changeLocale', 'change locale');
```

It means a POST request to the `/sessions/locale` URL will execute the `changeLocale` method of the [`Sessions` controller](/src/Sessions.php).
The last parameter allows us to reference this route more easily (e.g. in a view file, we can output its URL with `{{ url('change locale') }}`).
Otherwise, we would reference it by its action pointer (i.e. `Sessions#changeLocale`).

The routes are declared in their own class: [`Router`](/src/Router.php).

The last thing to know is an action ALWAYS returns a `Response`.
A `Response` has a HTTP code (e.g. 200, 302, 404) and a content.
Most of the time, the content comes from a view file that you can find under [the `src/views/` folder](/src/views/).
It also can declare headers to return to the browser.
The `Response` is returned to the index file which is responsible to output its content and headers.
