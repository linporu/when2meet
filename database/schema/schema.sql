-- When2Meet Database Schema (PostgreSQL) - Simplified Design

-- Events table (simplified)
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    hash VARCHAR(8) UNIQUE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Event datetime table (contains date and time information)
CREATE TABLE event_datetime (
    id SERIAL PRIMARY KEY,
    event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Event participants table (event-specific participants)
CREATE TABLE event_participants (
    id SERIAL PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    password VARCHAR(255),
    event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Available datetime table (participant availability with same structure as event_datetime)
CREATE TABLE available_datetime (
    id SERIAL PRIMARY KEY,
    event_id INTEGER REFERENCES events(id) ON DELETE CASCADE,
    participant_id INTEGER REFERENCES event_participants(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Unique constraint to prevent duplicate participant names within the same event
CREATE UNIQUE INDEX idx_unique_participant_name_per_event ON event_participants(name, event_id);

