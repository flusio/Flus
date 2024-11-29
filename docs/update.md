# How to update Flus

This is quite simple, but there are some important things to note. First,
ALWAYS check if there are migration notes in the [changelog](/CHANGELOG.md).
This is critical: you might miss important tasks to do otherwise and
potentially lose your data. Also, always make a backup of your data before
performing an update.

Then, you can pull the new code from GitHub:

```console
flus$ git status # check that you didn't make any change in your working directory
flus$ git fetch
flus$ git checkout TAG # to update to a specific version
flus$ # OR, in development
flus$ git pull
```

Then, install the dependencies:

```console
flus$ # In production
flus$ composer install --no-dev --optimize-autoloader
flus$ # In development
flus$ make install
```

**In production,** you should change the owner of the files:

```console
flus# chown -R www-data:www-data .
```

Then, apply the migrations and load seeds with:

```console
flus$ sudo -u www-data make setup NO_DOCKER=true
```

Finally, you might need to restart PHP and the job worker so it detects
localization and code changes:

```console
flus$ sudo systemctl restart php flus-worker
```

**In development,** don’t prefix commands with `sudo -u www-data`. To restart
php, just stop and restart the `make docker-start` command. You also might want
to rebuild the Docker images from time to time with `make docker-build`.

That’s all!

Obviously, if you made changes in your own working directory, things might not
go so easily. Please always check the current status of the Git repository.

---

If at any time something goes wrong and you need to reset the application to
its previous state, you should start by reverse the migrations with:

```console
flus$ sudo -u www-data make rollback STEP=1 NO_DOCKER=true
```

You can increase `STEP` to rollback more migrations (its default value is `1`
so its optional). Then, you can checkout to a previous version with:

```console
flus$ git checkout PREVIOUS_TAG
```

If something goes really wrong with the database, you can use the joker command:

```console
flus$ sudo -u www-data make reset FORCE=true
```

It will reset the database and reload the schema. **Note this command doesn't
work in production.**
