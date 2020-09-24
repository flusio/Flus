# Changelog of flusio

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
