# How to update flusio

This is quite simple, but there are some important things to note. First,
ALWAYS check if there are some migration notes in the [changelog](/CHANGELOG.md).
This is critical: you might miss important tasks to do otherwise and
potentially lose your data. Also, always make a backup of your data before
performing an update.

Then, you can pull the new code from GitHub:

```console
flusio$ git status # check that you didn't make any change in your working directory
flusio$ git fetch --recurse-submodules
flusio$ git checkout TAG # to update to a specific version
flusio$ # OR, in development
flusio$ git pull
```

**In production,** you should change the owner of the files:

```console
flusio# chown -R www-data:www-data .
```

Then, apply the migrations:

```console
flusio$ make update NO_DOCKER=true
```

Finally, you might need to restart PHP so it detects localization changes:

```console
flusio$ sudo systemctl restart php
```

In development, just stop and restart the `make start` command.

That’s all!

Obviously, if you made changes in your own working directory, things might not
go so easily. Please always check the current status of the Git repository.

---

If at any time something goes wrong and you need to reset the application to
its previous state, you should start by reverse the migrations with:

```console
flusio$ make rollback STEP=1 NO_DOCKER=true
```

You can increase `STEP` to rollback more migrations (its default value is `1`
so its optional). Then, you can checkout to a previous version with:

```console
flusio$ git checkout PREVIOUS_TAG
```

If something goes really wrong with the database, you can use the joker command:

```console
flusio$ make reset NO_DOCKER=true
```

It will reset the database and reload the schema. **Obviously, you should avoid
this command in production or you will erase all the data.**
