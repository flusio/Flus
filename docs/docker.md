# Working with Docker

You might ask how to run basic commands such as `php` or `npm` since they are
only available in the Docker containers. One solution would be to execute them
with `docker exec`, but it would quickly become annoying. This is the reason why
I added some useful scripts under the [`docker/bin/` folder](/docker/bin/):

```console
$ ./docker/bin/php
$ ./docker/bin/npm
$ ./docker/bin/composer
$ ./docker/bin/cli
```

They only delegate the commands to their respective containers via `docker-compose`.
Just take a look at the files to understand!
