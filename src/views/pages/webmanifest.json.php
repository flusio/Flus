{
  "name": "<?= $brand ?>",
  "short_name": "<?= $brand ?>",
  "description": "<?= _('Collect, organise, comment on and share links from around the Web.') ?>",
  "start_url": "<?= url('home') ?>",
  "display": "standalone",
  "background_color": "#eeebfb",
  "theme_color": "#00d0ad",
  "share_target": {
    "enctype": "application/x-www-form-urlencoded",
    "action": "<?= url('show search link') ?>",
    "method": "GET",
    "params": {
      "url": "url"
    }
  },
  "screenshots" : [
    {
      "src": "<?= url_static('screenshot.webp') ?>",
      "sizes": "1280x848",
      "type": "image/webp",
      "form_factor": "wide",
      "label": "<?= _f('Bookmarks screen of %s on desktop', $brand) ?>"
    },
    {
      "src": "<?= url_static('screenshot-mobile.webp') ?>",
      "sizes": "416x900",
      "type": "image/webp",
      "form_factor": "narrow",
      "label": "<?= _f('Bookmarks screen of %s on mobile', $brand) ?>"
    }
  ],
  "icons": [{
    "src": "<?= url_static('icons/icon-48.png') ?>",
    "sizes": "48x48",
    "type": "image/png"
  }, {
    "src": "<?= url_static('icons/icon-72.png') ?>",
    "sizes": "72x72",
    "type": "image/png"
  }, {
    "src": "<?= url_static('icons/icon-192.png') ?>",
    "sizes": "192x192",
    "type": "image/png"
  }, {
    "src": "<?= url_static('icons/icon-256.png') ?>",
    "sizes": "256x256",
    "type": "image/png"
  }, {
    "src": "<?= url_static('icons/icon-512.png') ?>",
    "sizes": "512x512",
    "type": "image/png"
  }]
}
