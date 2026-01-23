# How is the CLI working

You might have noticed a CLI is provided. It can be run with the following command:

```console
$ ./docker/bin/cli
$ # or if you don't use Docker
$ php cli
```

By default, it shows you how to use the command. The syntax is pretty simple: it takes a serie of commands (e.g. `feeds sync`) and parameters (e.g. `--user_id=ID`).

The architecture of the [`cli` file](/cli) is, in fact, pretty similar to the [`public/index.php`](/public/index.php).
It just needs to create the Request object differently, and the `Application` class can be found in the [`src/cli/Application.php` file](/src/cli/Application.php).
The routers are different to avoid exposing the CLI routes to the browser.
They should be inaccessible either way since they use the `cli` via method instead of `get` or `post`: it’s only an additional security.

The CLI controllers work the same way as the other controllers, but they usually return text instead of HTML since it would be unreadable in a console.

Finally, the CLI exit code is 0 if the response HTTP code is 20x, and 1 otherwise.
