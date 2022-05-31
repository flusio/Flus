# Changelog of flusio

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

You might need to reset some ids due to the bug fixed by [`df29d41`](https://github.com/flusio/flusio/commit/df29d413260cffa2d9f433139891c574ddcb504f).

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
