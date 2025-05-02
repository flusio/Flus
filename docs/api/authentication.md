# REST API: Authentication

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
