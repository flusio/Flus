# How are the assets bundled

I use [Parcel](https://parceljs.org/) to bundle the assets. Be aware I use the
version 2 which is still [in alpha version](https://github.com/parcel-bundler/parcel/issues/3377).
I really like Parcel, but unfortunately version 1 is buggy with the setup I want…

Either if you started Parcel via docker-compose or NPM, it will look at two
files: [`src/assets/stylesheets/application.css`](/src/assets/stylesheets/application.css)
and [`src/assets/javascripts/application.js`](/src/assets/javascripts/application.js).
These files are the entrypoints and load the other CSS and JS files, which are
monitored by Parcel.

Each time Parcel detects a change in one of these files, it bundles the files
altogether and puts the bundled files under the `public/dev_assets/` folder.
The files are finally loaded in the application [by the layout](/src/views/_layouts/base.phtml)
via the `url_asset()` function.

Please note you must never change directly the files under the `public/dev_assets/`
folder since they will be erased at the next build.

At each release, the assets are bundled and minified under the `public/assets/`
folder. The `url_asset()` view function is configured to serve this folder in
production.
