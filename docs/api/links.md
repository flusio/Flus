# REST API: Links

## List the links

```http
GET /api/v1/links
```

### GET Parameters

- `collection` (string, required): the id of the collection, or one of the values: "read" or "to-read"
- `page` (integer, optional): the pagination page to fetch
- `per_page` (integer, optional): the number of links to retrieve (min: 1, max: 100, default: 30)

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/links?collection=<id>&per_page=5&page=10"
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
        "source": null,
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

`403 Forbidden` if the user cannot access the collection:

```json
{
    "error": "You cannot access the collection."
}
```

`404 Not Found` if the collection doesn’t exist:

```json
{
    "error": "The collection does not exist."
}
```

`404 Not Found` if the page doesn’t exist:

```json
{
    "error": "The page does not exist."
}
```

### Changelog

- 2.0.0: added

## Get a link

```http
GET /api/v1/links/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/links/<id>"
```

### Response

`200 OK` on success:

```json
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
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

`403 Forbidden` if the user cannot access the link:

```json
{
    "error": "You cannot access the link."
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

## Update a link

```http
PATCH /api/v1/links/:id
```

### JSON Parameters

- `title` (string, optional): the title of the link
- `reading_time` (integer, optional): the reading time of the link, in minutes

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X PATCH \
       -d '{"title": "Flus homepage", "reading_time": 10}' \
       "https://app.flus.fr/api/v1/links/<id>"
```

### Response

`200 OK` on success:

```json
{
    "id": "1834713987655905145",
    "created_at": "2025-08-22T15:30:00+00:00",
    "title": "Flus homepage",
    "url": "https://flus.fr/",
    "is_hidden": false,
    "reading_time": 10,
    "tags": [],
    "source": null,
    "is_read": false,
    "is_read_later": false,
    "collections": [],
    "published_at": null,
    "number_notes": 0
}
```

`400 Bad Request` if the title is empty:

```json
{
    "errors": {
        "title": [
            {"code": "presence", "description": "The title is required."}
        ]
    }
}
```

`400 Bad Request` if the reading time is less than 0:

```json
{
    "errors": {
        "title": [
            {"code": "comparison", "description": "The reading time must be greater or equal to 0."}
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

`403 Forbidden` if the user cannot update the link:

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

## Delete a link

```http
DELETE /api/v1/links/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/links/<id>"
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

`403 Forbidden` if the user cannot delete the link:

```json
{
    "error": "You cannot delete the link."
}
```

`404 Not Found` if the link does not exist:

```json
{
    "error": "The link does not exist."
}
```

### Changelog

- 2.0.0: added

## Mark a link as read

```http
POST /api/v1/links/:id/read
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       "https://app.flus.fr/api/v1/links/<id>/read"
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

## Unmark a link as read

```http
DELETE /api/v1/links/:id/read
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/links/<id>/read"
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

## Mark a link to read later

```http
POST /api/v1/links/:id/later
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       "https://app.flus.fr/api/v1/links/<id>/later"
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

`404 Not Found` if the link doesn’t exist or cannot be updated by the authenticated user:

```json
{
    "error": "The link does not exist."
}
```

### Changelog

- 2.0.0: added

## Add a collection to a link

```http
PUT /api/v1/links/:link_id/collections/:collection_id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X PUT \
       "https://app.flus.fr/api/v1/links/<link_id>/collections/<collection_id>"
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

`403 Forbidden` if the user doesn't have access to the link or the collection:

```json
{
    "error": "You cannot update the link."
}
```

`404 Not Found` if the link or the collection don’t exist:

```json
{
    "error": "The link does not exist."
}
```

```json
{
    "error": "The collection does not exist."
}
```

### Changelog

- 2.0.0: added

## Remove a collection from a link

```http
DELETE /api/v1/links/:link_id/collections/:collection_id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/links/<link_id>/collections/<collection_id>"
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

`403 Forbidden` if the user doesn't have access to the link or the collection:

```json
{
    "error": "You cannot update the link."
}
```

`404 Not Found` if the link or the collection don’t exist:

```json
{
    "error": "The link does not exist."
}
```

```json
{
    "error": "The collection does not exist."
}
```

### Changelog

- 2.0.0: added
