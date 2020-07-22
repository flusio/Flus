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

A `make` target is provided to release a new version. It writes the new version
in the [`VERSION.txt` file](/VERSION.txt), it bundles and minifies the assets
under the `public/assets/` folder (via the [`npm run build` command](/package.json)),
opens the [changelog](/CHANGELOG.md) in your editor so you can document the
changes (at least to set the release date) and commits these changes.

You must run this command in a new branch, and push it on GitHub to create a
new pull request:

```console
flusio$ git checkout -b release/0.1
flusio$ make release VERSION=0.1
flusio$ git push -u origin release/0.1
```

Once you’ve reviewed and merged your pull request, you must make sure to push
the new tag on the server:

```console
flusio$ git push --tags
```

You must now [create a release](https://github.com/flusio/flusio/releases/new)
on GitHub. Use the tag just created (e.g. v0.1) for the release title.
Copy-paste the content of the changelog in the description field (don't forget
to adapt titles levels). Finally, publish the new release and celebrate!
