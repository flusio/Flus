# REST API: Collections

## List the collections

```http
GET /api/v1/collections
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/collections"
```

### Response

`200 OK` on success:

```json
[
    {
        "id": "1833740002943468786",
        "name": "My favourites",
        "description": "",
        "group": null,
        "is_public": false
    },
    {
        "id": "1833740002944268171",
        "name": "My shares",
        "description": "",
        "group": null,
        "is_public": true
    }
]
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

## Create a collection

```http
POST /api/v1/collections
```

### JSON Parameters

- `name` (string, required): the name of the collection
- `description` (string, optional): the description of the collection, formatted as Markdown
- `is_public` (boolean, optional, default to false): whether or not the collection must be public

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"name": "My collection", "description": "The description", "is_public": true}' \
       "https://app.flus.fr/api/v1/collections"
```

### Response

`200 OK` on success:

```json
{
    "id": "1833740002944268171",
    "name": "My collection",
    "description": "The description",
    "group": null,
    "is_public": true
}
```

`400 Bad Request` if the name is empty:

```json
{
    "errors": {
        "name": [
            {"code": "presence", "description": "The name is required."}
        ]
    }
}
```

`400 Bad Request` if the name is too long:

```json
{
    "errors": {
        "name": [
            {"code": "length", "description": "The name must be less than 100 characters."}
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

## Get a collection

```http
GET /api/v1/collections/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/collections/<id>"
```

### Response

`200 OK` on success:

```json
{
    "id": "1833740002944268171",
    "name": "My collection",
    "description": "The description",
    "group": null,
    "is_public": true
}
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

## Update a collection

```http
PATCH /api/v1/collections/:id
```

### JSON Parameters

- `name` (string, optional): the name of the collection
- `description` (string, optional): the description of the collection, formatted as Markdown
- `is_public` (boolean, optional): whether or not the collection must be public

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X PATCH \
       -d '{"name": "The collection", "is_public": false}' \
       "https://app.flus.fr/api/v1/collections/<id>"
```

### Response

`200 OK` on success:

```json
{
    "id": "1833740002944268171",
    "name": "The collection",
    "description": "The description",
    "group": null,
    "is_public": false
}
```

`400 Bad Request` if the name is empty:

```json
{
    "errors": {
        "name": [
            {"code": "presence", "description": "The name is required."}
        ]
    }
}
```

`400 Bad Request` if the name is too long:

```json
{
    "errors": {
        "name": [
            {"code": "length", "description": "The name must be less than 100 characters."}
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

`403 Forbidden` if the user cannot update the collection:

```json
{
    "error": "You cannot update the collection."
}
```

`404 Not Found` if the collection doesn’t exist:

```json
{
    "error": "The collection does not exist."
}
```

## Delete a collection

```http
DELETE /api/v1/collections/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/collections/<id>"
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

`403 Forbidden` if the user cannot delete the collection:

```json
{
    "error": "You cannot delete the collection."
}
```

`404 Not Found` if the collection does not exist:

```json
{
    "error": "The collection does not exist."
}
```
