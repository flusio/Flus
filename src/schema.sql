CREATE TABLE users (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    email TEXT UNIQUE NOT NULL,
    username TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    validated_at TIMESTAMPTZ
);

CREATE TABLE tokens (
    token TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    expired_at TIMESTAMPTZ NOT NULL,
    type TEXT NOT NULL,
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    invalidated_at TIMESTAMPTZ
);
