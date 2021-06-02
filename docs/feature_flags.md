# Enable experimental features

Some features might be available behind a feature flag. This allows to ship
new code and tests it in production without showing unfinished work to users.
You can enable them for specific users.

If you’re unfamiliar with the CLI, you’re encouraged to take a look at [its
documentation](/docs/cli.md).

First of all, you can list the features with the following command:

```console
$ ./cli --request /features
beta
```

Here, there is only one feature: beta.

You should now list the users to get there ids:

```console
$ ./cli --request /users
44ff4da402379f91ab0b1cf2a12bf6d4 2021-04-07 test1@example.com
a97f04ac01bce558a06fca5023cd3b54 2021-04-07 test2@example.com
8dff621fbf93ee18b39ee48fe6ec44d4 2021-04-14 test3@example.com
```

Each line corresponds to a user, it shows: id, creation date and email.

If you want to enable the feature `beta` for the user `test3@example.com`, you
must run the following command:

```console
$ ./cli --request /features/enable -ptype=beta -puser_id=8dff621fbf93ee18b39ee48fe6ec44d4
beta is enabled for user 8dff621fbf93ee18b39ee48fe6ec44d4 (test3@example.com)
```

Then, the user should be able to access the `beta` feature.

You can list the enabled flags:

```console
$ ./cli --request /features/flags
beta 8dff621fbf93ee18b39ee48fe6ec44d4 test3@example.com
```

Each line corresponds to an enabled flag, it shows: flag type, user id, user
email.

You can disable the flags at any moment:

```console
$ ./cli --request /features/disable -ptype=beta -puser_id=8dff621fbf93ee18b39ee48fe6ec44d4
beta is disabled for user 8dff621fbf93ee18b39ee48fe6ec44d4 (test3@example.com)
```
