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
    csrf TEXT NOT NULL DEFAULT '',
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

CREATE TABLE collections (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    type TEXT NOT NULL,
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_collections_user_id ON collections(user_id);

CREATE TABLE links (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT false,
    reading_time INTEGER NOT NULL DEFAULT 0,
    fetched_at TIMESTAMPTZ,
    fetched_code INTEGER NOT NULL DEFAULT 0,
    fetched_error TEXT,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_links_user_id_url ON links(user_id, url);

CREATE TABLE links_to_collections (
    id SERIAL PRIMARY KEY,
    link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
    collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_links_to_collections ON links_to_collections(link_id, collection_id);
CREATE INDEX idx_links_to_collections_collection_id ON links_to_collections(collection_id);

CREATE TABLE messages (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    content TEXT NOT NULL,
    link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_messages_link_id ON messages(link_id);
