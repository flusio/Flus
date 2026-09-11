# Changelog of Flus

## unreleased

### Migration notes

The file to import default "read later" links has been renamed: `data/default-bookmarks.atom.xml` becomes `data/default-read-later.atom.xml`.

The `/bookmarks` page is now served at `/read/later`.
The `/news` page is now served at `/journal`.
The old URLs redirect permanently.

In the archives exported by the users, `bookmarks.atom.xml` is renamed to `read-later.atom.xml` and `news.atom.xml` is renamed to `journal.atom.xml`.

Reminder: **a migration must have been applied manually when you upgraded to Flus 2.5.0.**
If you didn't yet, please run **before upgrading to Flus 3.0.0:**

```console
flus# sudo -u www-data php cli migrations setup-url-statuses
```

This command takes two optional parameters:

- `--batch-size=INT` to change the size of the batches (1000 by default)
- `--dry-run` to not apply the migration immediately, but to get how many data will be migrated.

## 2026-08-19 - v2.5.4

### Developers

- Add ArrayHelper::find and ArrayHelper::any methods ([8f90862d](https://github.com/flusio/Flus/commit/8f90862d))
- Extract a "scroller" Twig component ([2cbab88d](https://github.com/flusio/Flus/commit/2cbab88d))
- Provide a "sticky" controller to apply CSS on stuck elements ([99a5706c](https://github.com/flusio/Flus/commit/99a5706c))
- Provide a "selection" Stimulus controller ([f4d35ca2](https://github.com/flusio/Flus/commit/f4d35ca2), [32a9024b](https://github.com/flusio/Flus/commit/32a9024b))
- Accept `attrs` on popup macros and `form` on popup actions ([53828d6e](https://github.com/flusio/Flus/commit/53828d6e))
- Handle `aria-current` selected items in scroller ([8fd21e12](https://github.com/flusio/Flus/commit/8fd21e12))
- Handle more cases in the "focus" Stimulus controller ([66eed5e4](https://github.com/flusio/Flus/commit/66eed5e4))
- Limit the height of popups ([bd025e38](https://github.com/flusio/Flus/commit/bd025e38))


### Alpha: streams

- Allow to save stream filters as views ([5733d53e](https://github.com/flusio/Flus/commit/5733d53e))
- Add bulk actions to sources ([97d0aa82](https://github.com/flusio/Flus/commit/97d0aa82))
- Don't suggest the "all" filter for news ([7265f7b2](https://github.com/flusio/Flus/commit/7265f7b2))
- Add links' publication hour in streams ([72b4fce7](https://github.com/flusio/Flus/commit/72b4fce7))
- Display news info and stream count in collection ([2c57932e](https://github.com/flusio/Flus/commit/2c57932e))
- Improve the source information display on small screens ([074cffa9](https://github.com/flusio/Flus/commit/074cffa9))
- Improve look coherence of links on mobile ([38bf3044](https://github.com/flusio/Flus/commit/38bf3044))
- Fix look of source selector on small screens ([4c085a45](https://github.com/flusio/Flus/commit/4c085a45))
- Reduce filters spacing on mobile ([27ee0bdc](https://github.com/flusio/Flus/commit/27ee0bdc))

## 2026-08-07 - v2.5.3

### Features

- Rename "Remove from news" into "Ignore the link" ([ff6f6e08](https://github.com/flusio/Flus/commit/ff6f6e08))

### Bug fixes

- Fix the Altcha CSP headers ([2087bd15](https://github.com/flusio/Flus/commit/2087bd15))
- Display origin's collection owner ([0f83ed42](https://github.com/flusio/Flus/commit/0f83ed42))
- Fix repairing links in read and read later lists ([826dd5f3](https://github.com/flusio/Flus/commit/826dd5f3))
- Handle invalid query when searching in links ([c4cac480](https://github.com/flusio/Flus/commit/c4cac480))
- Fix main column overflowing on medium screens ([7e9547ba](https://github.com/flusio/Flus/commit/7e9547ba))
- Fix zindex on group headers properly ([80d4c9d9](https://github.com/flusio/Flus/commit/80d4c9d9))

### Maintenance

- Refactor the Link model and preload data ([0d804719](https://github.com/flusio/Flus/commit/0d804719), [f03bcabb](https://github.com/flusio/Flus/commit/f03bcabb), [918b22db](https://github.com/flusio/Flus/commit/918b22db), [b883f29e](https://github.com/flusio/Flus/commit/b883f29e), [9741474c](https://github.com/flusio/Flus/commit/9741474c))
- Refactor collections loading with a Preloader ([68fd6178](https://github.com/flusio/Flus/commit/68fd6178))
- Add an index on `links_to_collections(collection_id, created_at)` ([df72f1a6](https://github.com/flusio/Flus/commit/df72f1a6))
- Stop accumulating modal focus events during navigation ([2e98707e](https://github.com/flusio/Flus/commit/2e98707e))
- Refactor the popups ([cb94fff9](https://github.com/flusio/Flus/commit/cb94fff9), [57adcbf2](https://github.com/flusio/Flus/commit/57adcbf2))
- Memoize isBetaEnabled and isAlphaEnabled methods ([f3c1c511](https://github.com/flusio/Flus/commit/f3c1c511))
- Extract a details CSS component ([105553d6](https://github.com/flusio/Flus/commit/105553d6))
- Update the dependencies ([039153ea](https://github.com/flusio/Flus/commit/039153ea), [4df92d32](https://github.com/flusio/Flus/commit/4df92d32), [d20557b5](https://github.com/flusio/Flus/commit/d20557b5))

### Alpha: streams

- Provide a new "sources" page to replace "feeds" ([21f35a67](https://github.com/flusio/Flus/commit/21f35a67))
- Display links as rows ([7f2dc9a6](https://github.com/flusio/Flus/commit/7f2dc9a6))
- Manage streams from the followed source ([8b6d0894](https://github.com/flusio/Flus/commit/8b6d0894)), [6c449daf](https://github.com/flusio/Flus/commit/6c449daf))
- Add an unread dot to streams in navigation ([89624ce3](https://github.com/flusio/Flus/commit/89624ce3)), [7e859633](https://github.com/flusio/Flus/commit/7e859633))
- Allow to filter links with text queries ([be2a2ef0](https://github.com/flusio/Flus/commit/be2a2ef0))
- Hide dismissed links by default ([04a2cda3](https://github.com/flusio/Flus/commit/04a2cda3))
- Allow to ignore link individually in streams ([dbe96b84](https://github.com/flusio/Flus/commit/dbe96b84))
- Refactor and improve the filter component ([3a83f931](https://github.com/flusio/Flus/commit/3a83f931))
- Rework the interface of stream sources ([92d9fa15](https://github.com/flusio/Flus/commit/92d9fa15), [979c8ef5](https://github.com/flusio/Flus/commit/979c8ef5))
- Allow to add a source by a feed URL ([ddd7a781](https://github.com/flusio/Flus/commit/ddd7a781))
- Add a page to list sources of a stream ([0c902107](https://github.com/flusio/Flus/commit/0c902107))
- Display number of streams per sources ([ba92fdf7](https://github.com/flusio/Flus/commit/ba92fdf7))
- Add scroll buttons on the day-picker ([d07dd85b](https://github.com/flusio/Flus/commit/d07dd85b))
- Put statuses filter above the sources filter ([c9821f8e](https://github.com/flusio/Flus/commit/c9821f8e))
- Make source images clickable ([6a98baa1](https://github.com/flusio/Flus/commit/6a98baa1))
- Improve spacing and UX of the stream filters ([339f547c](https://github.com/flusio/Flus/commit/339f547c))
- Homogeneize the source filters ([8f303140](https://github.com/flusio/Flus/commit/8f303140))
- Improve the stream filters ([4061738e](https://github.com/flusio/Flus/commit/4061738e))
- Fix rendering the modal after applying a stream filter ([a5905dbe](https://github.com/flusio/Flus/commit/a5905dbe))
- Fix position of global actions' popup on mobile ([4ec94457](https://github.com/flusio/Flus/commit/4ec94457))
- Set the source correctly when marking link as read ([9d89da53](https://github.com/flusio/Flus/commit/9d89da53))
- Fix performances when editing stream sources ([e60c99e3](https://github.com/flusio/Flus/commit/e60c99e3))
- Optimize the performance when loading streams ([56cbc033](https://github.com/flusio/Flus/commit/56cbc033))
- Optimize the SQL request for links in streams ([a680779a](https://github.com/flusio/Flus/commit/a680779a))
- Avoid useless SQL scans to list user's streams ([c8077d56](https://github.com/flusio/Flus/commit/c8077d56))
- Extract a generic "filter" Stimulus controller ([ea7461c3](https://github.com/flusio/Flus/commit/ea7461c3))
- Allow to remember details state in local storage ([8f87dcc8](https://github.com/flusio/Flus/commit/8f87dcc8))
- Allow to remember "self" element in focus controller ([6b6bf2ce](https://github.com/flusio/Flus/commit/6b6bf2ce))
- Fade out scroller buttons nicely ([84361df9](https://github.com/flusio/Flus/commit/84361df9))
- Make scrolling sides more visible ([7d800236](https://github.com/flusio/Flus/commit/7d800236))
- Handle vertical scrolling in scroller controller ([d517ab7e](https://github.com/flusio/Flus/commit/d517ab7e))
- Don't force flex display on `scroller__strip` ([e9a363ff](https://github.com/flusio/Flus/commit/e9a363ff))
- Extract a JS controller to preserve attributes through morphing ([42251d6f](https://github.com/flusio/Flus/commit/42251d6f))
- Handle morphing on modal redirection ([bdf6e09d](https://github.com/flusio/Flus/commit/bdf6e09d))
- Handle morphing with the "back" button correctly ([6cf0b3da](https://github.com/flusio/Flus/commit/6cf0b3da))

## 2026-07-24 - v2.5.2

### Migration notes

This version fixes an XSS issue which was mitigated by CSP headers.
Update is recommended.

### Security

- Sanitize links and media URLs in the HtmlSanitizer ([c38209b5](https://github.com/flusio/Flus/commit/c38209b5))

### Bug fixes

- Fix offset of the first sidenav element on big screens ([68e1a995](https://github.com/flusio/Flus/commit/68e1a995))
- Adapt the width of the sidenav ([a019cabd](https://github.com/flusio/Flus/commit/a019cabd))
- Fix issues with long menu navigation labels ([108492dc](https://github.com/flusio/Flus/commit/108492dc), [33ca6001](https://github.com/flusio/Flus/commit/33ca6001))
- Decrease contrast of active sidenav element ([801797b6](https://github.com/flusio/Flus/commit/801797b6))
- Fix grouping links by dates ([5de22461](https://github.com/flusio/Flus/commit/5de22461))
- Forget URL status when deleting a link ([64c56402](https://github.com/flusio/Flus/commit/64c56402))

### Maintenance

- Change the source of read/read later/dismissed links ([39e1776f](https://github.com/flusio/Flus/commit/39e1776f), [70b933f4](https://github.com/flusio/Flus/commit/70b933f4))
- Apply bulk DB operations by chunks ([37dc4ac7](https://github.com/flusio/Flus/commit/37dc4ac7))
- Update the dependencies ([a6750539](https://github.com/flusio/Flus/commit/a6750539))
- Remove useless autosubmit on header locale form ([5618895a](https://github.com/flusio/Flus/commit/5618895a))

### Alpha

- Provide the initial "Stream" feature in alpha ([a255df50](https://github.com/flusio/Flus/commit/a255df50), [61793ea0](https://github.com/flusio/Flus/commit/61793ea0), [b8e8332f](https://github.com/flusio/Flus/commit/b8e8332f), [675815d6](https://github.com/flusio/Flus/commit/675815d6), [5f4993a3](https://github.com/flusio/Flus/commit/5f4993a3), [ff2f4810](https://github.com/flusio/Flus/commit/ff2f4810), [4e6ba6ee](https://github.com/flusio/Flus/commit/4e6ba6ee), [8a8f0291](https://github.com/flusio/Flus/commit/8a8f0291), [196e5009](https://github.com/flusio/Flus/commit/196e5009), [37283a6c](https://github.com/flusio/Flus/commit/37283a6c), [a01d96a0](https://github.com/flusio/Flus/commit/a01d96a0), [793a265d](https://github.com/flusio/Flus/commit/793a265d), [212a9abc](https://github.com/flusio/Flus/commit/212a9abc), [7c014fcb](https://github.com/flusio/Flus/commit/7c014fcb))

## 2026-06-23 - v2.5.1

### Maintenance

- Fix migration AddOriginToLinks to handle cases where `source_resource_id` is null ([aab2b7ed](https://github.com/flusio/Flus/commit/aab2b7ed))
- Improve the performance of the setupUrlStatuses migration ([f7a73a73](https://github.com/flusio/Flus/commit/f7a73a73))

## 2026-06-23 - v2.5.0

### Migration notes

You can enable Altcha to protect your registration page.
Set the new `APP_REGISTRATION_CAPTCHA` environment variable to `true`.

A migration isn't applied automatically but is optional to run this specific version of Flus.
**It will be required in an upcoming patch version, so it must be applied now.**
To apply this migration, run:

```console
flus# sudo -u www-data php cli migrations setup-url-statuses
```

This command takes two optional parameters:

- `--batch-size=INT` to change the size of the batches (1000 by default)
- `--dry-run` to not apply the migration immediately, but to get how many data will be migrated.

This command can take a lot of time to execute with a lot of data, so be patient.

### Features

- Enable interface customisation for everyone ([ea31089d](https://github.com/flusio/Flus/commit/ea31089d))
- Allow to change font family to "system-ui" ([165fe96e](https://github.com/flusio/Flus/commit/165fe96e))
- Allow to change and publish the link origin ([5f3994c9](https://github.com/flusio/Flus/commit/5f3994c9), [bb501d73](https://github.com/flusio/Flus/commit/bb501d73), [685855bd](https://github.com/flusio/Flus/commit/685855bd), [d64d679d](https://github.com/flusio/Flus/commit/d64d679d), [29b02432](https://github.com/flusio/Flus/commit/29b02432), [3a5adece](https://github.com/flusio/Flus/commit/3a5adece), [f51f56de](https://github.com/flusio/Flus/commit/f51f56de), [69c8f041](https://github.com/flusio/Flus/commit/69c8f041), [3ea1dd65](https://github.com/flusio/Flus/commit/3ea1dd65))
- Allow to mark a link as accessible ([5f091a97](https://github.com/flusio/Flus/commit/5f091a97))
- Allow to change the links' illustrations ([3bdbd8f3](https://github.com/flusio/Flus/commit/3bdbd8f3))
- Improve duration detection using JSON+LD ([6728cb3d](https://github.com/flusio/Flus/commit/6728cb3d), [53596613](https://github.com/flusio/Flus/commit/53596613))
- Allow to enable Altcha on the registration page ([afa530fc](https://github.com/flusio/Flus/commit/afa530fc), [01882da1](https://github.com/flusio/Flus/commit/01882da1))

### Improvements

- Remove the "accept contact" checkbox from the registration page ([c580245d](https://github.com/flusio/Flus/commit/c580245d))

### Bug fixes

- Don't duplicate link when repairing with the same URL ([32fc2a22](https://github.com/flusio/Flus/commit/32fc2a22))
- Fix avatars' look in dark mode ([7786de8d](https://github.com/flusio/Flus/commit/7786de8d))
- Fix the Mastodon "new message" zone in dark mode ([6a0d832e](https://github.com/flusio/Flus/commit/6a0d832e))
- Fix look of collections if stored by someone else ([eb2ecd19](https://github.com/flusio/Flus/commit/eb2ecd19))

### Maintenance

- Add official support for PHP 8.5 ([a9cd3427](https://github.com/flusio/Flus/commit/a9cd3427))
- Update the dependencies ([f907dd30](https://github.com/flusio/Flus/commit/f907dd30), [50e2ab30](https://github.com/flusio/Flus/commit/50e2ab30), [c8051bd3](https://github.com/flusio/Flus/commit/c8051bd3))

### Developers

- Handle read statuses through a `UrlStatus` model ([4e9c9eaa](https://github.com/flusio/Flus/commit/4e9c9eaa), [4bea9841](https://github.com/flusio/Flus/commit/4bea9841), [6d732b12](https://github.com/flusio/Flus/commit/6d732b12), [c06c379e](https://github.com/flusio/Flus/commit/c06c379e), [e4873a8c](https://github.com/flusio/Flus/commit/e4873a8c))
- Drop the support user ([ed80c14d](https://github.com/flusio/Flus/commit/ed80c14d))
- Clean code related to checkboxes ([fa84db8b](https://github.com/flusio/Flus/commit/fa84db8b))
- Accept callable in `Sorter::localeSort` ([3c0276cb](https://github.com/flusio/Flus/commit/3c0276cb))
- Move `hashUrl` to the `Belt` class ([909beb50](https://github.com/flusio/Flus/commit/909beb50))

## 2026-04-22 - v2.4.0

### Features

- Allow to filter links by users' tags ([d6e3e65a](https://github.com/flusio/Flus/commit/d6e3e65a), [48b502a5](https://github.com/flusio/Flus/commit/48b502a5), [b4344ceb](https://github.com/flusio/Flus/commit/b4344ceb), [7e243a21](https://github.com/flusio/Flus/commit/7e243a21))
- Allow to fill a profile's biography ([e5d60d6f](https://github.com/flusio/Flus/commit/e5d60d6f), [048beacb](https://github.com/flusio/Flus/commit/048beacb))
- Show links and collections distinctly on profiles ([f6d5f319](https://github.com/flusio/Flus/commit/f6d5f319))
- Add more "copy" links ([61f86105](https://github.com/flusio/Flus/commit/61f86105))
- Delete users not validated after a month ([5c9df5ef](https://github.com/flusio/Flus/commit/5c9df5ef))

### Improvements

- Clarify users can search by #tags ([7e5e6a10](https://github.com/flusio/Flus/commit/7e5e6a10))
- Increase space between preferences form elements ([c05bd508](https://github.com/flusio/Flus/commit/c05bd508))
- Improve explanation of the contact form ([84e6bdf3](https://github.com/flusio/Flus/commit/84e6bdf3))

### Bug fixes

- Fix page titles escaped too many times ([c77029ac](https://github.com/flusio/Flus/commit/c77029ac))
- Fix setting the link "via" information when adding a link from a collection or a profile ([f215e8cb](https://github.com/flusio/Flus/commit/f215e8cb))
- Check that Mastodon account is setup when checking user authorization ([a8d92a73](https://github.com/flusio/Flus/commit/a8d92a73))
- Fix the rendering of the forbidden page ([25e8ce44](https://github.com/flusio/Flus/commit/25e8ce44))

### Maintenance

- Update Docker image to PHP 8.4 ([2717265a](https://github.com/flusio/Flus/commit/2717265a))
- Update the dependencies ([45a308c6](https://github.com/flusio/Flus/commit/45a308c6), [abfc3a73](https://github.com/flusio/Flus/commit/abfc3a73))
- Update the ClearUrls rules ([5ca4217d](https://github.com/flusio/Flus/commit/5ca4217d))
- Set a unique tmp path per Flus version ([59a5b8b7](https://github.com/flusio/Flus/commit/59a5b8b7))

### Beta

- Allow to select interface theme (aka dark mode) ([5bde3dc6](https://github.com/flusio/Flus/commit/5bde3dc6))
- Allow to choose the text size of the application ([9ef77671](https://github.com/flusio/Flus/commit/9ef77671), [8ae1b083](https://github.com/flusio/Flus/commit/8ae1b083))
- Fix pagination when filtering by topic in the "Explore" section ([15f8fb4a](https://github.com/flusio/Flus/commit/15f8fb4a))

## 2026-03-13 - v2.3.1

### Improvements

- Increase the default font size ([726ab273](https://github.com/flusio/Flus/commit/726ab273))
- Center content even if the sidenav is present ([c80251de](https://github.com/flusio/Flus/commit/c80251de))
- Add a light transparent outline around illustrations ([ed90a1a7](https://github.com/flusio/Flus/commit/ed90a1a7))
- Increase space above page illustrations ([d5391a28](https://github.com/flusio/Flus/commit/d5391a28))
- Increase avatar size to 400x400px ([46da718d](https://github.com/flusio/Flus/commit/46da718d))
- Increase space below navigation close button ([f0740af3](https://github.com/flusio/Flus/commit/f0740af3))

### Bug fixes

- Use the correct icon to download data ([0daf6370](https://github.com/flusio/Flus/commit/0daf6370))
- Fix accessibility button border when hovering header ([6bbf9182](https://github.com/flusio/Flus/commit/6bbf9182))

### Maintenance

- Rename `navigation` block into `layout_sidenav` ([13938b7e](https://github.com/flusio/Flus/commit/13938b7e))
- Use relative widths ([a098aa98](https://github.com/flusio/Flus/commit/a098aa98))
- Update @flus/design ([726ab273](https://github.com/flusio/Flus/commit/726ab273))

## 2026-03-11 - v2.3.0

### Migration notes

You can remove the media "cards" and "large" folders as these images are no longer used.
Please make sure **to keep the "avatars" and "covers" folders.**
The folders are located either under public/media, or under the folder set by the `APP_MEDIA_PATH` environment variable.

### Features

- Provide the new design based on @flus/design ([2b2a34ef](https://github.com/flusio/Flus/commit/2b2a34ef))
- Rename "Bookmarks" in "To read" ([15b80984](https://github.com/flusio/Flus/commit/15b80984), [62ddb720](https://github.com/flusio/Flus/commit/62ddb720))
- Improve form feedbacks on error and successful actions ([a2602943](https://github.com/flusio/Flus/commit/a2602943))
- Display total number of elements in paginations ([0b04d186](https://github.com/flusio/Flus/commit/0b04d186))
- Add more actions on the notepad page ([14e6d992](https://github.com/flusio/Flus/commit/14e6d992))
- Add style to the tags in notepads ([99b8ca12](https://github.com/flusio/Flus/commit/99b8ca12))
- Improve editing avatar ([dd856473](https://github.com/flusio/Flus/commit/dd856473))
- Improve the text explaining the demo version ([66656015](https://github.com/flusio/Flus/commit/66656015))
- Put the explore/discovery menu item behind the beta flag ([bc7ed80e](https://github.com/flusio/Flus/commit/bc7ed80e))

### Bug fixes

- Display host and reading time while link is syncing ([e9dea67e](https://github.com/flusio/Flus/commit/e9dea67e))

### Maintenance

- Migrate PHTML files to Twig and the new design system ([a98d2307](https://github.com/flusio/Flus/commit/a98d2307))
- Update the icons to Lucide icons ([779ce043](https://github.com/flusio/Flus/commit/779ce043))
- Update the dependencies ([bc16e6d4](https://github.com/flusio/Flus/commit/bc16e6d4), [0667b293](https://github.com/flusio/Flus/commit/0667b293), [3797fa9a](https://github.com/flusio/Flus/commit/3797fa9a), [5b2b06b8](https://github.com/flusio/Flus/commit/5b2b06b8), [4f4fbf4e](https://github.com/flusio/Flus/commit/4f4fbf4e))
- Remove the `Permissions-Policy` HTTP header ([7cd6fa0d](https://github.com/flusio/Flus/commit/7cd6fa0d))
- Remove the `links.to_be_fetched` unused column ([3d82906b](https://github.com/flusio/Flus/commit/3d82906b))
- Remove support of "card" and "large" images ([52c585ce](https://github.com/flusio/Flus/commit/52c585ce))
- Remove the topic illustrations ([4ef68778](https://github.com/flusio/Flus/commit/4ef68778))

### Documentation

- Update the documentation ([b81b2ae8](https://github.com/flusio/Flus/commit/b81b2ae8))
- Update the screenshots ([414fba50](https://github.com/flusio/Flus/commit/414fba50))
- Fix the credits ([56775a6b](https://github.com/flusio/Flus/commit/56775a6b))

## 2025-12-30 - v2.2.1

### Bug fixes

- Allow to import `.opml` files ([4f07eb12](https://github.com/flusio/Flus/commit/4f07eb12))
- Fallback to Mastodon API v1 when getting server information ([dbbfc32a](https://github.com/flusio/Flus/commit/dbbfc32a))

### Maintenance

- Strengthen MastodonThreadsTest to not fail randomly ([55c3cc91](https://github.com/flusio/Flus/commit/55c3cc91))

## 2025-12-24 - v2.2.0

### Features

- Redesign and enable Mastodon sharing for everyone ([cd1b3060](https://github.com/flusio/Flus/commit/cd1b3060), [8b7a8c15](https://github.com/flusio/Flus/commit/8b7a8c15), [b4a6799b](https://github.com/flusio/Flus/commit/b4a6799b), [da5ed490](https://github.com/flusio/Flus/commit/da5ed490), [cc2bb106](https://github.com/flusio/Flus/commit/cc2bb106), [5e080a09](https://github.com/flusio/Flus/commit/5e080a09), [a528c653](https://github.com/flusio/Flus/commit/a528c653), [17a95e45](https://github.com/flusio/Flus/commit/17a95e45))
- Handle custom Mastodon servers' status max characters ([d4d2964a](https://github.com/flusio/Flus/commit/d4d2964a))
- Display the number of link's collections ([5edfdafe](https://github.com/flusio/Flus/commit/5edfdafe))

### Maintenance

- Update the dependencies ([35df1720](https://github.com/flusio/Flus/commit/35df1720))

### Developers

- Improve the Memoizer trait ([5b2e7a11](https://github.com/flusio/Flus/commit/5b2e7a11))

## 2025-12-06 - v2.1.3

### Bug fixes

- Fix check of the group name uniqueness ([7b75a95f](https://github.com/flusio/Flus/commit/7b75a95f))

### CLI

- Provide a basic command to import a wallabag export ([90d57f8d](https://github.com/flusio/Flus/commit/90d57f8d))

## 2025-11-30 - v2.1.2

### Maintenance

- Add missing links indexes on fetched columns ([52741d13](https://github.com/flusio/Flus/commit/52741d13))

## 2025-11-29 - v2.1.1

### Maintenance

- Refactor the HTTP cache system ([360582b1](https://github.com/flusio/Flus/commit/360582b1), [cd62fbfa](https://github.com/flusio/Flus/commit/cd62fbfa), [dfed8528](https://github.com/flusio/Flus/commit/dfed8528))
- Refetch links in error only on specific HTTP codes ([409ab50a](https://github.com/flusio/Flus/commit/409ab50a), [5fc6f75a](https://github.com/flusio/Flus/commit/5fc6f75a))
- Refetch links by considering Response lifetime ([4c83827b](https://github.com/flusio/Flus/commit/4c83827b))

### Developers

- Provide a generic Cache system ([9b766fa7](https://github.com/flusio/Flus/commit/9b766fa7))
- Set the default timezone to Europe/Paris in development ([f7c0a1e8](https://github.com/flusio/Flus/commit/f7c0a1e8))

## 2025-11-25 - v2.1.0

### Migration notes

Pocket is now definitely down, and Pocket import has been removed (except the file import with the CLI).
You can safely remove the `APP_POCKET_CONSUMER_KEY` environment key from your `.env` file.

### Features

- Remove Web Pocket import ([1e5d3834](https://github.com/flusio/Flus/commit/1e5d3834))

### Bug fixes

- Make sure that links and feeds are saved in database ([18b70a95](https://github.com/flusio/Flus/commit/18b70a95))

### Maintenance

- Refactor the backend controllers ([1f392899](https://github.com/flusio/Flus/commit/1f392899), [8deac9c8](https://github.com/flusio/Flus/commit/8deac9c8), [f6b1331c](https://github.com/flusio/Flus/commit/f6b1331c), [64d8ee8a](https://github.com/flusio/Flus/commit/64d8ee8a), [5a0a9798](https://github.com/flusio/Flus/commit/5a0a9798), [0c584663](https://github.com/flusio/Flus/commit/0c584663), [328dbca7](https://github.com/flusio/Flus/commit/328dbca7), [ecc40310](https://github.com/flusio/Flus/commit/ecc40310), [ba3de401](https://github.com/flusio/Flus/commit/ba3de401), [164830b0](https://github.com/flusio/Flus/commit/164830b0), [4a8e4570](https://github.com/flusio/Flus/commit/4a8e4570), [37fa0ada](https://github.com/flusio/Flus/commit/37fa0ada), [5fb17289](https://github.com/flusio/Flus/commit/5fb17289), [ea322dff](https://github.com/flusio/Flus/commit/ea322dff), [7338eb4d](https://github.com/flusio/Flus/commit/7338eb4d), [00fb404b](https://github.com/flusio/Flus/commit/00fb404b), [c8c29c1e](https://github.com/flusio/Flus/commit/c8c29c1e), [af4400db](https://github.com/flusio/Flus/commit/af4400db), [04583f08](https://github.com/flusio/Flus/commit/04583f08), [0f759bf4](https://github.com/flusio/Flus/commit/0f759bf4), [29a68f32](https://github.com/flusio/Flus/commit/29a68f32), [6cae69a6](https://github.com/flusio/Flus/commit/6cae69a6), [38409c05](https://github.com/flusio/Flus/commit/38409c05), [51a4c2ba](https://github.com/flusio/Flus/commit/51a4c2ba), [ed5dab95](https://github.com/flusio/Flus/commit/ed5dab95), [6e004811](https://github.com/flusio/Flus/commit/6e004811), [c49d3d7e](https://github.com/flusio/Flus/commit/c49d3d7e), [f630283c](https://github.com/flusio/Flus/commit/f630283c), [ed70e62a](https://github.com/flusio/Flus/commit/ed70e62a), [6ae498d9](https://github.com/flusio/Flus/commit/6ae498d9), [3727c392](https://github.com/flusio/Flus/commit/3727c392), [131c4b6c](https://github.com/flusio/Flus/commit/131c4b6c), [8fe7043b](https://github.com/flusio/Flus/commit/8fe7043b), [44bad00b](https://github.com/flusio/Flus/commit/44bad00b), [b7516723](https://github.com/flusio/Flus/commit/b7516723), [ef649a9e](https://github.com/flusio/Flus/commit/ef649a9e), [ab12d4b1](https://github.com/flusio/Flus/commit/ab12d4b1), [abb4c845](https://github.com/flusio/Flus/commit/abb4c845), [43ed1f3d](https://github.com/flusio/Flus/commit/43ed1f3d), [4d7bdda4](https://github.com/flusio/Flus/commit/4d7bdda4), [e841bf81](https://github.com/flusio/Flus/commit/e841bf81), [019eb292](https://github.com/flusio/Flus/commit/019eb292), [c999ab3e](https://github.com/flusio/Flus/commit/c999ab3e), [4b1f7542](https://github.com/flusio/Flus/commit/4b1f7542), [b45bb9c4](https://github.com/flusio/Flus/commit/b45bb9c4), [1bb3a59b](https://github.com/flusio/Flus/commit/1bb3a59b), [939a811e](https://github.com/flusio/Flus/commit/939a811e), [58a714e3](https://github.com/flusio/Flus/commit/58a714e3), [a8093de0](https://github.com/flusio/Flus/commit/a8093de0), [754dd991](https://github.com/flusio/Flus/commit/754dd991), [d5affbb4](https://github.com/flusio/Flus/commit/d5affbb4), [de8d6e67](https://github.com/flusio/Flus/commit/de8d6e67), [f871b51c](https://github.com/flusio/Flus/commit/f871b51c), [515ad6fc](https://github.com/flusio/Flus/commit/515ad6fc), [7599c264](https://github.com/flusio/Flus/commit/7599c264), [abc6c7c6](https://github.com/flusio/Flus/commit/abc6c7c6), [0fc7f819](https://github.com/flusio/Flus/commit/0fc7f819))
- Update dependencies ([1c3a3a11](https://github.com/flusio/Flus/commit/1c3a3a11), [7fa84515](https://github.com/flusio/Flus/commit/7fa84515), [e631c70f](https://github.com/flusio/Flus/commit/e631c70f), [9b5fff85](https://github.com/flusio/Flus/commit/9b5fff85))
- Increase performance during tests ([c6a6e0e5](https://github.com/flusio/Flus/commit/c6a6e0e5), [5c59a6bb](https://github.com/flusio/Flus/commit/5c59a6bb))

## 2025-10-23 - v2.0.6

### Bug fixes

- Fix performance when refreshing news ([1692ce6d](https://github.com/flusio/Flus/commit/1692ce6d))

## 2025-10-22 - v2.0.5

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

### API

- Add enpoints to follow/unfollow a collection/feed ([ae8c3904](https://github.com/flusio/Flus/commit/ae8c3904))
- Return feeds on the search enpoint ([83d333d1](https://github.com/flusio/Flus/commit/83d333d1))
- Add the `is_followed` attribute to the collections/feeds objects ([26c196ce](https://github.com/flusio/Flus/commit/26c196ce))
- Add the `publication_frequency_per_year` attribute to the collections/feeds objects ([39419a55](https://github.com/flusio/Flus/commit/39419a55))
- Add the missing link `created_at`, `is_hidden`, `source`, `published_at`, `number_notes` attributes to the search response ([8669729a](https://github.com/flusio/Flus/commit/8669729a))
- Document a changelog for each API endpoints ([8989c305](https://github.com/flusio/Flus/commit/8989c305))

### Technical

- Store the publication frequency in database ([c1895833](https://github.com/flusio/Flus/commit/c1895833))
- Optimize calculation of publication frequency ([59074931](https://github.com/flusio/Flus/commit/59074931))
- Update the dependencies ([573f0196](https://github.com/flusio/Flus/commit/573f0196))

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

## Older versions

The changes of the previous major versions are listed in dedicated files:

- [Flus 1.x](/docs/changelogs/v1.md)
- [Flus 0.x](/docs/changelogs/v0.md)
