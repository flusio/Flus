# API

## Get an access token

Create a new session and get an access token:

```http
POST /api/v1/sessions
```

### JSON Parameters

- `email` (string, required): the email of the user to authenticate
- `password` (string, required): the password of the user to authenticate
- `app_name` (string, required): the name of the application making the request

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

### Example

```console
$ curl -H "Content-Type: application/json" \
       -X POST \
       -d '{"email": "alix@example.org", "password": "secret", "app_name": "curl request"}' \
       https://app.flus.fr/api/v1/sessions
```

## Search link information

Retrieve link information by URL:

```http
POST /api/v1/search
```

### JSON Parameters

- `url` (string, required): a valid HTTP or HTTPS URL

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

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       -d '{"url": "https://flus.fr"}' \
       https://app.flus.fr/api/v1/search
```

## Mark a link as read

Mark a link as read for the authenticated user:

```http
POST /api/v1/links/:id/read
```

### Response

`200 OK` on success:

```json
{}
```

`404 Not Found` if the link doesn’t exist:

```json
{
    "error": "The link does not exist."
}
```

`403 Forbidden` if the user doesn't have access to the link:

```json
{
    "error": "You cannot update the link."
}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X POST \
       https://app.flus.fr/api/v1/links/<id>/read
```

## Unmark a link as read

Unmark a link as read for the authenticated user:

```http
DELETE /api/v1/links/:id/read
```

### Response

`200 OK` on success:

```json
{}
```

`404 Not Found` if the link doesn’t exist:

```json
{
    "error": "The link does not exist."
}
```

`403 Forbidden` if the user doesn't have access to the link:

```json
{
    "error": "You cannot update the link."
}
```

`401 Unauthorized` if the request is not authenticated:

```json
{
    "error": "The request is not authenticated."
}
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       https://app.flus.fr/api/v1/links/<id>/read
```
