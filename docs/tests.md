# How to run the test suite

Obviously, you should make sure to have a running development environment
first.

The tests can be simply executed with:

```console
$ make test
```

If you want to filter the tests to run, you can use the `FILE` and/or the
`FILTER` environment variables:

```console
$ make test FILE=./tests/PagesTest.php
$ make test FILTER=testHome
```

A code coverage analysis is generated under the `coverage/` folder and can be
opened with your browser:

```console
$ xdg-open coverage/index.html
```

If you want to change the coverage format, you can set the `COVERAGE`
environment variable. It takes one of the value of [the PHPUnit CLI
options](https://phpunit.readthedocs.io/en/9.0/textui.html#textui-clioptions).

```console
$ make test COVERAGE=--coverage-text
```

You also can run the linters with:

```console
$ make lint
$ # or, to fix errors detected by the linters
$ make lint-fix
```

The test suite is automatically executed on pull requests with [GitHub
Actions](https://github.com/flusio/flusio/actions). You can learn more by
having a look at the [workflow file](/.github/workflows/ci.yml).
