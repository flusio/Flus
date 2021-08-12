# How to improve performance

If you’re the only user of your instance, you probably don’t need to read this
document. However, if you start to have a lot of links and feeds to synchronize
and that your current setup doesn't hold the load, you should take the time to
read it.

Here, we’ll consider that you followed the [“Deploy in production”](/docs/production.md)
document, in particular the “Setup the job worker” section.

At a certain point, you might have too many feeds to fetch in an acceptable
time. Also, having a lot of feeds means having more and more links to
synchronize. This work is done by asynchronous jobs, via the worker you’ve
setup in the systemd service.

By default, the worker watches for all the jobs. It means that synchronization
can have an impact on other jobs (e.g. sending emails). Hopefully, jobs are
dispatched to different queues. The first thing to do is to adapt your systemd
service so you can start several workers dedicated to different queues.

First, make sure to stop the actual service:

```console
# systemctl stop flusio-worker
# systemctl disable flusio-worker
```

Then, rename the systemd service file into `/etc/systemd/system/flusio-worker@.service`
(the `@` is important!) Finally, change the file itself:

```systemd
[Unit]
Description=A job worker for flusio (queue %i)

[Service]
ExecStart=php /var/www/flusio/cli --request /jobs/watch -pqueue=%i
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target
```

You can now start multiple workers, each dedicated to a specific queue:

```console
# systemctl daemon-reload
# systemctl enable flusio-worker@default flusio-worker@mailers flusio-worker@fetchers1 flusio-worker@all
# systemctl start flusio-worker@default flusio-worker@mailers flusio-worker@fetchers1 flusio-worker@all
```

You may have noticed the `%i` parameter in the systemd file: it is replaced by
the value you give after the `@` when starting the services.

At this point, feeds and links synchronization (i.e. `fetchers` queue) should
no longer impact the other jobs.

However, you might still have performance issues. We’ll now increase the number
of jobs and workers to handle the synchronization. First, you need to increase
the values of the `JOB_FEEDS_SYNC_COUNT` and `JOB_LINKS_SYNC_COUNT` environment
variables in your `.env` file. Apply the change by executing the migration
command:

```console
flusio$ sudo -u www-data make update NO_DOCKER=true
```

It should tell you that your system is already up to date and seeds have been
loaded. Now, you must start more workers for the `fetchers` queue. To know how
many workers to start, add up values of `JOB_FEEDS_SYNC_COUNT` and `JOB_LINKS_SYNC_COUNT`
variables. For example, if you’ve chosen 2 and 2 jobs, you should start 3 more
services (for a total of 4):

```console
# systemctl enable flusio-worker@fetchers2 flusio-worker@fetchers3 flusio-worker@fetchers4
# systemctl start flusio-worker@fetchers2 flusio-worker@fetchers3 flusio-worker@fetchers4
```

Don’t forget to monitor your CPU and memory, the impact can be counterproductive!
It is recommended to increase the values one by one and check how your system
reacts.

To finish: a small tip. When you update flusio, you’ll need to restart the
services. Instead of doing it one by one, you can settle for:

```console
flusio$ sudo systemctl restart flusio-worker@*
```
