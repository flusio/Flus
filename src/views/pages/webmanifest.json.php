{
  "name": "<?= get_app_configuration('brand') ?>",
  "short_name": "<?= get_app_configuration('brand') ?>",
  "description": "<?= _('Collect, organise, annotate and share links from around the Web.') ?>",
  "start_url": "<?= url('home') ?>",
  "display": "standalone",
  "background_color": "#f7f6f4",
  "theme_color": "#00d7ad",
  "share_target": {
    "enctype": "application/x-www-form-urlencoded",
    "action": "<?= url('share') ?>",
    "method": "GET",
    "params": {
      "title": "title",
      "text": "text",
      "url": "url"
    }
  },
  "screenshots" : [
    {
      "src": "<?= url_static('screenshot.webp') ?>",
      "sizes": "1280x848",
      "type": "image/webp",
      "form_factor": "wide",
      "label": "<?= _f('Bookmarks screen of %s on desktop', get_app_configuration('brand')) ?>"
    },
    {
      "src": "<?= url_static('screenshot-mobile.webp') ?>",
      "sizes": "416x900",
      "type": "image/webp",
      "form_factor": "narrow",
      "label": "<?= _f('Bookmarks screen of %s on mobile', get_app_configuration('brand')) ?>"
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
