# REST API: Journal

## List the links of the journal

```http
GET /api/v1/journal
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/journal"
```

### Response

`200 OK` on success:

```json
[
    {
        "id": "1834713987655905145",
        "created_at": "2025-08-22T15:30:00+00:00",
        "title": "Flus, le complément éditorial de votre veille",
        "url": "https://flus.fr/",
        "is_hidden": false,
        "reading_time": 3,
        "tags": [],
        "source": "collection#<id>",
        "is_read": false,
        "is_read_later": false,
        "collections": [],
        "published_at": "2025-08-22T15:30:00+00:00",
        "number_notes": 0
    }
]
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Changelog

- 2.0.0: added

## Refresh the journal

```http
POST /api/v1/journal
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       "https://app.flus.fr/api/v1/journal"
```

### Response

`200 OK` on success:

```json
{
    "count": 50
}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Changelog

- 2.0.0: added

## Mark the links of the journal as read

```http
POST /api/v1/journal/read
```

### JSON Parameters

- `date` (string, optional, format: `YYYY-MM-DD`): a date to filter the links to mark as read
- `source` (string, optional): a source to filter the links to mark as read

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"date": "2025-08-22"}' \
       "https://app.flus.fr/api/v1/journal/read"
```

### Response

`200 OK` on success:

```json
{}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Changelog

- 2.0.0: added

## Mark the links of the journal to read later

```http
POST /api/v1/journal/later
```

### JSON Parameters

- `date` (string, optional, format: `YYYY-MM-DD`): a date to filter the links to mark to read later
- `source` (string, optional): a source to filter the links to mark to read later

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"date": "2025-08-22"}' \
       "https://app.flus.fr/api/v1/journal/later"
```

### Response

`200 OK` on success:

```json
{}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Changelog

- 2.0.0: added

## Remove the links from the journal

```http
DELETE /api/v1/journal/links
```

### JSON Parameters

- `date` (string, optional, format: `YYYY-MM-DD`): a date to filter the links to remove
- `source` (string, optional): a source to filter the links to remove

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       -d '{"date": "2025-08-22"}' \
       "https://app.flus.fr/api/v1/journal/links"
```

### Response

`200 OK` on success:

```json
{}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Changelog

- 2.0.0: added

## Remove a single link from the journal

```http
DELETE /api/v1/journal/links/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/journal/links/<id>"
```

### Response

`200 OK` on success:

```json
{}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

`403 Forbidden` if the user doesn't have access to the link:

```json
{
    "error": "You cannot update the link."
}
```

`404 Not Found` if the link doesn’t exist:

```json
{
    "error": "The link does not exist."
}
```

### Changelog

- 2.0.0: added
