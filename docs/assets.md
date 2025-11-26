# How are the assets bundled

I use [esbuild](https://esbuild.github.io/) to bundle the assets.

Either if you started esbuild via Docker Compose or NPM, it will look at two files: [`src/assets/stylesheets/application.css`](/src/assets/stylesheets/application.css) and [`src/assets/javascripts/application.js`](/src/assets/javascripts/application.js).
These files are the entrypoints and load the other CSS and JS files, which are monitored by esbuild.

Each time esbuild detects a change in one of these files, it bundles the files all together and puts the bundled files under the `public/dev_assets/` folder.
The files are finally loaded in the application [by the layout](/src/views/layouts/base.html.twig) via the `url_asset()` function.

Please note you must never change directly the files under the `public/dev_assets/` folder since they will be erased at the next build.

At each release, the assets are bundled and minified under the `public/assets/` folder.
The `url_asset()` view function is configured to serve this folder in production.
