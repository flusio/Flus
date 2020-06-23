# How is the localization managed

To localize flusio, I use the “old-but-still-good” [`gettext()` function](https://www.php.net/manual/function.gettext)
which is aliased by `_()` (you might have seen it from time to time).

Gettext allows me to write the code in full English (instead of some sort of
key string). It means that I can write directly, for instance `<?= _('Login') ?>`
instead of `<?= _('loginPage.form.submit') ?>` for a submit button in a login
form. It also means English is the default language of flusio.

For the other languages (only French at the moment), the locale files are
placed under the [`locales/` folder](/locales/). Each language defines at
least three files. For French:

- `locales/fr_FR/metadata.json` declares (only) the language in a
  human-readable way (“Français”);
- `locales/fr_FR/LC_MESSAGES/main.po` is where the strings are translated
- `locales/fr_FR/LC_MESSAGES/main.mo` is the compiled version of this file,
  used by PHP.

I don’t manipulate the two last files directly, but I use [Poedit](https://poedit.net/),
a translation editor which is able to manipulate `po` and `mo` files.

PHP is told where to find these files with the [`bindtextdomain()`](https://www.php.net/manual/function.bindtextdomain.php)
function and the language to use is set with [`setlocale()`](https://www.php.net/manual/function.setlocale.php).
All this happens in the [`src/Application.php` file](/src/Application.php).

Sometimes, I need to localize strings within the JavaScript code. In this case,
the `_()` function is a bit different since it's not provided by PHP. It must
be imported:

```javascript
import _ from 'js/l10n.js';

console.log(_('Hello World!'));
```

The function will look into the `window.jsConfiguration.l10n` object to search
for the string. To add a new translation to this object, you’ll have to add the
string to the `$translations` array from the [`src/utils/javascript_configuration.php`
file](/src/utils/javascript_configuration.php)
