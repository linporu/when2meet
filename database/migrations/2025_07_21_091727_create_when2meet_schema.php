<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Events table
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('hash', 8)->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // Event datetime table
        Schema::create('event_datetime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestampTz('created_at')->useCurrent();
        });

        // Event participants table
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('password', 255)->nullable();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->timestampTz('created_at')->useCurrent();

            // Unique constraint for participant name per event
            $table->unique(['name', 'event_id'], 'idx_unique_participant_name_per_event');
        });

        // Available datetime table
        Schema::create('available_datetime', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('event_participants')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_datetime');
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('event_datetime');
        Schema::dropIfExists('events');
    }
};
