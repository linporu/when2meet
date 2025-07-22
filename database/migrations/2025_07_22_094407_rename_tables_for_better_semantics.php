<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename tables for better semantic naming
        Schema::rename('available_datetime', 'participant_availabilities');
        Schema::rename('event_datetime', 'event_time_slots');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the table renaming
        Schema::rename('participant_availabilities', 'available_datetime');
        Schema::rename('event_time_slots', 'event_datetime');
    }
};
