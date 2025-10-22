# Changelog of Flus

## unreleased

### Migration notes

After the update, you may want to run the new `CollectionsPublicationFrequencySync` job:

```console
$ php cli jobs
$ # Get the id of the job
$ php cli jobs run --id=<ID>
```

Depending on the number of collections in the database, this operation can take a long time or not.

This operation is not required as it will be run everyday at 3AM then.
It just allows to have coherent publication frequencies in the interface.

## 2025-09-19 - v2.0.4

### Bug fixes

- Fix the series SQL fetch queries ([fd2e7721](https://github.com/flusio/Flus/commit/fd2e7721))

## 2025-09-19 - v2.0.3

### API

- Add an endpoint to delete the current session ([6c7b4847](https://github.com/flusio/Flus/commit/6c7b4847))

### CLI

- Allow to pass a User-Agent to the `urls show` command ([1dcf0b0e](https://github.com/flusio/Flus/commit/1dcf0b0e))

### Technical

- Improve getting the collections and links to fetch ([45db972c](https://github.com/flusio/Flus/commit/45db972c))
- Lock links' URLs during synchronization ([27f082bc](https://github.com/flusio/Flus/commit/27f082bc))
- Update the dependencies ([7f79b359](https://github.com/flusio/Flus/commit/7f79b359), [60f63b33](https://github.com/flusio/Flus/commit/60f63b33), [9bbf0244](https://github.com/flusio/Flus/commit/9bbf0244))

### Developers

- Add healthchecks and dependencies in `docker-compose.yml` ([ce4a8c29](https://github.com/flusio/Flus/commit/ce4a8c29), [c97cf21f](https://github.com/flusio/Flus/commit/c97cf21f))

## 2025-09-05 - v2.0.2

### Bug fixes

- Fix illustrated cards' background ([9179402f](https://github.com/flusio/Flus/commit/9179402f))

## 2025-09-05 - v2.0.1

### Improvements

- Remove remaining purple and turquoise colors ([a32188a0](https://github.com/flusio/Flus/commit/a32188a0))
- Improve the look of the focus outline ([c6fa43b2](https://github.com/flusio/Flus/commit/c6fa43b2))

### Technical

- Uncompress gzipped HTTP responses ([f094b292](https://github.com/flusio/Flus/commit/f094b292))

## 2025-09-01 - v2.0.0

### New

- Provide a new design ([967bf76f](https://github.com/flusio/Flus/commit/967bf76f), [12706c3d](https://github.com/flusio/Flus/commit/12706c3d), [080ce351](https://github.com/flusio/Flus/commit/080ce351))
- Provide an API ([4a06a833](https://github.com/flusio/Flus/commit/4a06a833), [e219656a](https://github.com/flusio/Flus/commit/e219656a))
- Replace the comments by a notepad ([2fc30209](https://github.com/flusio/Flus/commit/2fc30209))
- Allow to do common actions on the link page ([d53b4ade](https://github.com/flusio/Flus/commit/d53b4ade))

### Technical

- Update the dependencies ([2425de1f](https://github.com/flusio/Flus/commit/2425de1f))

### Developers

- Provide a CSS columns system ([4ab21dc5](https://github.com/flusio/Flus/commit/4ab21dc5))
- Refactor tags extraction ([e1523366](https://github.com/flusio/Flus/commit/e1523366))
- List only "collection" type in Link::collections ([51feae86](https://github.com/flusio/Flus/commit/51feae86))
- Limit the Session name to 50 chars ([a003ae7a](https://github.com/flusio/Flus/commit/a003ae7a))
- Remove useless image names from docker-compose.yml ([d2c00415](https://github.com/flusio/Flus/commit/d2c00415))

## 2025-07-12 - v1.2.6

### Technical

- Decrease links refetching duration ([02cdc8c6](https://github.com/flusio/Flus/commit/02cdc8c6))
- Update the dependencies ([6166a470](https://github.com/flusio/Flus/commit/6166a470))

### Beta

- Improve the design according to the website theme ([9bacb7c3](https://github.com/flusio/Flus/commit/9bacb7c3), [fa2f49ed](https://github.com/flusio/Flus/commit/fa2f49ed), [bfcf5d7a](https://github.com/flusio/Flus/commit/bfcf5d7a), [8a1d1c3f](https://github.com/flusio/Flus/commit/8a1d1c3f), [84c80ec0](https://github.com/flusio/Flus/commit/84c80ec0), [775c11e8](https://github.com/flusio/Flus/commit/775c11e8), [89c7f726](https://github.com/flusio/Flus/commit/89c7f726))

## 2025-06-09 - v1.2.5

### Improvements

- Add a note to explain that news need to be clear ([64d15eff](https://github.com/flusio/Flus/commit/64d15eff))
- Improve the look of invalid inputs ([f79569e1](https://github.com/flusio/Flus/commit/f79569e1))

### Bug fixes

- Fix a typo in French translation ([7c69512b](https://github.com/flusio/Flus/commit/7c69512b))

### Technical

- Update the dependencies ([18bf993f](https://github.com/flusio/Flus/commit/18bf993f), [80994a78](https://github.com/flusio/Flus/commit/80994a78), [c0727b7d](https://github.com/flusio/Flus/commit/c0727b7d), [406a35d0](https://github.com/flusio/Flus/commit/406a35d0))
- Add a scope to sessions ([e27117d5](https://github.com/flusio/Flus/commit/e27117d5))
- Change the auth in the Subscriptions service ([3ede73a1](https://github.com/flusio/Flus/commit/3ede73a1))

### Developers

- Upgrade to Minz 2.0 ([5ae0ece9](https://github.com/flusio/Flus/commit/5ae0ece9))
- Refactor handling of the sessions ([ed804176](https://github.com/flusio/Flus/commit/ed804176))
- Refactor the login form ([93ddc92a](https://github.com/flusio/Flus/commit/93ddc92a))
- Remove the errors.md document ([eb8300bb](https://github.com/flusio/Flus/commit/eb8300bb))
- Provide a `.wrapper` CSS class ([994768d6](https://github.com/flusio/Flus/commit/994768d6))

## 2025-04-30 - v1.2.4

### Bug fixes

- Fix the account validation mechanism ([0836dba6](https://github.com/flusio/Flus/commit/0836dba6))

### Technical

- Update the Composer dependencies ([334d9dc0](https://github.com/flusio/Flus/commit/334d9dc0), [83859ccd](https://github.com/flusio/Flus/commit/83859ccd))

### Developers

- Refactor requiring logged-in user ([cda27108](https://github.com/flusio/Flus/commit/cda27108))
- Add a CSS utility class `.text--center` ([11ad4d29](https://github.com/flusio/Flus/commit/11ad4d29))

## 2025-04-16 - v1.2.3

### Improvements

- Improve the French translation of the inactivity email ([4d65b880](https://github.com/flusio/Flus/commit/4d65b880))

### Technical

- Allow to send support messages to a Bileto server ([c6bd416e](https://github.com/flusio/Flus/commit/c6bd416e))
- Sleep for a random number of seconds between two inactivity emails ([2c442040](https://github.com/flusio/Flus/commit/2c442040))

### Developers

- Allow to send JSON encoded data with the SpiderBits `Http` class ([fddcfa59](https://github.com/flusio/Flus/commit/fddcfa59))
- Update the dependencies ([73286b0b](https://github.com/flusio/Flus/commit/73286b0b))

## 2025-03-21 - v1.2.2

### Bug fixes

- Limit the memory used to calculate the collections' frequency ([6e15ebc3](https://github.com/flusio/Flus/commit/6e15ebc3))

### Technical

- Update the dependencies ([19b97089](https://github.com/flusio/Flus/commit/19b97089), [ad6ae4a7](https://github.com/flusio/Flus/commit/ad6ae4a7))

## 2025-02-21 - v1.2.1

### Improvements

- Display the publication frequency in the collection cards ([9a8158a4](https://github.com/flusio/Flus/commit/9a8158a4))

### Bug fixes

- Fix the synchronization of Youtube resources ([c3ff13d1](https://github.com/flusio/Flus/commit/c3ff13d1))
- Fix a typo in French translation ([27462124](https://github.com/flusio/Flus/commit/27462124))

### Technical

- Add official support for PHP 8.4 ([8f3d556c](https://github.com/flusio/Flus/commit/8f3d556c))
- Decrease the synchronization frequency based on the publication frequency ([2de24577](https://github.com/flusio/Flus/commit/2de24577))
- Improve the display of feed statistics ([55c248d4](https://github.com/flusio/Flus/commit/55c248d4))

### Developers

- Upgrade the dependencies ([fb7209c6](https://github.com/flusio/Flus/commit/fb7209c6), [37ed8db0](https://github.com/flusio/Flus/commit/37ed8db0), [e29325d8](https://github.com/flusio/Flus/commit/e29325d8), [79d64f8b](https://github.com/flusio/Flus/commit/79d64f8b), [444e0ff2](https://github.com/flusio/Flus/commit/444e0ff2))
- Get feeds to fetch from the new `feed_fetched_next_at` attribute ([cbc6736c](https://github.com/flusio/Flus/commit/cbc6736c))

## 2025-02-07 - v1.2.0

### Migration notes

Flus now requires [Composer](https://getcomposer.org/) in production.
You must install it and install the dependencies with:

```console
$ composer install --no-dev --optimize-autoloader
```

See [the documentation to update Flus.](/docs/update.md)

### New

- Delete automatically users after 1 year of inactivity ([2e744913](https://github.com/flusio/Flus/commit/2e744913), [fb625995](https://github.com/flusio/Flus/commit/fb625995))

### Improvements

- Display the collections' publication frequency ([8c490ae8](https://github.com/flusio/Flus/commit/8c490ae8))
- Allow titles in Markdown content ([6dcac125](https://github.com/flusio/Flus/commit/6dcac125))
- Allow to get a link to a comment ([94eda9ed](https://github.com/flusio/Flus/commit/94eda9ed))
- Always display the full date of comments ([537d6ce1](https://github.com/flusio/Flus/commit/537d6ce1))
- Homogeneize and improve the look of the emails ([7c9447de](https://github.com/flusio/Flus/commit/7c9447de), [9d89091c](https://github.com/flusio/Flus/commit/9d89091c), [b647b99e](https://github.com/flusio/Flus/commit/b647b99e), [8cccaa22](https://github.com/flusio/Flus/commit/8cccaa22))
- Forbid changing the credentials of the demo account ([ab30c7af](https://github.com/flusio/Flus/commit/ab30c7af))

### Bug fixes

- Treat ISO-8859-1 HTML responses as Windows-1252 ([a58a5dee](https://github.com/flusio/Flus/commit/a58a5dee))
- Fix the URL duplicating itself in the user agent ([30cbfaca](https://github.com/flusio/Flus/commit/30cbfaca))
- Allow to setup Flus if the database already exists ([f8dc4024](https://github.com/flusio/Flus/commit/f8dc4024), [eda82090](https://github.com/flusio/Flus/commit/eda82090))
- Handle correctly invalid HTTP method errors ([d73bab29](https://github.com/flusio/Flus/commit/d73bab29))

### Technical

- Configure Flus with Composer ([24c91753](https://github.com/flusio/Flus/commit/24c91753))
- Send "Accept" header when fetching feeds ([59fd37f3](https://github.com/flusio/Flus/commit/59fd37f3))
- Update the dependencies ([4a39d517](https://github.com/flusio/Flus/commit/4a39d517), [c04aa670](https://github.com/flusio/Flus/commit/c04aa670), [230e6a41](https://github.com/flusio/Flus/commit/230e6a41))

### Developers

- Upgrade to Minz 1.0 ([9872079a](https://github.com/flusio/Flus/commit/9872079a))
- Improve the PR template ([1fc47468](https://github.com/flusio/Flus/commit/1fc47468))
- Refactor fetching HTTP resources ([b3366ae7](https://github.com/flusio/Flus/commit/b3366ae7))
- Extract a FilesystemHelper class ([da442a4c](https://github.com/flusio/Flus/commit/da442a4c))
- Improve typing of the configuration ([45209fe1](https://github.com/flusio/Flus/commit/45209fe1))
- Replace some view variables by helper functions ([231dcb08](https://github.com/flusio/Flus/commit/231dcb08))
- Improve config files for PHPUnit, PHPStan and PHPCS ([96b2978a](https://github.com/flusio/Flus/commit/96b2978a))
- Install Rector ([eb23bfbf](https://github.com/flusio/Flus/commit/eb23bfbf))
- Reorganize the Docker development environment ([48a54387](https://github.com/flusio/Flus/commit/48a54387))
- Improve the Makefile ([82f389fa](https://github.com/flusio/Flus/commit/82f389fa))
- Fix the CI configuration ([42d40c0c](https://github.com/flusio/Flus/commit/42d40c0c))

## 2024-11-14 - v1.1.0

### New

- Enable the tagging system for everyone ([9b3c31c1](https://github.com/flusio/Flus/commit/9b3c31c1))

### Improvements

- Add feedback when autosubmit forms are submitted ([61fcba81](https://github.com/flusio/Flus/commit/61fcba81))

### Bug fixes

- Fix the sharing to Flus through the Android system sharing ([ef660258](https://github.com/flusio/Flus/commit/ef660258))
- Fix the appearance of the popup buttons during the submission ([05c2de51](https://github.com/flusio/Flus/commit/05c2de51))
- Fix overflowing news groups titles on Chrome ([aadb9786](https://github.com/flusio/Flus/commit/aadb9786))

### Technical

- Decrease the Pocket retrieve `count` parameter to 30 ([d2b23f35](https://github.com/flusio/Flus/commit/d2b23f35))
- Update the dependencies ([a958487f](https://github.com/flusio/Flus/commit/a958487f), [9d4519a9](https://github.com/flusio/Flus/commit/9d4519a9))

## 2024-11-01 - v1.0.4

### Improvements

- Use the system dialog to share URLs to Flus on mobile (not sure if it works :)) ([7a837c2e](https://github.com/flusio/Flus/commit/7a837c2e))
- Display the total number of links in the news ([26edbade](https://github.com/flusio/Flus/commit/26edbade))
- Improve the feedback of the disabled buttons ([8991c212](https://github.com/flusio/Flus/commit/8991c212))
- Explain that Markdown can be used in comments ([0010f425](https://github.com/flusio/Flus/commit/0010f425))
- Improve the description of Flus ([54c52978](https://github.com/flusio/Flus/commit/54c52978))

### Bug fixes

- Fix the position of the inner circle of radio buttons ([4542d36b](https://github.com/flusio/Flus/commit/4542d36b))
- Fix the Collection group's default value in forms ([c7083afb](https://github.com/flusio/Flus/commit/c7083afb))
- Fix the z-index of group headers ([dffbdd63](https://github.com/flusio/Flus/commit/dffbdd63))
- Generate correct links to tags in the "direct" Atom feeds ([ccbcbbc2](https://github.com/flusio/Flus/commit/ccbcbbc2))
- Handle the Pocket links without URL ([f14335ae](https://github.com/flusio/Flus/commit/f14335ae))

### Technical

- Remove the `apple-mobile-web-app*` meta tags ([0b77f022](https://github.com/flusio/Flus/commit/0b77f022))
- Add screenshots to the webmanifest ([8043f060](https://github.com/flusio/Flus/commit/8043f060))

### Developers

- Replace Parcel by esbuild ([5a988874](https://github.com/flusio/Flus/commit/5a988874))

### Beta

- Ignore case when searching for tags ([b8e55544](https://github.com/flusio/Flus/commit/b8e55544))
- Import Pocket tags as links tags ([8d36730f](https://github.com/flusio/Flus/commit/8d36730f))
- Explain that tags can be used ([0010f425](https://github.com/flusio/Flus/commit/0010f425))
- (tec) Add a GIN index on links.tags ([4faef23b](https://github.com/flusio/Flus/commit/4faef23b))

## 2024-10-18 - v1.0.3

### Improvements

- Improve the performance when checking if news are available ([41014681](https://github.com/flusio/Flus/commit/41014681))

### Bug fixes

- Fix the position of checkbox ticks of the topics selector ([eb698ace](https://github.com/flusio/Flus/commit/eb698ace))

### Technical

- Use the feed entry id as link if the link is missing ([edfcddd5](https://github.com/flusio/Flus/commit/edfcddd5))
- Update the ClearUrls rules ([51fb95e0](https://github.com/flusio/Flus/commit/51fb95e0))

### Developers

- Configure Mailpit to catch emails in development mode ([736a0871](https://github.com/flusio/Flus/commit/736a0871))
- Remove the NewsPicker service ([b30022da](https://github.com/flusio/Flus/commit/b30022da))

### Beta

- Allow to add tags to links ([6870e57b](https://github.com/flusio/Flus/commit/6870e57b))
- Allow to search for tags and URL parts ([4258fe2d](https://github.com/flusio/Flus/commit/4258fe2d), [14a859d2](https://github.com/flusio/Flus/commit/14a859d2))
- Display the links' tags ([4b63e012](https://github.com/flusio/Flus/commit/4b63e012))
- Make the tags in comments clickable ([fc648e37](https://github.com/flusio/Flus/commit/fc648e37))

## 2024-10-03 - v1.0.2

### Improvements

- Allow to follow a shared collection ([99b8cb18](https://github.com/flusio/Flus/commit/99b8cb18))
- Improve performance to get public links of a user ([56aae219](https://github.com/flusio/Flus/commit/56aae219))
- Improve the performance of news refreshing ([0d54d7c6](https://github.com/flusio/Flus/commit/0d54d7c6))
- Homogeneize the "remove from news" labels ([96907f33](https://github.com/flusio/Flus/commit/96907f33))

### Technical

- Improve performance to retrieve links to fetch ([805380d3](https://github.com/flusio/Flus/commit/805380d3))

### Developers

- Mock all the requests in LinksSyncTest ([0c514c5c](https://github.com/flusio/Flus/commit/0c514c5c))
- Refactor the NewsPicker API ([346e732e](https://github.com/flusio/Flus/commit/346e732e))
- Fix `popup__container--bottom` position ([ad8cdbd2](https://github.com/flusio/Flus/commit/ad8cdbd2))

## 2024-09-19 - v1.0.1

### Improvements

- Improve the performance when refreshing the news ([4cf99396](https://github.com/flusio/Flus/commit/4cf99396))
- Add a button to never see collections links ([d6b4e1bf](https://github.com/flusio/Flus/commit/d6b4e1bf))
- Improve the label of the terms of services ([f2c3f1ce](https://github.com/flusio/Flus/commit/f2c3f1ce))

### Bug fixes

- Allow to fetch information from youtu.be ([e67fa439](https://github.com/flusio/Flus/commit/e67fa439))
- Fix a typo in French translation ([88528d3c](https://github.com/flusio/Flus/commit/88528d3c))

### Technical

- Improve the performance of database reset ([eba02290](https://github.com/flusio/Flus/commit/eba02290))
- Split tests and linters actions on the CI ([b136a7e1](https://github.com/flusio/Flus/commit/b136a7e1))

## 2024-09-02 - v1.0.0

🎉

### Improvements

- Increase the default news period to 1 week ([cb54aba0](https://github.com/flusio/Flus/commit/cb54aba0))

### Bug fixes

- Fix "My links" page description ([ea9074dc](https://github.com/flusio/Flus/commit/ea9074dc))
- Fetch correct information from Youtube URLs ([252e0994](https://github.com/flusio/Flus/commit/252e0994))
- Support RSS feeds with dates in two-digits ([01f87f24](https://github.com/flusio/Flus/commit/01f87f24))

## 2024-06-14 - v0.60

### Migration notes

PHP 8.2+ is now required.

Depending on the number of links you store in the database, the last migration may take a very long time to end.
Consider to shutdown the service and warn your users before executing the migrations.

### Improvements

- Get the correct duration of Youtube videos ([e132baa3](https://github.com/flusio/Flus/commit/e132baa3))
- Enable the “Reading” tab for all ([c45d8bde](https://github.com/flusio/Flus/commit/c45d8bde))
- Bring back the global actions on the news ([bf0fdb61](https://github.com/flusio/Flus/commit/bf0fdb61))
- Reset the scroll when doing a "day" action in the news ([be6111bc](https://github.com/flusio/Flus/commit/be6111bc))
- Improve the feedback of the news button ([890b9324](https://github.com/flusio/Flus/commit/890b9324))

### Bug fixes

- Make the svg icons unselectable ([afdb1d97](https://github.com/flusio/Flus/commit/afdb1d97))
- Show the popup menu on top of the following dates groups ([4e0363e3](https://github.com/flusio/Flus/commit/4e0363e3), [58f12f90](https://github.com/flusio/Flus/commit/58f12f90))
- Align correctly the icons of date groups in the news ([d3f2bd8d](https://github.com/flusio/Flus/commit/d3f2bd8d))

### Technical

- Require PHP 8.2+ ([7283bdfa](https://github.com/flusio/Flus/commit/7283bdfa))
- Add support for PHP 8.3 ([3e490209](https://github.com/flusio/Flus/commit/3e490209))
- Replace `Link.url_lookup` by `url_hash` ([fd1fe711](https://github.com/flusio/Flus/commit/fd1fe711))
- Rename the software from flusio to Flus ([4e7e981c](https://github.com/flusio/Flus/commit/4e7e981c))
- Upgrade dependencies ([1f3c2fb9](https://github.com/flusio/Flus/commit/1f3c2fb9))

## 2024-04-02 - v0.59

### New

- Improve the overall "News" experience:
    - Display links grouped by dates in news ([a1ec6c29](https://github.com/flusio/Flus/commit/a1ec6c29), [7b5cb20b](https://github.com/flusio/Flus/commit/7b5cb20b))
    - Display links grouped by sources in news ([f9835613](https://github.com/flusio/Flus/commit/f9835613), [0952ee13](https://github.com/flusio/Flus/commit/0952ee13))
    - (beta) Change the "news" tab by a "reading" tab ([4d02e91c](https://github.com/flusio/Flus/commit/4d02e91c))
    - (beta) Load more links in the news from followed feeds ([f8acb421](https://github.com/flusio/Flus/commit/f8acb421), [d58c7938](https://github.com/flusio/Flus/commit/d58c7938))
    - (beta) Mark bookmarks as read when storing them ([84c870ab](https://github.com/flusio/Flus/commit/84c870ab))

### Bug fixes

- Fix the resizing of images with invalid transparency ([4953b107](https://github.com/flusio/Flus/commit/4953b107))
- Format the sessions dates with the correct locale ([63609b18](https://github.com/flusio/Flus/commit/63609b18))

### Technical

- Update Minz ([a0ee2b83](https://github.com/flusio/Flus/commit/a0ee2b83))

### Developers

- Update the dependencies ([74c84454](https://github.com/flusio/Flus/commit/74c84454), [774d75d5](https://github.com/flusio/Flus/commit/774d75d5))
- Remove the warning about docker-compose.yml version ([e6717742](https://github.com/flusio/Flus/commit/e6717742))
- Refactor the `_date` helper function ([28620cbc](https://github.com/flusio/Flus/commit/28620cbc))
- Rename the Link "via" fields to "source" ([e3902683](https://github.com/flusio/Flus/commit/e3902683))

## 2024-03-18 - v0.58

### New

- Allow users to accept to be contacted ([53fac741](https://github.com/flusio/Flus/commit/53fac741))

### Improvements

- Improve the look of multilines checkboxes ([60066866](https://github.com/flusio/Flus/commit/60066866))
- Display sessions names before IPs ([a212ae90](https://github.com/flusio/Flus/commit/a212ae90))
- Don't share to Mastodon by default (beta) ([d7a798e8](https://github.com/flusio/Flus/commit/d7a798e8))
- Completely anonymize IPs in demo mode ([4d72e2ee](https://github.com/flusio/Flus/commit/4d72e2ee))

### Bug fixes

- Fix broken modal/menu when going back in browser history ([441053a2](https://github.com/flusio/Flus/commit/441053a2))
- Sanitize URL queries containing spaces correctly ([49c324f8](https://github.com/flusio/Flus/commit/49c324f8))
- Lowercase the scheme in URLs during sanitizing ([220832d2](https://github.com/flusio/Flus/commit/220832d2))
- Remove the GitHub URL from UA in prod and test ([6377f42e](https://github.com/flusio/Flus/commit/6377f42e))
- Allow showcase pages to be loaded in a modal ([bbbc5cbc](https://github.com/flusio/Flus/commit/bbbc5cbc))

### Technical

- Update dependencies ([e76943af](https://github.com/flusio/Flus/commit/e76943af), [2cf08968](https://github.com/flusio/Flus/commit/2cf08968), [e8e56694](https://github.com/flusio/Flus/commit/e8e56694), [bf97bee3](https://github.com/flusio/Flus/commit/bf97bee3), [b2f7e374](https://github.com/flusio/Flus/commit/b2f7e374), [f219c589](https://github.com/flusio/Flus/commit/f219c589))
- Allow to configure Plausible ([f35878ee](https://github.com/flusio/Flus/commit/f35878ee))
- Clean empty directories when cleaning media ([87346a90](https://github.com/flusio/Flus/commit/87346a90))
- Improve output of the system stats command ([244278dc](https://github.com/flusio/Flus/commit/244278dc))
- Extract statistics in a dedicated CLI command ([8361c898](https://github.com/flusio/Flus/commit/8361c898))
- Provide statistics as CSV ([578a8489](https://github.com/flusio/Flus/commit/578a8489))
- Add a link to the about page in the user agent ([dfd97a7c](https://github.com/flusio/Flus/commit/dfd97a7c))
- Improve requirements declaration in Composer ([eadf05b4](https://github.com/flusio/Flus/commit/eadf05b4))

### Documentation

- Document "restart on-failure" in the production doc ([6767ed72](https://github.com/flusio/Flus/commit/6767ed72))

## 2023-09-08 - v0.57

Browscap is now used to identify the sessions of the user.
It is recommended to enable it (and update the browsercap.ini file, see [browscap.org](https://browscap.org/)).

IP and user agent are now processed to help to detect attacks.
The last bit of the IP is anonymized and only the browser and the platform are used from the user agent.
You may want to adapt your GDPR files according to these changes.

### New

- Allow to list and manage sessions ([2eb23747](https://github.com/flusio/Flus/commit/2eb23747))
- (beta) Allow to share to Mastodon ([ad529d4c](https://github.com/flusio/Flus/commit/ad529d4c))

### Improvements

- Reorganize the "Account & data" page ([c93afb83](https://github.com/flusio/Flus/commit/c93afb83))
- Autosave content of textareas ([facd1be9](https://github.com/flusio/Flus/commit/facd1be9))

### Bug fixes

- Don't render the "new link" page in a modal after a change ([19b63332](https://github.com/flusio/Flus/commit/19b63332))
- Edit the profile in the modale ([ee452d47](https://github.com/flusio/Flus/commit/ee452d47))
- Fix the French locale file ([63ba73a9](https://github.com/flusio/Flus/commit/63ba73a9))

## 2023-08-07 - v0.56

### Bug fixes

- Ignore invalid URLs when synchronising Pocket ([f966578c](https://github.com/flusio/Flus/commit/f966578c))
- Ignore invalid URLs when synchronising feeds ([00214993](https://github.com/flusio/Flus/commit/00214993))
- Handle Response::utf8Data with unsupported encodings ([a82bb534](https://github.com/flusio/Flus/commit/a82bb534))
- Make sure to pass non-empty strings to SpiderBits\\Dom ([45d60fd3](https://github.com/flusio/Flus/commit/45d60fd3))
- Compare LinksToCollections only on created\_at ([487adc41](https://github.com/flusio/Flus/commit/487adc41))

### Technical

- Execute Cleaner job at 01:00 AM ([1f533403](https://github.com/flusio/Flus/commit/1f533403))
- Update the Composer dependencies ([dff1ccf3](https://github.com/flusio/Flus/commit/dff1ccf3))
- Update the NPM dependencies ([bd193407](https://github.com/flusio/Flus/commit/bd193407))

### Developers

- Test that migrations and rollbacks can be applied ([7f85b727](https://github.com/flusio/Flus/commit/7f85b727))
- Upgrade to PHPUnit 10 ([d53c58d6](https://github.com/flusio/Flus/commit/d53c58d6))

## 2023-08-02 - v0.55

### Migration notes

PHP 8.1+ is now required. Please check your version before updating flusio!

The CLI command to execute a single job has changed.
You should now execute:

```console
$ php cli jobs watch --stop-after=1
```

The `run` command now takes a job id to execute a specific job.

WebP support is now required as all the (new) images are now converted to WebP.

### New

- Support the JSON feed format ([41a7701a](https://github.com/flusio/Flus/commit/41a7701a))

### Improvements

- Display the feeds description as HTML ([28f23088](https://github.com/flusio/Flus/commit/28f23088))
- Allow to click on the link title to open its URL on the comments page ([0646a3d1](https://github.com/flusio/Flus/commit/0646a3d1))

### Bug fixes

- Make sure to redirect after adding a feed ([3ff109d6](https://github.com/flusio/Flus/commit/3ff109d6))
- Fix a typo in the French Pocket error message ([14d21628](https://github.com/flusio/Flus/commit/14d21628))

### Technical

- Set minimal version of PHP to 8.1 ([8741c97a](https://github.com/flusio/Flus/commit/8741c97a))
- Add support for PHP 8.2 ([4eda049f](https://github.com/flusio/Flus/commit/4eda049f))
- Save all new images to WebP ([cc0acbd6](https://github.com/flusio/Flus/commit/cc0acbd6))
- Limit size of illustrations to 5 Mo ([ae56a29c](https://github.com/flusio/Flus/commit/ae56a29c))
- Update the dependencies ([4d3a7af2](https://github.com/flusio/Flus/commit/4d3a7af2))

### Developers

- Upgrade Minz to its last version ([0717a0ac](https://github.com/flusio/Flus/commit/0717a0ac))
- Setup PHPStan ([afa0bdda](https://github.com/flusio/Flus/commit/afa0bdda))
- Use Docker Compose v2 ([770ef50f](https://github.com/flusio/Flus/commit/770ef50f))
- Serve files with Nginx in development ([6d59863f](https://github.com/flusio/Flus/commit/6d59863f))
- Improve the detection of the Responses encoding ([69b0f271](https://github.com/flusio/Flus/commit/69b0f271))
- Increase HTTP buffer size when setting max size ([9bfb3738](https://github.com/flusio/Flus/commit/9bfb3738))
- Provide a SpiderBits\HtmlSanitizer class ([adeff002](https://github.com/flusio/Flus/commit/adeff002))
- Don't clone DomDocument when using Dom#select ([5c4c1f49](https://github.com/flusio/Flus/commit/5c4c1f49))
- Accept LibXML options in the Dom::fromText method ([72f0d711](https://github.com/flusio/Flus/commit/72f0d711))
- Provide a Dom::html method ([a2bd4294](https://github.com/flusio/Flus/commit/a2bd4294))
- Extract a PocketAccount model from User ([3aacd09a](https://github.com/flusio/Flus/commit/3aacd09a))
- Don't require real Pocket key during tests ([3e9a4e49](https://github.com/flusio/Flus/commit/3e9a4e49))
- Fail the Mailer job if the email cannot be sent ([9c4517c0](https://github.com/flusio/Flus/commit/9c4517c0))

## 2023-04-07 - v0.54

### Security

- Reset the session if it exists but the token is not in the cookies ([0f537541](https://github.com/flusio/Flus/commit/0f537541))

### Bug fixes

- Absolutize correctly the URLs relatively to the $base\_url document ([8609c787](https://github.com/flusio/Flus/commit/8609c787))
- Absolutize correctly the URLs starting with a hash ([7bf78bcc](https://github.com/flusio/Flus/commit/7bf78bcc))
- Validate replies URL correctly ([db3103c3](https://github.com/flusio/Flus/commit/db3103c3))
- Take next job with the oldest perform\_at ([ebc484a8](https://github.com/flusio/Flus/commit/ebc484a8))
- Reschedule jobs considering Daylight Saving Time ([49b25c6f](https://github.com/flusio/Flus/commit/49b25c6f))
- Fix mapping of entries ids in FeedFetcher ([cbe374bf](https://github.com/flusio/Flus/commit/cbe374bf))

### Misc

- Force the xdebug version in the Dockerfile ([6effdf05](https://github.com/flusio/Flus/commit/6effdf05))

## 2022-10-28 - v0.53

### Improvements

- Improve the selector of collections ([02b12c70](https://github.com/flusio/Flus/commit/02b12c70))
- Improve outline of header links ([f0cd2db3](https://github.com/flusio/Flus/commit/f0cd2db3))
- Improve error message when email is invalid during login ([ef4a9bca](https://github.com/flusio/Flus/commit/ef4a9bca))
- Improve compatibility with passwords managers ([54f4c013](https://github.com/flusio/Flus/commit/54f4c013))
- Rename label of read links card ([9130e6c7](https://github.com/flusio/Flus/commit/9130e6c7))
- Remove user menu and alerts from Turbo cache ([9f2534ca](https://github.com/flusio/Flus/commit/9f2534ca))

### Bug fixes

- Display the correct link when URL is duplicated ([16a41d51](https://github.com/flusio/Flus/commit/16a41d51))
- Exclude links only in the never list from the search ([b80eb736](https://github.com/flusio/Flus/commit/b80eb736))
- Render links update errors in the edit form ([649c0f36](https://github.com/flusio/Flus/commit/649c0f36))
- Show the collection delete button only if it's owned ([06875bf4](https://github.com/flusio/Flus/commit/06875bf4))
- Don’t warn about tracking when repairing form has errors ([5773763b](https://github.com/flusio/Flus/commit/5773763b))
- Hide links actions to unconnected users ([75c1b059](https://github.com/flusio/Flus/commit/75c1b059))

### Misc

- Change how modals are rendered ([fcabfbe7](https://github.com/flusio/Flus/commit/fcabfbe7))
- Update dependencies:
    - Update Minz lib ([5a1a09bd](https://github.com/flusio/Flus/commit/5a1a09bd))
    - Update Turbo to 7.2.4 ([e3eaf68a](https://github.com/flusio/Flus/commit/e3eaf68a))
    - Update Stimulus to 3.1.0 ([be301915](https://github.com/flusio/Flus/commit/be301915))
    - Update parcel to 2.7.0 ([81283df7](https://github.com/flusio/Flus/commit/81283df7))
    - Update with audit fix ([cb516879](https://github.com/flusio/Flus/commit/cb516879))

## 2022-10-07 - v0.52

### Migration notes

In development, commands `make start` and `make stop` have been renamed to
`make docker-start` and `make docker-clean`. You’ll also have to rebuild the
bundler image with `make docker-build`.

You can set the new `APP_FEED_WHAT_IS_NEW` environment variable to change the
feed served by the “What’s new?” link (in “Help & support”).

### New

- Provide a compact mode for links ([6bfd55a6](https://github.com/flusio/Flus/commit/6bfd55a6))
- Provide a “Preferences” page to change locale and enable beta features ([b13d4999](https://github.com/flusio/Flus/commit/b13d4999))
- Provide a “What's new?” page ([14ffb40e](https://github.com/flusio/Flus/commit/14ffb40e))
- Add anchors to external comments on links from feeds ([63343e06](https://github.com/flusio/Flus/commit/63343e06))

### Improvements

- Track “via” info on mark as read and read later ([7795570d](https://github.com/flusio/Flus/commit/7795570d))
- Move “Login & security” in “Account & data” ([16590954](https://github.com/flusio/Flus/commit/16590954))
- Add more purple to buttons in links footers ([c4dc24f5](https://github.com/flusio/Flus/commit/c4dc24f5))
- Improve the look of buttons at the bottom of feeds cards ([6603ef9b](https://github.com/flusio/Flus/commit/6603ef9b))
- Improve the look of the current item in pagination ([277f19a3](https://github.com/flusio/Flus/commit/277f19a3))
- Export collections feeds with `direct=true` in OPMLs ([6160516a](https://github.com/flusio/Flus/commit/6160516a))

### Bug fixes

- Allow to click on the top of links images ([90fa04f5](https://github.com/flusio/Flus/commit/90fa04f5))
- Fix various outlines style ([33fe6284](https://github.com/flusio/Flus/commit/33fe6284))
- Try to fix a bug on links actions ([c9ecbd10](https://github.com/flusio/Flus/commit/c9ecbd10))

### Misc

- Support more DateTime formats in feeds parser ([9fc53d5d](https://github.com/flusio/Flus/commit/9fc53d5d))
- (dev) Use ubuntu-22.04 on the CI ([bdb935b5](https://github.com/flusio/Flus/commit/bdb935b5))
- (dev) Update svg-sprite to 2.0.0 ([09362b93](https://github.com/flusio/Flus/commit/09362b93))
- (dev) Update Node and NPM in the Docker image ([8d473217](https://github.com/flusio/Flus/commit/8d473217))
- (dev) Add a command to rebuild Docker images ([448ec722](https://github.com/flusio/Flus/commit/448ec722))
- (dev) Prepend start and stop make targets with docker ([f86e1fe0](https://github.com/flusio/Flus/commit/f86e1fe0))

## 2022-09-23 - v0.51

### Improvements

- Preserve scroll on links actions ([7124f456](https://github.com/flusio/Flus/commit/7124f456))
- Improve the links UX:
    - Reorganize links actions ([933815de](https://github.com/flusio/Flus/commit/933815de))
    - Move the delete action to the "Actions" menus ([612d318a](https://github.com/flusio/Flus/commit/612d318a))
    - Homogeneize links collections management ([161677a5](https://github.com/flusio/Flus/commit/161677a5))
    - Improve the look of links on mobile ([6d2e7ada](https://github.com/flusio/Flus/commit/6d2e7ada))
    - Autoload a modal to explain recent changes to the links ([72fc4405](https://github.com/flusio/Flus/commit/72fc4405))
- Improve pagination ([cd1bc184](https://github.com/flusio/Flus/commit/cd1bc184))
- Fix and homogeneize focus outlines ([d1d3bf02](https://github.com/flusio/Flus/commit/d1d3bf02) and [c40ca054](https://github.com/flusio/Flus/commit/c40ca054))
- Add the link title to the illustrations alt ([be6974b4](https://github.com/flusio/Flus/commit/be6974b4))
- Improve the look of the "More help" section ([df8c1237](https://github.com/flusio/Flus/commit/df8c1237))

### Bug fixes

- Avoid repaired links to reappear in the news ([55fad70a](https://github.com/flusio/Flus/commit/55fad70a))
- Fix invalid prefix in feeds XSL file ([7a63d9a0](https://github.com/flusio/Flus/commit/7a63d9a0))
- Fix canonical links with several parameters ([25f95e92](https://github.com/flusio/Flus/commit/25f95e92))

### Misc

- Remove unused icons ([c4b57982](https://github.com/flusio/Flus/commit/c4b57982))
- Accept `dc:date` and `dc:created` in RSS feeds ([3578a811](https://github.com/flusio/Flus/commit/3578a811))

## 2022-09-10 - v0.50

### Migration notes

A new `FEEDS_LINKS_KEEP_MAXIMUM` environment variable can be set to keep a
maximum number of links per feed. You’ll have to set the variable in your
`.env` file and restart your jobs workers.

When you change values of `JOB_FEEDS_SYNC_COUNT` and `JOB_LINKS_SYNC_COUNT`,
you can now execute the command `php cli jobs install` instead of executing
the migrations command. It is quite the same under the hood, but it should be
more intuitive.

### New

- Display the number of links in collections ([f1e8f3b4](https://github.com/flusio/Flus/commit/f1e8f3b4))
- Paginate the bookmarks ([ba84a1fb](https://github.com/flusio/Flus/commit/ba84a1fb))
- Create an Add-ons page ([a709c586](https://github.com/flusio/Flus/commit/a709c586))

### Security

- Force the login redirection on the current instance ([2f087f2f](https://github.com/flusio/Flus/commit/2f087f2f))

### Improvements

- Move "About" menu item in "Help & support" ([ab89479e](https://github.com/flusio/Flus/commit/ab89479e))
- Enlarge the avatar menu popup container ([f3fdf68a](https://github.com/flusio/Flus/commit/f3fdf68a))

### Misc

- Handle feeds declaring a wrong encoding ([0cb9840e](https://github.com/flusio/Flus/commit/0cb9840e))
- Add Referrer-Policy and X-Content-Type-Options headers ([fe2e227d](https://github.com/flusio/Flus/commit/fe2e227d))
- Display icons with svg symbols ([99388f10](https://github.com/flusio/Flus/commit/99388f10))
- Provide a robots.txt file ([13bb3694](https://github.com/flusio/Flus/commit/13bb3694))
- Don't create too old links in FeedFetcher ([53a10c0a](https://github.com/flusio/Flus/commit/53a10c0a))
- (admin) Allow to limit the number of links in feeds ([26f1de9d](https://github.com/flusio/Flus/commit/26f1de9d))
- (admin) Add a /jobs/install command ([469f49ad](https://github.com/flusio/Flus/commit/469f49ad))
- (admin) Add more info to the /system command ([e936b400](https://github.com/flusio/Flus/commit/e936b400))
- (dev) Provide a command to create an icon sprite ([961c26f2](https://github.com/flusio/Flus/commit/961c26f2))

## 2022-08-15 - v0.49

### Improvements

- Allow to repair links directly by clicking on warning ([1efc4c10](https://github.com/flusio/Flus/commit/1efc4c10))
- Improve links warnings visibility ([d211533f](https://github.com/flusio/Flus/commit/d211533f))
- Change "ask sync" by "force sync" ([a3c19380](https://github.com/flusio/Flus/commit/a3c19380))
- Suggest to delete link when cleared url is empty ([221616c9](https://github.com/flusio/Flus/commit/221616c9))
- Remove the separator above the unread link button ([83f26f89](https://github.com/flusio/Flus/commit/83f26f89))
- (admin) Indicate that links count is estimated in /system command ([8315c9e9](https://github.com/flusio/Flus/commit/8315c9e9))

### Bug fixes

- Allow to submit the repair form with enter ([22caafee](https://github.com/flusio/Flus/commit/22caafee))
- Get newest feeds links when `keep_period` is set ([9cd437ae](https://github.com/flusio/Flus/commit/9cd437ae))
- Fix the `Url::percentRecodeQuery()` method ([23be6a62](https://github.com/flusio/Flus/commit/23be6a62))
- Handle URLs with "&" correctly ([85935bbf](https://github.com/flusio/Flus/commit/85935bbf))
- Consider unfetched links to not be in error ([f81136c0](https://github.com/flusio/Flus/commit/f81136c0))

### Misc

- Provide ClearURLs rules README and license ([8869bfc0](https://github.com/flusio/Flus/commit/8869bfc0))

## 2022-08-12 - v0.48

### Migration notes

A new `FEEDS_LINKS_KEEP_MINIMUM` environment variable can be set to keep a
minimum number of links per feed when purging is enabled (see `FEEDS_LINKS_KEEP_PERIOD`).
You’ll have to set the variable in your `.env` file and restart your jobs
workers. You may also want to execute the `cli feeds reset-hashes` command so
the old unchanged feeds can be synchronized again to get old links.

### New

- Warn about trackers in URLs ([172fa8be](https://github.com/flusio/Flus/commit/172fa8be))
- Allow to repair links ([2d68e25d](https://github.com/flusio/Flus/commit/2d68e25d))
- Allow to edit links reading time ([d1aa62a3](https://github.com/flusio/Flus/commit/d1aa62a3))
- Allow to edit messages ([2cf523db](https://github.com/flusio/Flus/commit/2cf523db))

### Improvements

- Show an icon on links in error ([8f668e43](https://github.com/flusio/Flus/commit/8f668e43))
- Show who added a link on the comments page ([65e9584d](https://github.com/flusio/Flus/commit/65e9584d))
- Move links edit button at the end of the menu ([b60e8351](https://github.com/flusio/Flus/commit/b60e8351))
- Lighten the background of blockquotes ([eb56e0f0](https://github.com/flusio/Flus/commit/eb56e0f0))
- Allow to set feeds `<link rel="alternate">` to original links ([bc0d8acb](https://github.com/flusio/Flus/commit/bc0d8acb))
- Improve perf when displaying link collections ([0a474ba9](https://github.com/flusio/Flus/commit/0a474ba9))
- (admin) Allow to keep a minimum number of links in feeds ([71e20731](https://github.com/flusio/Flus/commit/71e20731))
- (admin) Make /cli/clean command more verbose ([666c2903](https://github.com/flusio/Flus/commit/666c2903))

### Bug fixes

- Fix the strategy to sanitize URLs ([c41afda2](https://github.com/flusio/Flus/commit/c41afda2))
- Redirect unlogged users to login when posting messages ([84176df3](https://github.com/flusio/Flus/commit/84176df3))

### Misc

- Add credits to dependencies in the README ([a25277e6](https://github.com/flusio/Flus/commit/a25277e6))

## 2022-07-27 - v0.47

### Migration notes

A new `FEEDS_LINKS_KEEP_PERIOD` environment variable can be set to purge old
links from feeds. This allows to reduce the number of links in database. You’ll
have to set the variable in your `.env` file and restart your jobs workers.

### New

- Allow to share read and write access to collections ([2c20d924](https://github.com/flusio/Flus/commit/2c20d924) and [2516bb56](https://github.com/flusio/Flus/commit/2516bb56))
- Allow to purge old feeds links ([889754cd](https://github.com/flusio/Flus/commit/889754cd))

### Improvements

- Improve performance when getting news from followed collections ([f706d416](https://github.com/flusio/Flus/commit/f706d416))
- Improve performance when listing links ([e65d0237](https://github.com/flusio/Flus/commit/e65d0237))
- Allow to unset all collections of a link ([849bfa09](https://github.com/flusio/Flus/commit/849bfa09))
- Move the link visibility checkbox to the collections modal ([233acd0c](https://github.com/flusio/Flus/commit/233acd0c))
- Format numbers accordingly to the current locale ([9ad383b9](https://github.com/flusio/Flus/commit/9ad383b9))
- Add style to blockquotes and code in messages ([49b77190](https://github.com/flusio/Flus/commit/49b77190))
- Remove the trailing space in visibility badge ([0c3dbc01](https://github.com/flusio/Flus/commit/0c3dbc01))

### Bug fixes

- Handle errors in `Application::run` correctly ([a6aaa0c2](https://github.com/flusio/Flus/commit/a6aaa0c2))
- Fix changing collections when passing by a not owned link ([93b817ec](https://github.com/flusio/Flus/commit/93b817ec))
- Declare `links.search_index` as a normal property ([7532d61b](https://github.com/flusio/Flus/commit/7532d61b))
- Handle bulk methods with no values ([12d22824](https://github.com/flusio/Flus/commit/12d22824))
- Fix Node Dockerfile ([1af3d480](https://github.com/flusio/Flus/commit/1af3d480))

### Misc

- Add a command to clean the media files ([fec39a8c](https://github.com/flusio/Flus/commit/fec39a8c))
- Add a `.gitattributes` file ([2f1621ee](https://github.com/flusio/Flus/commit/2f1621ee))
- Fix documentation of the `Sorter` class ([fe387dea](https://github.com/flusio/Flus/commit/fe387dea))
- Reorganize the `env.sample` file ([0d08ef1d](https://github.com/flusio/Flus/commit/0d08ef1d))
- Fix link to Docker Engine installation guide ([26a4c007](https://github.com/flusio/Flus/commit/26a4c007))

## 2022-05-31 - v0.46

### Breaking changes

The minimal versions for PHP and PostgreSQL have changed: PHP 7.4 and
PostgreSQL 13. Please check the versions before updating flusio!

### News

- Enable search feature for all
- Allow Markdown in descriptions and messages

### Improvements

- Redesign the "no news" paragraph
- Improve performance of the Feeds page
- Sort searched links by created\_at

### Misc

- Change the minimal versions for PHP and PostgreSQL
- Add support for PHP 8.1
- Add Parsedown library to parse Markdown
- Update parcel to 2.6.0
- Upgrade eslint plugins

## 2022-05-23 - v0.45

### News

- Provide a form to search in "My links" (beta)
- Create an about page

### Security

- Upgrade minimist dependency

### Bug fixes

- Fix "from" URLs containing "&" characters in `links/_link.phtml` view partial

### Misc

- Remove back element from DOM instead of hidding

## 2022-02-24 - v0.44

### News

- Enable the new navigation for all users

### Improvements

- Improve the accessibility of popup menus
- Improve the accessibility of forms
- Announce to screen readers that links are opened in a new window
- Improve the accessibility of the "skip to main content" button
- Improve the alerts on importations/exportations
- Rework the onboarding
- Improve Atom feeds content
- Move the "Create collection" button on next row
- Append the brand to title for connected users as well
- Stop tracking all pages in the "back" history
- Disable the "forgot password" feature if demo is enabled

### Bug fixes

- Check CSRF first in Exportations#create
- Do not reset autoload\_modal on redirections

### Misc

- Reset users autoload\_modal for recent users
- Improve performance of the `system` command
- Provide a command to remove cache of an URL
- Remove the command to list subscriptions
- Provide a file GDPR.txt
- Update README.md

## 2022-02-10 - v0.43

### News

- Enable public profiles for all users
- Allow to get profile collections as OPML file

### Improvements

- Display via info in more contexts
- Allow to edit a link from profile
- Display user identity on account deletion
- Add a `https://` placeholder to "url" inputs
- Add style to the Atom feeds
- Examine responses with no content type

### Bug fixes

- Redirect to /links after collection deletion
- Don't copy link on links/Collections#index
- Redirect feed of feeds to original feed

### Misc

- Display jobs names and durations
- Alter some columns to use `BIGINT` type
- Upgrade Parcel to v2.3.0
- Ignore `src/views` in lint-fix
- Use the new subscriptions sync API

## 2022-01-28 - v0.42

### News

- Allow to init new users with default data (cf. production documentation)

### Improvements

- Display publication date on hover
- Add /feed URL alias to the feeds

### Bug fixes

- Remove duplicated links from profile page
- Break long words in comments
- Handle duplicated read links

### Misc

- Export time filters in OPML file
- Format exported XML files nicely
- Add a mechanism to mock HTTP requests during tests

## 2022-01-20 - v0.41

### News

- List last shared links on profile pages (beta)
- Add Atom feed to profiles (beta)

### Improvements

- Improve performance on "Mark all as read"
- Display info about the new navigation (beta)
- Redirect to the feed once added (beta)

### Misc

- Refactor and fix back navigation
- Support text/rss+xml content type
- Add support for PHP 8
- Reset the locale after each job operation

## 2022-01-15 - v0.40

### News

- Allow to remove link from the read list

### Improvements

- Improve performance on various collections pages
- Revert delaying groups loading on /collections

### Misc

- Refactor and clean a LOT of code

## 2022-01-14 - v0.39

### News

- Display an icon to distinguish read links
- Allow to mark a link as read on collections pages
- Allow to mark a followed collection as read
- Allow to obtain a link from a public collection
- Add public profile pages (beta)

### Security

- Improve security around the support user

### Improvements

- Change the icon of the action of adding a new link in collections
- Add a separator above the unfollow button
- Homogeneize the login modal look
- Display "new feed" errors in the modal (beta)
- Use "followed feeds" wording (beta)

### Bug fixes

- Allow blocked users to contact the support
- Fix some incorrect current\_tab for collections (beta)

### Misc

- Provide CLI to validate a user
- Update Stimulus and Turbo dependencies
- Update parcel to v2.2.0

## 2022-01-10 - v0.38

### News

- Reorganize the navigation (beta)

### Improvements

- Improve support for invalid OPMLs
- Improve the feed icon

### Bug fixes

- Fix support of unicode through the application
- Fix Twitter fetching

## 2021-11-23 - v0.37

### News

- Enable the time filters for everyone

### Improvements

- Decrease the space taken by the news buttons
- Lazy load links images
- Improve the UX of time filters
- Decrease the padding around the sections intro

## 2021-11-19 - v0.36

### News

- Allow to configure a time filter per collection (behind a feature flag)

### Improvements

- Improve popup items on big screens

### Bug fixes

- Fix performance issue when deleting links
- Fix a test of validation email
- Fix migrations create tests

### Misc

- Autoclean old invalidated users
- Autoclean old unused feeds and links
- Unlock jobs, links and collections if locked for more than an hour
- Update the `idx_links_fetched_code` index
- Allow to list subscriptions with CLI
- Provide a command to generate migration files
- Remove CLI users clean command

## 2021-11-05 - v0.35

### Improvements

- Improve performance when listing links from followed
- Delay groups loading on collections page
- Forbid ‘@’ character in usernames

### Misc

- Handle `x-rss+xml` mime type for RSS
- Close DB connections in jobs workers
- Update README.md
- Update index `links_fetched_at`
- Cache NPM on GitHub CI
- Improve performance of tests

## 2021-10-04 - v0.34

### Improvements

- Deduplicate feeds with same name on search page

### Bug fixes

- Fix URLs in OPML exportation
- Make parsing of feeds dates more robust
- Support `APP_PATH` correctly

### Misc

- Reorganize media files
- Add type `rss` to OPML export
- Store feeds type

## 2021-09-28 - v0.33

### News

- Allow users to export their data
- Provide CLI users' data exportation

### Improvements

- Change the wording of "New link" to "New"
- Change the wording of URL to address

### Bug fixes

- Fix redirection to Pocket

### Misc

- Limit download size in fetchers
- Enhance links and collections feeds
- Enhance feed parsers
- Provide a Docker psql command
- Execute docker bins in running containers if any
- Fix a Http test
- Alter version in development and test environments

## 2021-09-13 - v0.32

### News

- Remove links from news without marking as read

### Improvements

- Auto-resize text editors

### Bug fixes

- Fix use of `LinkToCollection` DAO in `FeedFetcher`

### Misc

- Upgrade JavaScript dependencies
- Migrate from Turbolinks to Turbo
- Upgrade to Stimulus 2.0.0
- Refactor modal with TurboFrame
- Drop `news_links` table

## 2021-09-09 - v0.31

**Important note:** this version comes with a bunch of tricky migrations. They
are expected to work well but, you know, a crash can be expected. For this
reason, it's recommended to stop the jobs workers and to put the application
in maintenance mode during the update.

### News

- Allow to list read links

### Improvements

- Handle different link publication dates

### Security

- Duplicate identical URLs for different feeds

### Misc

- Decrease rate limit for Youtube feeds
- Improve rate limit during feeds fetching
- Select links to fetch based on fetched\_code
- Sort jobs by ids in CLI

## 2021-08-27 - v0.30

### Migration notes

**Important change:** CLI commands interface has changed! You’ll need to update
the worker(s) service/cron command with the new format (i.e. `php cli jobs
watch [--queue=TEXT]`).

If you dedicates workers to queues, the [performance document](/docs/performance.md)
didn’t mentionned the `importators` queue previously. It’s now fixed and you
should start a worker dedicated to this queue as well.

### Improvements

- Improve performance of Youtube and feeds fetching
- Redesign the command line interface
- Provide CLI command to show system info and stats
- Add three indexes to database for performance

### Misc

- Refactor a whole bunch of code
- Mention importators queue in performance doc
- Improve handling of errors during feeds and links sync
- Fix output of migration and rollback commands
- Rename the LinksFetcher job
- Handle correctly empty responses with SpiderBits
- Fix some failing tests

## 2021-08-12 - v0.29

### Migration notes

You can now improve performance if you have a high number of feeds and links to
synchronize. Documentation has been updated with a new document titled “[How to
improve performance](/docs/performance.md)”.

### Improvements

- Improve performance of synchronization jobs

### Bug fixes

- Fix click on buttons with SVG on Safari
- Track `cache` folder with git
- Change `stopWatch` to be a public method

## 2021-08-04 - v0.28

### Improvements

- Improve look of error and success messages
- Make explicit the username is public
- Show email address when resending validation
- Add info importation can be long

### Security

- Reset sessions/reset token on password change
- Send X-Frame-Options header to deny embeding

### Bug fixes

- Don't decode `+` during URL sanitization
- Catch errors during collection image fetching

### Misc

- Revert Googlebot-compatibility by default
- Dump Pocket items on importation errors
- Fix tests

## 2021-06-02 - v0.27

### News

- Enable feeds for all!

### Improvements

- Improve accessibility for form captions
- Fix small accessibility issues

### Misc

- Add an accessibility item to PR checklist

## 2021-05-27 - v0.26

### News

- Add illustrations to collections
- Allow to group collections
- Import OPML groups
- Allow to copy the collection link

### Improvements

- Improve collections look
- Add OpenGraph image to public collections
- Add publication date on all links
- Indicate when a link is being fetched
- Add a follow button for unconnected users
- Make explicit collection name max length
- Improve handling of feeds with bad links

### Bug fixes

- Fix string length calculation on validations
- Update links if entry id exists on feed sync
- Set correct font-family on buttons

### Misc

- Add Googlebot-compatible to default user agent
- Update JS dependencies

## 2021-05-19 - v0.25

### News

- Allow to reset forgotten passwords

### Bug fixes

- Handle Atom feeds with no published date

### Misc

- Make sure users always have bookmarks
- Rename registration-form controller in csrf-loader

## 2021-05-14 - v0.24

### Migration notes

Now, topics can have illustrations. They appear on the new discovery page. You
can update existing topics with CLI and provide an `image_url` to set
illustrations.

### News

- Add discovery by topics
- Allow to contact the support

### Improvements

- Add anchors on news "via collections"
- Change "account" to "account & data" in header
- Improve style of popup items
- Add section--longbottom on various sections

### Bug fixes

- Order links messages by creation date
- Fix alignment of locale form icon
- Set correctly feed entry id for links to sync

### Misc

- Adapt pull request template
- Update Minz
- Update JS dependencies

## 2021-05-10 - v0.23

### News

- Replace news configuration by pre-selections

### Improvements

- Display publication date on news
- Improve look of the button to empty the news
- Remove points of interest
- Adapt onboarding to the latest changes
- List empty feeds on search page

### Misc

- Cache successful requests only
- Decrease Youtube rate limit to 1 request per minute
- Force IPv4 when fetching Youtube links
- Decrease number of links to fetch at once
- Remove duplicated info between `links` and `news_links`
- Replace links `feed_published_at` by `created_at`

## 2021-05-04 - v0.22

### Improvements

- Improve UX when adding a link to a collection (directly fetch the link +
  redirect to the collection)
- Ignore rate limits on search page
- Decrease rate limit for Youtube
- Always display feed website on search page
- Hide link if url is a feed URL on search page
- Hide comments on news via feed

### Bug fixes

- Order links by id when `created_at` are identical
- Redirect to paginated page on collections actions
- List only OPML/XML files when uploading OPML

### Misc

- Refresh links in error
- Dedicate a job to clean system data
- Fix data in dao Job test failing randomly
- Change the icon system (include SVG directly in the HTML)

## 2021-04-29 - v0.21

### Migration notes

The `data/` directory can now contain big files (OPML importations). You can
move it to a different location by setting the `APP_DATA_PATH` variable in your
`.env` file. **Make sure to move the `data/migrations_version.txt` file as
well!**

### News

- Provide OPML importation
- Add support for RDF feed (RSS 0.90)

### Improvements

- Adapt feeds look in news

### Bug fixes

- Fix order in feeds for existing links
- Fix a test in Job DAO
- Handle avatar upload with no files

### Misc

- Add rate limits on links and feeds fetching

## 2021-04-24 - v0.20

### Improvements

- Tell Chrome to don’t track users (WTF‽)
- Improve look of feeds cards on search page
- Move search button on desktop
- Don’t list empty feeds on search
- Add feeds autodiscovery for Youtube
- Adapt meta tags for feed collections
- Change the default-card.png file

### Bug fixes

- Don’t set link title if entry title is empty
- Handle feeds with no `feed_site_url` correctly

### Misc

- Increase HTTP timeouts
- Provide a scheduled job to clean the cache
- Add credits to the README

## 2021-04-22 - v0.19

### News

- Add support for syndication feeds (behind a feature flag)
- Replace new link page by a search page
- Autodiscover feeds on search page

### Improvements

- Improve look of "New link" anchor (header)
- Use absolute URLs for atom links

### Bug fixes

- Adapt the number of links on owned collections pages
- Make sure to initialize locale with the default one
- Accept CLI parameters containing `=` char
- Add html entities protection on URLs

### Misc

- Add flag system for experimental features
- Add CLI commands to list users and manipulate feeds
- Improve the look of CLI usage command
- Improve jobs priority
- Raise exceptions on Curl errors
- Change `UserLinksFetcher` to a global `LinksFetcher` scheduled job
- Move `CurrentUser` under `flusio\auth\` namespace
- Refactor resources access permissions
- Split and reorganize controllers
- Ignore specific lines with PHP linter

## 2021-03-25 - v0.18

### Migration notes

Jobs worker services can now be dedicated to queues.

If `APP_DEMO` is true, the reset of the application is now automatically done
via a scheduled job. The `make reset-demo` target is removed, you should remove
your cron task if you had one.

If you’ve set the subscription system, the sync cron task must be removed as
well (a scheduled job is running every 4 hours).

A new environment variable must be set in your `.env` file: `APP_SUPPORT_EMAIL`.
It’s used to create a default user. Make sure to set it to a user that doesn’t
exist since feeds are attached to it.

See production documentation for more information.

### News

- Provide an Atom feed for links comments and collections
- Add OpenGraph tags on public links/collections

### Improvements

- Display info about pricing during registration
- Facilitate password managers operations
- Keep order during Pocket importation
- Remove Pocket collections if empty
- Clarify the impact of Pocket tags importation

### Bug fixes

- Handle iso-8859-1 and bad encoding during link fetching
- Correctly initialize `created_at` on saving
- Fix account icon if subscriptions are off

### Misc

- Add scheduled jobs
- Add data seeds on system setup
- Add jobs queue
- Fix documentation about systemd service file
- Move controllers under their own folder
- Fix ubuntu version on CI
- Allow to set CLI default locale
- Load registration CSRF token with JavaScript
- Add device-width to HTML viewport tag
- Initialize a user to handle support
- Rename Fetch service in LinkFetcher
- Initialize `.env` file on `make start`
- Ignore more folders on make tree

## 2021-02-23 - v0.17

### Breaking changes

Validation emails are now sent asynchronously by a jobs worker. First of all,
you must make sure to have installed the `pcntl` PHP extension. Then, please
have a look to the production documentation to learn how to setup the worker.

If you want to setup the Pocket importation system, you’ll have to create a
Pocket "consumer key". More information in the production documentation.

### News

- Add Pocket importations
- Allow users to change their avatar
- Allow to directly mark bookmarks as read

### Improvements

- Fetch actual content of Twitter pages
- Reword links "Show publicly" option in "Hide in public collections"
- Add pagination on collection page
- Add `aria-current="page"` on concerned anchors
- Remove icons of collections titles

### Bug fixes

- Fix the infinite redirection when discovering page was empty
- Fix username length issues
- Create validation token on "resend email" action if the token is missing

### Misc

- Setup an async jobs system
- Provide a DaoConnector trait and refactor code
- Extract the routes into a dedicated class
- Set docker-compose project name to `flusio`
- Update the README
- Reorganise technical documentation

## 2021-01-27 - v0.16

A new batch of fixes and improvements to deploy in production today!

### News

- Allow to delete messages
- Provide pagination on discovering page

### Improvements

- Display origin of news links
- Change wording for accepting terms of service
- Clarify important emails on registration
- Improve integration on various platforms
- Improve account nav menu
- Increase modal margin bottom on mobile
- Change default title font-family to sans-serif
- Add spacing under `.news__postpone`
- Remove some autofocus
- Lighten the layout border color
- Homogeneize card-action border width with footer

### Bug fixes

- Fix link to continue on step 4 of onboarding
- Fix modal undefined content
- Fix checkbox shrinking on mobile
- Fix section image on mobile
- Fix header background for Firefox on mobile

## 2021-01-22 - v0.15

This release brings mainly a lot of UI/UX improvements.

### Improvements

- Improve overall layout structure
    - Reorganize create/edit/delete buttons
    - Remove cancel actions from forms
    - Change body background
    - Add a border around content
- Improve links UX
    - Change link main action from "see" to "read"
    - Move actions from link show page to collection cards
    - Remove quick unbookmark button in cards
    - Simplify link show page
    - Remove sharing page
- Add links to web extension stores
- Remove shadow card from discover and public lists
- Hide "remove from news definitively" option
- Change card footer from turquoise to purple
- Change the green color in collections illustration to turquoise
- Create subscription accounts on Cron sync

### Misc

- Add service param in subscription login request
- Fix a SpiderBits test
- Fix the GitHub funding link
- Bump ParcelJS version
- Bump JS ini version to 1.3.8

## 2020-12-11 - v0.14

A small release for the “grand opening”!

### Improvements

- Provide better integration for browser extension

### Misc

- Bump ParcelJS version
- Increase default HTTP timeout
- Add log on subscriptions sync

## 2020-10-30 - v0.13

### News

- Allow to mark a single link as read

### Improvements

- Pick public news links in private collections
- Display a default card image for links with no image
- Move news preferences in a modal
- Improve readability of fieldsets
- Increase the quantity of blue in grey color
- Change cards titles to display block
- Add autofocus on security confirm password
- Add a pop-out icon on the subscription anchor
- Reword collections index section intro
- Improve details of the link fetching page
- Put "Show publicly" always at the end of forms
- Reword default option to select collections
- Change the color of selected collections
- Add a confirmation on "mark all as read"
- Add a bit of colors to cards footers
- Animate slowly the "body after" bar

### Bug fixes

- Fix popup menu position on mobile

## 2020-10-28 - v0.12

### Migration notes

Make sure that the GD PHP extension is installed with support of PNG, JPEG and
WEBP images.

You might need to reset some ids due to the bug fixed by [`df29d41`](https://github.com/flusio/Flus/commit/df29d413260cffa2d9f433139891c574ddcb504f).

(optional) flusio now supports Open Graph and Twitter Cards images. For oldest
links, you can refresh their image by running the following command:

```console
flusio$ # where NUMBER should be replaced by a positive integer value (default is 10)
flusio$ php cli --request /links/refresh -pnumber=NUMBER
```

### New

- Display Open Graph images on links
- Add batch actions at the bottom of news

### Improvements

- Improve the UX of news
- Improve unbookmark UX
- Save and redirect at step 4 of onboarding
- Improve visibility of `.popup__item` on hover
- Add light gradient backgrounds
- Change cursor on button hover to pointer
- Reduce line-height of cards titles
- Reorganize commands in CLI usage action

### Bug fixes

- Fix id on Link::initFromNews

### Misc

- Don't hide card--shadow on mobile per default
- Add French sync to PR template
- Add PHP gd extension requirement

## 2020-10-21 - v0.11

### Breaking changes

The ids of collections and links are changing to a numeric form, which means
previous URLs will break. This is not a change that I would do if the service
was open or installed by other people, at least not in a >= 1.0 release. Since
I'm almost the only person using it today and that I shared very few URLs, it’s
OK for me to do it. It's also the last occasion to make this change (or it
would require more work).

### Migration notes

(optional, mostly for myself) You can configure the subscription feature with
the `APP_SUBSCRIPTIONS_*` environment variables. Read production documentation
for more details.

### New

- Provide a subscription feature (monetization)
- Allow users to update login credentials
- Add a “terms of service” mecanism
- Block not validated users after 1 day

### Improvements

- Make explicit that JavaScript is required
- Change links and collections ids format (decimal instead of hexadecimal)
- Reorganize the "avatar" menu
- Reduce width of registration/login sections
- Improve extraction of websites title (again!)
- Hide back anchors on public page if no back URL
- Center submit button on profile page
- Homogeneize titles case
- Add a light linear gradient on popup menus
- Fix height of inputs

### Bug fixes

- Set cookies `SameSite` to `Lax`
- Generate user CSRF token directly instead of calling `Minz\CSRF`

### Misc

- Update Minz version
- Fix the locale in User factory
- Improve SpiderBits\Http get method
- Rename `format_date` to `format_message_date` and create a more generic
  `format_date()` function

## 2020-10-01 - v0.10

### Migration notes

(optional) You can change your instance brand name by setting `APP_BRAND` in
your `.env` file.

### New

- Allow to configure news
- Provide onboarding
- Allow to change the brand name

### Improvements

- Consider the OpenGraph and Twitter titles
- Redirect intelligently on link deletion
- Improve the news tips section
- Reword options to remove news
- Increase the topic label max size

### Bug fixes

- Don't select link in owned collection for news
- Hide "add to collections" if user has no collections
- Fix select width with long options on mobile
- Fix padding for header locale form

### Misc

- Add `[devmode]` in page title in development
- Fix break line in cards details
- Refactor listing with `human_implode` in News
- Fix NewsPicker duration test
- Fix a test to be sure to generate unique URLs

## 2020-09-24 - v0.9

### Migration notes

(optional) You can now create topics. Topics are attached to collections in
order to categorize them. Topics are created by the administrator with the CLI:

```console
flusio# php ./cli --request /topics/create -plabel=LABEL
```

### New

- Provide topics for collections
- Allow users to set their points of interest
- Get news suggestions from points of interest
- Provide public collections discovery
- Allow to delete a link

### Improvements

- Change default avatar
- Display if collection/link is public
- Improve the link and collection settings menus
- Add a card shadow to complete blocks of 3
- Add a light color on card:focus-within
- Add placeholder on public links without comments
- Display owner of followed collections
- Improve tips when there are no news
- Change links to close modals to buttons
- Improve `section__nav` margin on mobile
- Fix wording for private collection back button
- Go to previous page from public collection

### Bug fixes

- Refactor and fix "back" anchor on link pages
- Fix `<title>` for collections pages
- Fix cards design
- Hide titles overflow
- Return 404 if deleting non existing collection

### Misc

- Add support for rollbacks
- Update Parcel to beta-1
- Add support for serial ids in SaveHelper
- Add a test on cli usage command

## 2020-09-04 - v0.8

### Security

- Forbid access to not owned collections

### New

- Allow to create public collections
- Allow to follow collections
- Provide tips if there are no news to suggest
- Allow to permanently hide a news

### Improvements

- Allow to set link public during creation/edit
- Improve the process to add news to collections
- Move a bunch of actions in modals
- Improve the collections selector
- Improve the look of checkboxes
- Move public checkboxes at the end of forms
- Add a link to skip to the main content
- Add an anchor to go back from links add page
- Redirect directly to link page after fetch
- Add autofocus on a bunch of inputs
- Improve the look of navbar on mobile
- Change the news icon
- Put primary buttons on the right
- Add back anchor on public pages
- Add a light background to cards footers
- Fix links "collections" button padding
- Add few illustrations
- Improve and homogeneize wording

### Bug fixes

- Fix sanitization of HTML `<title>`
- Fix cards overflow
- Fix scrolling to top on Firefox
- Save backForLink on turbolinks:visit
- Fix margins of `.card__details`
- Hide marker of `.popup__opener` on Chrome

### Misc

- Extract a CSS card component
- Provide a modal mechanism
- Migrate the news system to a dedicated model
- Update French locales
- Change `include_once` by `include` for JS configuration
- Bump bl from 4.0.2 to 4.0.3

## 2020-08-21 - v0.7

### New

- Provide a basic system to read the "News"
- Provide a back anchor on link page

### Improvements

- Show link title on edit and collections
- Make a bunch of small design adjustments

### Misc

- Return l10n key if value doesn't exist (JS)

## 2020-08-06 - v0.6

### New

- Allow to comment links
- Allow to share public links

### Improvements

- Improve the (un)bookmark button
- Add the logo to the "not connected" header
- Improve look of buttons
- Set session cookie on registration
- Add anchors on cards titles
- Add a title on "manage" link collections

### Misc

- Update French locales
- Update the PR template
- Complete doc about release and update
- Split Links controller file
- Bump elliptic from 6.5.2 to 6.5.3
- Bump lodash from 4.17.15 to 4.17.19

## 2020-07-17 - v0.5

### Improvements

- Always show anchor to add links to collection
- Rename new collection description label
- Reduce time before fetching link
- Hide "www." from the links hosts
- Change "about" link to flus.fr

### Bug fixes

- Fix header UI details
- Fix bookmarks name localization
- Fix title scrapping for Youtube

### Misc

- Parse title only for HTML pages
- Update French locales

## 2020-07-16 - v0.4

### New

- Allow creation/edition/deletion of collections
- Provide a dedicated page to add links
- Manage collections from the link page
- Add support for mobiles

### Improvements

- Rework header bar
- Change style of hr tag
- Set Turbolinks progress bar style
- Move "edit/settings" anchor on link show page

### Bug fixes

- Set correct version in SpiderBits user agent

### Misc

- Update French locales
- Update icons
- Force CSRF token for connected users
- Homogeneize routes and controller actions names
- Provide a FakerHelper class for tests

## 2020-07-01 - v0.3

### Migration notes

If your instance is a demo, you should change the cron task which reset the
data by `make reset-demo NO_DOCKER=true`. This will reset the database and
create a demo user.

(optional) You can close registrations on your instance by setting the
`APP_OPEN_REGISTRATIONS` environment variable to `false`.

### New

- Allow to close registrations
- Allow to create users via the CLI

### Improvements

- Improve reading time calculation by removing script tags from dom content
- Decrease margins to put more content on small screens
- Add few illustrations
- Create a demo account during demo reset
- Select default locale based on http Accept-Language header
- Improve the look of success message when an account is deleted

### Bug fixes

- Fix title encoding on some websites
- Fix sessions lifetime by initializing the session from a custom cookie

### Misc

- Update French l10n

## 2020-06-26 - v0.2

### Migration notes

(optional) The responses from links fetching are now cached during one day. The
`APP_CACHE_PATH` env variable can be set in order to change the location of
cached responses (default is the flusio `cache/` folder).

### Improvements

- Puny-decode links host
- Don't lowercase links title
- Show a spinner animation during link fetching

### Misc

- Fix "reseted" typo
- Cache response during links fetching

## 2020-06-25 - v0.1

First version
