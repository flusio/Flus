# How to update flusio

This is quite simple, but there are some important things to note. First,
ALWAYS check if there are some migration notes in the [changelog](/CHANGELOG.md).
This is critical: you might miss important tasks to do otherwise and
potentially lose your data. Also, always make a backup of your data before
performing an update.

Then, you can pull the new code from GitHub:

```console
$ git status # check that you didn't make any change in your working directory
$ git pull --recurse-submodules
```

Apply the migrations:

```console
$ make update
```

That’s all!

Obviously, if you made changes in your own working directory, things might not
go so easily. Please always check the current status of the Git repository.

If at any time something goes wrong with the database, you can use the joker
command:

```console
$ make reset
```

It will reset the database and reload the schema. Obviously, you should avoid
this command in production or you will erase all the data.
