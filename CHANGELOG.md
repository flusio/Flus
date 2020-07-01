# Changelog of flusio

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
