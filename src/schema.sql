CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE TABLE jobs (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    perform_at TIMESTAMPTZ NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    args JSON NOT NULL DEFAULT '{}',
    frequency TEXT NOT NULL DEFAULT '',
    queue TEXT NOT NULL DEFAULT 'default',
    locked_at TIMESTAMPTZ,
    number_attempts BIGINT NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL DEFAULT '',
    failed_at TIMESTAMPTZ
);

CREATE TABLE locks (
    key TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    expired_at TIMESTAMPTZ NOT NULL
);

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
    avatar_filename TEXT,
    autoload_modal TEXT NOT NULL DEFAULT '',
    option_compact_mode BOOLEAN NOT NULL DEFAULT false,
    accept_contact BOOLEAN NOT NULL DEFAULT false,

    validated_at TIMESTAMPTZ,
    validation_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE,
    reset_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE,

    last_activity_at TIMESTAMPTZ NOT NULL DEFAULT date_trunc('second', NOW()),
    deletion_notified_at TIMESTAMPTZ,

    subscription_account_id TEXT,
    subscription_expired_at TIMESTAMPTZ
        NOT NULL
        DEFAULT date_trunc('second', NOW() + INTERVAL '1 month')
);

CREATE INDEX idx_users_email ON users(email);

CREATE TABLE feature_flags (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    type TEXT NOT NULL,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_feature_flags_type_user_id ON feature_flags(type, user_id);

CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    scope TEXT NOT NULL DEFAULT 'browser',
    confirmed_password_at TIMESTAMPTZ,
    name TEXT NOT NULL,
    ip TEXT NOT NULL,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    token TEXT UNIQUE REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_sessions_token ON sessions(token);

CREATE TABLE importations (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    type TEXT NOT NULL,
    status TEXT NOT NULL,
    options JSON NOT NULL,
    error TEXT NOT NULL DEFAULT '',
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE exportations (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    status TEXT NOT NULL,
    error TEXT NOT NULL DEFAULT '',
    filepath TEXT NOT NULL DEFAULT '',
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE fetch_logs (
    id BIGSERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    url TEXT NOT NULL,
    host TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'link',
    ip TEXT
);

CREATE INDEX idx_fetch_logs_host_created_at ON fetch_logs(host, created_at);
CREATE INDEX idx_fetch_logs_created_at ON fetch_logs(created_at);

CREATE TABLE groups (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    name TEXT NOT NULL,
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_groups_user_id_name ON groups(user_id, name);

CREATE TABLE collections (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    locked_at TIMESTAMPTZ,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    type TEXT NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT false,
    image_filename TEXT,
    image_fetched_at TIMESTAMPTZ,
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    group_id TEXT REFERENCES groups ON DELETE SET NULL ON UPDATE CASCADE,
    publication_frequency_per_year INTEGER NOT NULL DEFAULT 0,

    feed_url TEXT,
    feed_type TEXT,
    feed_site_url TEXT,
    feed_last_hash TEXT,
    feed_fetched_code INTEGER NOT NULL DEFAULT 0,
    feed_fetched_at TIMESTAMPTZ,
    feed_fetched_next_at TIMESTAMPTZ,
    feed_fetched_error TEXT
);

CREATE INDEX idx_collections_user_id ON collections(user_id);
CREATE INDEX idx_collections_feed_fetched_next_at ON collections(feed_fetched_next_at);
CREATE INDEX idx_collections_image_filename ON collections(image_filename) WHERE image_filename IS NOT NULL;

CREATE TABLE links (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    is_hidden BOOLEAN NOT NULL DEFAULT false,

    title TEXT NOT NULL,
    url TEXT NOT NULL,
    url_feeds JSON NOT NULL DEFAULT '[]',
    url_replies TEXT NOT NULL DEFAULT '',
    reading_time INTEGER NOT NULL DEFAULT 0,
    image_filename TEXT,
    tags JSONB NOT NULL DEFAULT '[]',

    to_be_fetched BOOLEAN NOT NULL DEFAULT true,
    fetched_at TIMESTAMPTZ,
    fetched_code INTEGER NOT NULL DEFAULT 0,
    fetched_error TEXT,
    fetched_count INTEGER NOT NULL DEFAULT 0,
    fetched_retry_at TIMESTAMPTZ DEFAULT NULL,

    feed_entry_id TEXT,

    source_type TEXT NOT NULL DEFAULT '',
    source_resource_id TEXT,
    group_by_source BOOLEAN NOT NULL DEFAULT false,

    search_index TSVECTOR GENERATED ALWAYS AS (to_tsvector('french', title || ' ' || url)) STORED,
    url_hash TEXT GENERATED ALWAYS AS (encode(digest(url, 'sha256'), 'hex')) STORED,

    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_links_user_id_url_hash ON links USING btree(user_id, url_hash);
CREATE INDEX idx_links_url_hash ON links USING hash(url_hash);
CREATE INDEX idx_links_url ON links USING gin (url gin_trgm_ops);
CREATE INDEX idx_links_to_be_fetched ON links(to_be_fetched) WHERE to_be_fetched = true;
CREATE INDEX idx_links_fetched_at ON links(fetched_at) WHERE fetched_at IS NULL;
CREATE INDEX idx_links_fetched_retry_at ON links(fetched_retry_at) WHERE fetched_retry_at IS NOT NULL;
CREATE INDEX idx_links_image_filename ON links(image_filename) WHERE image_filename IS NOT NULL;
CREATE INDEX idx_links_search ON links USING GIN (search_index);
CREATE INDEX idx_links_tags ON links USING GIN (tags);

CREATE TABLE links_to_collections (
    id BIGSERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
    collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_links_to_collections ON links_to_collections(link_id, collection_id);
CREATE INDEX idx_links_to_collections_collection_id ON links_to_collections(collection_id);
CREATE INDEX idx_links_to_collections_created_at ON links_to_collections(created_at);

CREATE TABLE followed_collections (
    id BIGSERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    time_filter TEXT NOT NULL DEFAULT 'normal',
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE,
    group_id TEXT REFERENCES groups ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_followed_collections ON followed_collections(user_id, collection_id);

CREATE TABLE collection_shares (
    id BIGSERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    type TEXT NOT NULL,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
    collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_collection_shares ON collection_shares(user_id, collection_id);

CREATE TABLE notes (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    content TEXT NOT NULL,
    link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
    user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_notes_link_id ON notes(link_id);

CREATE TABLE topics (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    label TEXT NOT NULL,
    image_filename TEXT
);

CREATE INDEX idx_topics_image_filename ON topics(image_filename) WHERE image_filename IS NOT NULL;

CREATE TABLE collections_to_topics (
    id SERIAL PRIMARY KEY,
    collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE,
    topic_id TEXT REFERENCES topics ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX idx_collections_to_topics ON collections_to_topics(collection_id, topic_id);
CREATE INDEX idx_collections_to_topics_topic_id ON collections_to_topics(topic_id);

CREATE TABLE mastodon_servers (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,

    host TEXT NOT NULL,
    client_id TEXT NOT NULL,
    client_secret TEXT NOT NULL
);

CREATE TABLE mastodon_accounts (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,

    username TEXT NOT NULL,
    access_token TEXT NOT NULL,
    options JSON NOT NULL,

    mastodon_server_id INT NOT NULL REFERENCES mastodon_servers ON DELETE CASCADE ON UPDATE CASCADE,
    user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE mastodon_statuses (
    id TEXT PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,

    content TEXT NOT NULL,
    status_id TEXT NOT NULL,
    posted_at TIMESTAMPTZ,

    mastodon_account_id INT REFERENCES mastodon_accounts ON DELETE CASCADE ON UPDATE CASCADE,
    reply_to_id TEXT REFERENCES mastodon_statuses ON DELETE CASCADE ON UPDATE CASCADE,
    link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
    note_id TEXT REFERENCES notes ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_mastodon_statuses_posted_at ON mastodon_statuses(posted_at) WHERE posted_at IS NULL;
CREATE INDEX idx_mastodon_statuses_mastodon_account_id ON mastodon_statuses(mastodon_account_id);
CREATE INDEX idx_mastodon_statuses_reply_to_id ON mastodon_statuses(reply_to_id);
CREATE INDEX idx_mastodon_statuses_link_id ON mastodon_statuses(link_id);
CREATE INDEX idx_mastodon_statuses_note_id ON mastodon_statuses(note_id);
