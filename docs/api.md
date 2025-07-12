# API

## Get an access token

```http
POST /api/v1/sessions
```

### JSON Parameters

- `email` (string, required): the email of the user to authenticate
- `password` (string, required): the password of the user to authenticate
- `app_name` (string, required): the name of the application making the request

### Example

```console
$ curl -H "Content-Type: application/json" \
       -X POST \
       -d '{"email": "alix@example.org", "password": "secret", "app_name": "curl request"}' \
       https://app.flus.fr/api/v1/sessions
```

### Response

`200 OK` on success:

```json
{
    "token": "b6d6926418cf69285f3917556e7fe7cc99c43c07cb220e5375eb325efcec5fd5"
}
```

`400 Bad Request` if the credentials are invalid:

```json
{
    "errors": {
        "@base": [
            {"code": "invalid_credentials", "description": "The credentials are invalid."}
        ]
    }
}
```

`400 Bad Request` if a parameter is missing:

```json
{
    "errors": {
        "app_name": [
            {"code": "presence", "description": "The app name is required."}
        ]
    }
}
```

## Search link information

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
       https://app.flus.fr/api/v1/search
```

### Response

`200 OK` on success:

```json
{
    "links": [
        {
            "id": "1834713987655905145",
            "title": "Flus, le complément éditorial de votre veille",
            "url": "https://flus.fr/",
            "reading_time": 3,
            "tags": [],
            "is_read": false,
            "is_read_later": false,
            "collections": []
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

## Mark a link as read

```http
POST /api/v1/links/:id/read
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       https://app.flus.fr/api/v1/links/<id>/read
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

## Unmark a link as read

```http
DELETE /api/v1/links/:id/read
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       https://app.flus.fr/api/v1/links/<id>/read
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

## Mark a link to read later

```http
POST /api/v1/links/:id/later
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       https://app.flus.fr/api/v1/links/<id>/later
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

## List the collections

```http
GET /api/v1/collections
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       https://app.flus.fr/api/v1/collections
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

## Add a collection to a link

```http
PUT /api/v1/links/:link_id/collections/:collection_id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X PUT \
       https://app.flus.fr/api/v1/links/<link_id>/collections/<collection_id>
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

## Remove a collection from a link

```http
DELETE /api/v1/links/:link_id/collections/:collection_id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       https://app.flus.fr/api/v1/links/<link_id>/collections/<collection_id>
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

## List the notes of a link

```http
GET /api/v1/:link_id/notes
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       https://app.flus.fr/api/v1/links/<link_id>/notes
```

### Response

`200 OK` on success:

```json
[
   {
      "created_at": "2025-07-12T09:00:00+00:00",
      "html_content": "<p>This is very interesting! <a href=\"https://app.flus.fr/links?q=%23tool\">#tool</a> <a href=\"https://app.flus.fr/links?q=%23FreeSoftware\">#FreeSoftware</a></p>",
      "id": "b52cfda703268bf56540ffaa13ee8279",
      "tags": [
         "tool",
         "FreeSoftware"
      ],
      "user": {
         "username": "Alix Hambourg"
      }
   }
]
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
    "error": "You cannot list the notes of the link."
}
```

`404 Not Found` if the link does not exist:

```json
{
    "error": "The link does not exist."
}
```

## Add a note to a link

```http
POST /api/v1/links/:link_id/notes
```

### JSON Parameters

- `content` (string, required): the content of the note, formatted as Markdown

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"content": "This is very interesting! #tools #FreeSoftware"}' \
       https://app.flus.fr/api/v1/links/<link_id>/notes
```

### Response

`200 OK` on success:

```json
{
  "created_at": "2025-07-12T09:00:00+00:00",
  "html_content": "<p>This is very interesting! <a href=\"https://app.flus.fr/links?q=%23tool\">#tool</a> <a href=\"https://app.flus.fr/links?q=%23FreeSoftware\">#FreeSoftware</a></p>",
  "id": "b52cfda703268bf56540ffaa13ee8279",
  "tags": [
     "tool",
     "FreeSoftware"
  ],
  "user": {
     "username": "Alix Hambourg"
  }
}
```

`400 Bad Request` if the content is empty:

```json
{
    "errors": {
        "content": [
            {"code": "presence", "description": "The message is required."}
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

`403 Forbidden` if the user doesn't have access to the link:

```json
{
    "error": "You cannot add notes to the link."
}
```

`404 Not Found` if the link does not exist:

```json
{
    "error": "The link does not exist."
}
```
