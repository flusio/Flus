CREATE TABLE tokens (
    token TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    expired_at TIMESTAMPTZ NOT NULL,
    invalidated_at TIMESTAMPTZ
);

CREATE TABLE users (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    email TEXT UNIQUE NOT NULL,
    username TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    locale TEXT NOT NULL DEFAULT 'en_GB',
    validated_at TIMESTAMPTZ,
    validation_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_users_email ON users(email);

CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    name TEXT NOT NULL,
    ip TEXT NOT NULL,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    token TEXT UNIQUE REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_sessions_token ON sessions(token);
