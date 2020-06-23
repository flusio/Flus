# How are the users’ errors managed

It’s a well-known fact: we cannot rely on users’ input. An input can be
improperly completed, or an attacker could try to find a security breach.
Consequently, we must be very careful when verifying requests parameters. This
document is intended to standardize how the errors are handled in flusio.

## Overview

The errors are mainly verified at the controller level. It’s where we return
the responses, and so where we must verify the inputs. We must distinguish
routes that accept both `GET` and `POST` requests (e.g. `/registration`), from
the routes which only accept `POST` requests (e.g. `/logout`). In the first
case, both controller actions can easily return the same view since they are
tightly related, while in the other case, the main response will often be a
redirection.

In the rest of this document, we’ll see different errors cases and see how to
handle them. The order in this document is important and should dictate the
order of checks in the controllers.

## Error case #1: Not connected

**Most of the actions require the user to be connected. If she’s not, the
action should be forbidden to avoid access issues.**

If the route accepts both `GET` and `POST`, the default response should be a
redirection to the login page with a `redirect_to` param:

```php
$user = utils\CurrentUser::get();
if (!$user) {
    return Response::redirect('login', [
        'redirect_to' => \Minz\Url::for('the current route'),
    ]);
}
```

## Error case #2: Not found resources

**Some routes accept an `id` parameter (or several) as `GET` or `POST`
parameters. If the id matches non existing resource, or if the user has no
access to the resource, a `not found` response should be returned.**

If the route accepts both `GET` and `POST` requests, the default response
should be a `not found` HTTP response displaying the current `GET` view:

```php
if (!$db_resource) {
    return Response::notFound('get view pointer', [
        'error' => _('This resource doesn’t exist.'),
    ]);
}
```

The error message should be adapted to specific cases. You must verify the view
displays the `$error` variable.

If the view has no sense without the given resource, it’s the only case where
it’s recommended to use the generic error view:

```php
if (!$db_resource) {
    return Response::notFound('not_found.phtml', [
        'error' => _('This resource doesn’t exist.'),
    ]);
}
```

## Error case #3: Invalid CSRF token

**All the routes that modify user data should verify the CSRF token. If it’s
not valid, it probably means an attacker tries to forge a request on behalf of
an authenticated user.**

If the route accepts both `GET` and `POST` requests, the default response
should be a `bad request` HTTP response returning the current `GET` view:

```php
$csrf = new \Minz\CSRF();
if (!$csrf->validateToken($request->param('csrf'))) {
    return Response::badRequest('get view pointer', [
        'error' => _('A security verification failed: you should retry to submit the form.'),
    ]);
}
```

The error message can be adapted to specific cases. You must verify the view
displays the `$error` variable.

## Error case #4: Access to a resource with invalid state

**In some cases, a user can try to access a route which should not be available
given a specific state.**

For instance, a link that has never been fetched should not be accessed via the
`show` action. In these cases, the default response should be redirecting to
the route that will correctly handle the resource state. In our
never-fetched-link example, we redirect to the `fetch` action:

```php
if (!$link->fetched_at) {
    return Response::redirect('show fetch link', [
        'id' => $link->id,
    ]);
}
```

## Error case #5: Receiving invalid data

**We always must take care of verifying users’ data validity to avoid
incoherent data in the database.**

The most common situation is when trying to create or update a resource with
invalid data. The `\Minz\Model` class inherited by the app models provides a
useful `validate()` method which returns errors based on the class `PROPERTIES`
constant:

```php
$errors = $resource->validate();
if ($errors) {
    return Response::badRequest('get view pointer', [
        'errors' => $errors,
    ]);
}
```

Note that you might want to override the `validate()` method in the model to
localize the errors and simplify the `errors` structure.

The data can also be tested directly and error set explicitely if it cannot be
`validate`d by the model. For instance:

```php
if (!$user->verifyPassword($password)) {
    return Response::badRequest('sessions/new.phtml', [
        'errors' => [
            'password_hash' => _('The password is incorrect.'),
        ],
    ]);
}
```

## The `from` case

**In some cases, a route can accept a `from` parameter. In this case, the
parameter takes priority.** This parameter should only be accepted for
`POST`-only routes as a way to bypass generic error pages (see below). For
instance, if a `redirect_to` redirection is expected, its value should come
from the `from` parameter:

```php
$from = $request->param('from', \Minz\Url::for('the current route'));
return Response::redirect('login', [
    'redirect_to' => $from,
]);
```

Or if an error is expected, you should store the error message as a `Flash`
variable and redirect to the `from` parameter:

```php
utils\Flash::set('error', _('A problem happened.'));
return Response::found($from);
```

You should take care to display the error on the destination page though.

This is useful to avoid letting a user on a route which doesn’t accept a `GET`
request: if she refreshes the page, a not found error would then appear.

## Generic error views

**In cases of routes accepting only `POST` requests and without accepting a
`from` parameter, we should default to generic errors views**, which are:
`bad_request.phtml`, `not_found.phtml` and `unauthorized.phtml`. They accept an
`error` parameter if you need to override the default error message. For
instance:

```php
return Response::badRequest('bad_request.phtml', [
    'error' => _('This resource cannot be accessed for X reason.'),
]);
```

**Please note this solution should be avoided as much as possible.**
