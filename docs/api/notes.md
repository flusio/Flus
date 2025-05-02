# REST API: Notes

## List the notes of a link

```http
GET /api/v1/:link_id/notes
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X GET \
       "https://app.flus.fr/api/v1/links/<link_id>/notes"
```

### Response

`200 OK` on success:

```json
[
   {
      "id": "b52cfda703268bf56540ffaa13ee8279",
      "created_at": "2025-07-12T09:00:00+00:00",
      "content": "This is very interesting! #tools #FreeSoftware",
      "html_content": "<p>This is very interesting! <a href=\"https://app.flus.fr/links?q=%23tool\">#tool</a> <a href=\"https://app.flus.fr/links?q=%23FreeSoftware\">#FreeSoftware</a></p>",
      "tags": [
         "tool",
         "FreeSoftware"
      ]
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
       "https://app.flus.fr/api/v1/links/<link_id>/notes"
```

### Response

`200 OK` on success:

```json
{
  "id": "b52cfda703268bf56540ffaa13ee8279",
  "created_at": "2025-07-12T09:00:00+00:00",
  "content": "This is very interesting! #tools #FreeSoftware",
  "html_content": "<p>This is very interesting! <a href=\"https://app.flus.fr/links?q=%23tool\">#tool</a> <a href=\"https://app.flus.fr/links?q=%23FreeSoftware\">#FreeSoftware</a></p>",
  "tags": [
     "tool",
     "FreeSoftware"
  ]
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

## Update a note

```http
PATCH /api/v1/notes/:id
```

### JSON Parameters

- `content` (string, optional): the content of the note, formatted as Markdown

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X PATCH \
       -d '{"content": "This is very interesting! #tools #FreeSoftware"}' \
       "https://app.flus.fr/api/v1/notes/<id>"
```

### Response

`200 OK` on success:

```json
{
  "id": "b52cfda703268bf56540ffaa13ee8279",
  "created_at": "2025-07-12T09:00:00+00:00",
  "content": "This is very interesting! #tools #FreeSoftware",
  "html_content": "<p>This is very interesting! <a href=\"https://app.flus.fr/links?q=%23tool\">#tool</a> <a href=\"https://app.flus.fr/links?q=%23FreeSoftware\">#FreeSoftware</a></p>",
  "tags": [
     "tool",
     "FreeSoftware"
  ]
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

`403 Forbidden` if the user cannot update the note:

```json
{
    "error": "You cannot update the note."
}
```

`404 Not Found` if the note does not exist:

```json
{
    "error": "The note does not exist."
}
```

## Delete a note

```http
DELETE /api/v1/notes/:id
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/notes/<id>"
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

`403 Forbidden` if the user cannot delete the note:

```json
{
    "error": "You cannot delete the note."
}
```

`404 Not Found` if the note does not exist:

```json
{
    "error": "The note does not exist."
}
```
