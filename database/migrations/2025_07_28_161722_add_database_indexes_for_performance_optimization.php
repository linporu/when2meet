<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // High Priority Indexes - Critical for GroupAvailabilityService performance

        // 1. Index on participant_availabilities.date for JOIN operations
        Schema::table('participant_availabilities', function (Blueprint $table) {
            $table->index('date', 'idx_participant_availabilities_date');
        });

        // 2. Composite index on event_time_slots for ORDER BY optimization
        Schema::table('event_time_slots', function (Blueprint $table) {
            $table->index(['date', 'start_time'], 'idx_event_time_slots_date_start_time');
        });

        // 3. Composite index on participant_availabilities for JOIN optimization
        Schema::table('participant_availabilities', function (Blueprint $table) {
            $table->index(['participant_id', 'date'], 'idx_participant_availabilities_participant_date');
        });

        // Medium Priority Indexes - For time range queries and scopes

        // 4. Composite index for date and start_time range queries
        Schema::table('participant_availabilities', function (Blueprint $table) {
            $table->index(['date', 'start_time'], 'idx_participant_availabilities_date_start_time');
        });

        // 5. Composite index for date and end_time range queries
        Schema::table('participant_availabilities', function (Blueprint $table) {
            $table->index(['date', 'end_time'], 'idx_participant_availabilities_date_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('participant_availabilities', function (Blueprint $table) {
            $table->dropIndex('idx_participant_availabilities_date_end_time');
            $table->dropIndex('idx_participant_availabilities_date_start_time');
            $table->dropIndex('idx_participant_availabilities_participant_date');
            $table->dropIndex('idx_participant_availabilities_date');
        });

        Schema::table('event_time_slots', function (Blueprint $table) {
            $table->dropIndex('idx_event_time_slots_date_start_time');
        });
    }
};
