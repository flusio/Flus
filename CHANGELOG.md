# Changelog of Flus

## 2026-10-28 - v3.0.1

### Features

- Put OPML actions on the "Sources" pages ([85c5b73a](https://github.com/flusio/Flus/commit/85c5b73a))
- Allow to follow sources from the stream sources ([f44b9798](https://github.com/flusio/Flus/commit/f44b9798))
- Add a button to unselect all sources in the stream filters ([42b28356](https://github.com/flusio/Flus/commit/42b28356))

### Bug fixes

- Fix display of the number of results when searching links ([9df39cc1](https://github.com/flusio/Flus/commit/9df39cc1))
- Handle unreachable Mastodon hosts properly ([95048a03](https://github.com/flusio/Flus/commit/95048a03))

### Maintenance

- Update the dependencies ([867e9c35](https://github.com/flusio/Flus/commit/867e9c35))
- Load only the feed links matching the fetched entries when synchronizing feeds ([8e03364a](https://github.com/flusio/Flus/commit/8e03364a))
- Ignore the feed entries that don't match min/max/period constraints when synchronizing feeds ([d17faf58](https://github.com/flusio/Flus/commit/d17faf58))
- Remove the `links.fetched_error` column ([beb90bfd](https://github.com/flusio/Flus/commit/beb90bfd))

### Developers

- Add a `Belt::slugify` method ([baac9567](https://github.com/flusio/Flus/commit/baac9567))
- Refactor generating OPML ([67c19b6d](https://github.com/flusio/Flus/commit/67c19b6d))
- Allow to test unreachable URLs with the mock server ([79497a82](https://github.com/flusio/Flus/commit/79497a82))
- Fix the target branches on the CI ([27285d8f](https://github.com/flusio/Flus/commit/27285d8f))

## 2026-10-11 - v3.0.0

### Migration notes

The file to import default "read later" links has been renamed: `data/default-bookmarks.atom.xml` becomes `data/default-read-later.atom.xml`.

The `/bookmarks` page is now served at `/read/later`.
The `/news` page is now served at `/journal`.
The old URLs redirect permanently.

In the archives exported by the users, `bookmarks.atom.xml` is renamed to `read-later.atom.xml` and `news.atom.xml` is renamed to `journal.atom.xml`.

A new `DB_CLEANER_WORK_MEM` environment variable allows you to increase the memory used by PostgreSQL when deleting the old links.
With a lot of links, it avoids writing big temporary files on disk every night.
See `env.sample` for more information.

Reminder: **a migration must have been applied manually when you upgraded to Flus 2.5.0.**
If you didn't yet, please run **before upgrading to Flus 3.0.0:**

```console
flus# sudo -u www-data php cli migrations setup-url-statuses
```

This command takes two optional parameters:

- `--batch-size=INT` to change the size of the batches (1000 by default)
- `--dry-run` to not apply the migration immediately, but to get how many data will be migrated.

### Features

- Enable streams for everyone! ([14f42f2a](https://github.com/flusio/Flus/commit/14f42f2a))
- Add more ways to search links / improve the search engine ([b952056a](https://github.com/flusio/Flus/commit/b952056a), [3f2f99e8](https://github.com/flusio/Flus/commit/3f2f99e8), [1b4285a8](https://github.com/flusio/Flus/commit/1b4285a8), [e1379b47](https://github.com/flusio/Flus/commit/e1379b47))
- Allow to customise sources' name and illustration ([7fb630e1](https://github.com/flusio/Flus/commit/7fb630e1))
- Add a toolbar to the text editor ([74d19b97](https://github.com/flusio/Flus/commit/74d19b97))
- Extend the session duration on visits ([e6c534a7](https://github.com/flusio/Flus/commit/e6c534a7))
- Allow to go to sources directly from link timelines ([55b59249](https://github.com/flusio/Flus/commit/55b59249))
- Provide a "skip to navigation" button ([8120bbe7](https://github.com/flusio/Flus/commit/8120bbe7), [0daa3613](https://github.com/flusio/Flus/commit/0daa3613))
- Display the last activity date in sessions ([f2466b6a](https://github.com/flusio/Flus/commit/f2466b6a))

Streams:

- Allow to make a stream public ([2fe05d15](https://github.com/flusio/Flus/commit/2fe05d15), [cd0c581d](https://github.com/flusio/Flus/commit/cd0c581d))
- Allow to share a stream to other users ([a9f45273](https://github.com/flusio/Flus/commit/a9f45273))
- Allow to select several sources in streams ([e92ea28a](https://github.com/flusio/Flus/commit/e92ea28a))
- Export and import streams ([29afae2c](https://github.com/flusio/Flus/commit/29afae2c), [210de0c0](https://github.com/flusio/Flus/commit/210de0c0), [f74a2cbc](https://github.com/flusio/Flus/commit/f74a2cbc))
- Provide Atom feed and OPML for streams ([a870df6e](https://github.com/flusio/Flus/commit/a870df6e), [4bd0641a](https://github.com/flusio/Flus/commit/4bd0641a), [34760849](https://github.com/flusio/Flus/commit/34760849), [45f3458b](https://github.com/flusio/Flus/commit/45f3458b))
- Make "has unread" depend on the current user ([41942243](https://github.com/flusio/Flus/commit/41942243))
- Display "Today" and "Yesterday" in streams ([6a3bea86](https://github.com/flusio/Flus/commit/6a3bea86))
- Display the total number of links ([19a9218b](https://github.com/flusio/Flus/commit/19a9218b))
- Localize the time format of links ([25c81676](https://github.com/flusio/Flus/commit/25c81676))

### Improvements

- Rename "News" into "Journal" ([20ef24c0](https://github.com/flusio/Flus/commit/20ef24c0))
- Rename "feed" to "source" in the Journal ([2b0b4e2c](https://github.com/flusio/Flus/commit/2b0b4e2c))
- Improve the onboarding and mention the streams ([1f8dacd9](https://github.com/flusio/Flus/commit/1f8dacd9))
- Remove URL share from the "Access" modals ([7aa822d6](https://github.com/flusio/Flus/commit/7aa822d6))
- Decrease link, collection and profile illustration sizes ([63f0325f](https://github.com/flusio/Flus/commit/63f0325f))
- Decrease margin around "Select" buttons in sources ([cd5c5d30](https://github.com/flusio/Flus/commit/cd5c5d30))

### Bug fixes

- Don't delete users if subscription is ongoing ([cd27e8c0](https://github.com/flusio/Flus/commit/cd27e8c0))
- Fix the outline visibility in the popups ([c7dc0124](https://github.com/flusio/Flus/commit/c7dc0124))
- Don't change scroll position when writing in editor ([58be1e8d](https://github.com/flusio/Flus/commit/58be1e8d))
- Don't animate chevron on state restoration in details ([4926b1e1](https://github.com/flusio/Flus/commit/4926b1e1))
- Fix the format of displayed datetimes ([c7613902](https://github.com/flusio/Flus/commit/c7613902))
- Display the "empty news" popup above the button ([b35dd6ee](https://github.com/flusio/Flus/commit/b35dd6ee))
- Redirect to links if link is deleted from its own page ([9b6714ff](https://github.com/flusio/Flus/commit/9b6714ff))
- Handle revoked Mastodon access ([4a4bbf21](https://github.com/flusio/Flus/commit/4a4bbf21))
- Track users last activity on API calls ([7e8be489](https://github.com/flusio/Flus/commit/7e8be489))

### Maintenance

- Improve performance when fetching links ([0ff010d2](https://github.com/flusio/Flus/commit/0ff010d2))
- Make possible to set `work_mem` when cleaning links ([d9f383b7](https://github.com/flusio/Flus/commit/d9f383b7))
- Add indexes to list read and read later links ([6c4c2176](https://github.com/flusio/Flus/commit/6c4c2176))
- Count rows without fetching entire rows ([e0f200f0](https://github.com/flusio/Flus/commit/e0f200f0))
- Log fetch errors by URL instead of by links ([15d90447](https://github.com/flusio/Flus/commit/15d90447), [cf8bab76](https://github.com/flusio/Flus/commit/cf8bab76))
- Fix active users statistics ([ea1e789a](https://github.com/flusio/Flus/commit/ea1e789a))
- Update the dependencies ([aa8db4a3](https://github.com/flusio/Flus/commit/aa8db4a3), [05d2f3c8](https://github.com/flusio/Flus/commit/05d2f3c8))

### Documentation

- Set up contribution instructions ([b6fe93d6](https://github.com/flusio/Flus/commit/b6fe93d6))
- Split the changelog ([39852166](https://github.com/flusio/Flus/commit/39852166))
- Update README description and screenshots ([853af21d](https://github.com/flusio/Flus/commit/853af21d))

### Developers

- Set up the streams for all the users ([e6f84016](https://github.com/flusio/Flus/commit/e6f84016))
- Allow to open modal from a DOM template ([7b868ea3](https://github.com/flusio/Flus/commit/7b868ea3))
- Improve the validation of tags ([0c94dd42](https://github.com/flusio/Flus/commit/0c94dd42))
- Normalize View parameters ([1f4d1181](https://github.com/flusio/Flus/commit/1f4d1181))
- Extract a partial to generate hidden inputs ([7835b28c](https://github.com/flusio/Flus/commit/7835b28c))
- Refactor `User::refreshLastActivity()` ([b19c6e45](https://github.com/flusio/Flus/commit/b19c6e45))
- Extract the Markdown fields in a dedicated template ([9ca1b49c](https://github.com/flusio/Flus/commit/9ca1b49c))
- Don't display time filter in profile OPML ([c6796c42](https://github.com/flusio/Flus/commit/c6796c42))
- Refactor getting collection feed URL ([e9f4b158](https://github.com/flusio/Flus/commit/e9f4b158))
- Refactor User follow-related methods ([19e20202](https://github.com/flusio/Flus/commit/19e20202))
- Extract traits from Share/Unshare collection forms ([11877cc7](https://github.com/flusio/Flus/commit/11877cc7))
- Cleanup groups on followed collections ([630d43e3](https://github.com/flusio/Flus/commit/630d43e3))
- Remove deprecated code ([f5b347f8](https://github.com/flusio/Flus/commit/f5b347f8))

Preloading:

- Display number of notes in all lists with preloading ([5df73671](https://github.com/flusio/Flus/commit/5df73671))
- Preload the notes in the feeds ([bd14d498](https://github.com/flusio/Flus/commit/bd14d498))
- Preload follows in User instead of Collection ([435c85bd](https://github.com/flusio/Flus/commit/435c85bd))

URL statuses:

- Remove migration to setup UrlStatuses ([61ffaba9](https://github.com/flusio/Flus/commit/61ffaba9))
- Delete bookmarks/read/never collection types ([104a7ee8](https://github.com/flusio/Flus/commit/104a7ee8), [8e13d678](https://github.com/flusio/Flus/commit/8e13d678), [cecedb17](https://github.com/flusio/Flus/commit/cecedb17), [2f2778f3](https://github.com/flusio/Flus/commit/2f2778f3))
- Finish to rename bookmarks into read later ([1a4f2dee](https://github.com/flusio/Flus/commit/1a4f2dee))
- Rename the "never" concept to "dismiss" ([866b1838](https://github.com/flusio/Flus/commit/866b1838))

## Older versions

The changes of the previous major versions are listed in dedicated files:

- [Flus 2.x](/docs/changelogs/v2.md)
- [Flus 1.x](/docs/changelogs/v1.md)
- [Flus 0.x](/docs/changelogs/v0.md)
