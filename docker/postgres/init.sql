-- ============================================
-- PostgreSQL Initialization Script
-- ============================================

-- Enable UUID extension (if needed for future features)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable pg_trgm for better text search performance
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Create database user if not exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_user WHERE usename = 'when2meet_user') THEN
        CREATE USER when2meet_user WITH PASSWORD 'when2meet_password';
    END IF;
END
$$;

-- Grant necessary privileges
GRANT ALL PRIVILEGES ON DATABASE when2meet TO when2meet_user;

-- Set default timezone
SET timezone = 'UTC';
