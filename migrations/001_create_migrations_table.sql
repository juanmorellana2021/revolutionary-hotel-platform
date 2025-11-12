-- Migration tracking table
-- Run this first to enable migration tracking

CREATE TABLE IF NOT EXISTS migrations_log (
    id SERIAL PRIMARY KEY,
    migration_name VARCHAR(255) UNIQUE NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'success',
    error_message TEXT
);

-- Index for faster lookups
CREATE INDEX IF NOT EXISTS idx_migrations_name ON migrations_log(migration_name);
CREATE INDEX IF NOT EXISTS idx_migrations_applied ON migrations_log(applied_at);

-- Add notes column if doesn't exist
ALTER TABLE migrations_log ADD COLUMN IF NOT EXISTS notes TEXT;

COMMENT ON TABLE migrations_log IS 'Tracks which database migrations have been applied';
