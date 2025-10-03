# REST API: Search links and feeds

## Search information

```http
POST /api/v1/search
```

### JSON Parameters

- `url` (string, required): a valid HTTP or HTTPS URL

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"url": "https://flus.fr"}' \
       "https://app.flus.fr/api/v1/search"
```

### Response

`200 OK` on success:

```json
{
    "links": [
        {
            "id": "1834713987655905145",
            "created_at": "2025-08-22T15:30:00+00:00",
            "title": "Flus, le complément éditorial de votre veille",
            "url": "https://flus.fr/",
            "is_hidden": false,
            "reading_time": 3,
            "tags": [],
            "source": null,
            "is_read": false,
            "is_read_later": false,
            "collections": [],
            "published_at": null,
            "number_notes": 0
        }
    ],
    "feeds": [
        {
            "id": "1844964122066694830",
            "name": "Le carnet de Flus",
            "description": "",
            "group": null,
            "url": "https://flus.fr/carnet/feeds/all.atom.xml",
            "type": "atom",
            "site_url": "https://flus.fr/carnet/"
        }
    ]
}
```

`400 Bad Request` if the URL is invalid:

```json
{
    "errors": {
        "url": [
            {"code": "url", "description": "The link is invalid."}
        ]
    }
}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Information

Note that feeds are a specific type of [collections](./collections.md).
Thus, you can follow feeds using the same endpoint as for the collections.

### Changelog

- 2.0.0: added
- 2.0.5: add missing `created_at`, `is_hidden`, `source`, `published_at`, `number_notes`
- 2.0.5: `feeds` entry added
