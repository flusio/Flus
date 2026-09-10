# REST API: Authentication

## Get an access token

The token is valid for 1 month.
Each authenticated request extends its validity by 1 month, up to 1 year after its creation.
After that, or if the token is not used for 1 month, a new token must be requested.

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
       "https://app.flus.fr/api/v1/sessions"
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

### Changelog

- 2.0.0: added
- 3.0.0: the validity of the token is extended on each request

## Delete current session

```http
DELETE /api/v1/session
```

### Example

```console
$ curl -H "Content-Type: application/json" \
       -H "Authorization: Bearer <token>" \
       -X DELETE \
       "https://app.flus.fr/api/v1/session"
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

- 2.0.3: added
