# How to release a new version

Each time enough new features and bug fixes have been added to flusio, a new
version should be released. New versions should be released as often as
possible.

A version is a number of the form `x.y`. There’re no strict conventions
concerning this version numbers for now:

- x is the major version number and it is changed only once in a while, when
  flusio attained a new level of maturity;
- y is incremented when new features are added or removed, or when bugs are
  fixed.

If it becomes necessary, more complex rules could apply in the future.

A `make` target is provided to release a new version:

```console
$ make release VERSION=0.1
```

It bundles and minifies the assets under the `public/assets/` folder (via the
[`npm run build` command](/package.json)), opens the [changelog](/CHANGELOG.md)
in your editor so you can document the changes (at least to set the release
date) and commits these changes.
