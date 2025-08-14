<?php

namespace App\Console\Commands;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats {--event-id= : Show stats for specific event ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display GroupAvailability cache statistics and information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 GroupAvailability Cache Statistics');
        $this->line('==========================================');

        // Display cache driver information
        $this->displayCacheDriverInfo();

        if ($eventId = $this->option('event-id')) {
            $this->displayEventCacheStats((int) $eventId);
        } else {
            $this->displayOverallCacheStats();
        }
    }

    protected function displayCacheDriverInfo(): void
    {
        $driver = config('cache.default');
        $supportsTagging = $this->supportsTags();

        $this->table(['Configuration', 'Value'], [
            ['Cache Driver', $driver],
            ['Tags Support', $supportsTagging ? '✅ Yes' : '❌ No'],
            ['Static Slots TTL', '24 hours'],
            ['Dynamic Results TTL', '30 minutes'],
        ]);

        $this->line('');
    }

    protected function displayEventCacheStats(int $eventId): void
    {
        $event = Event::find($eventId);

        if (! $event) {
            $this->error("Event with ID {$eventId} not found");

            return;
        }

        $this->info("📋 Cache Stats for Event: {$event->name} (ID: {$eventId})");

        $service = app(GroupAvailabilityServiceInterface::class);

        $stats = $service->getCacheStats($eventId);

        if (! empty($stats)) {
            $this->table(['Cache Item', 'Status'], [
                ['Static Time Slots', $stats['static_slots_cached'] ? '✅ Cached' : '❌ Not Cached'],
                ['Static Slots Key', $stats['static_slots_key']],
            ]);

            // Show participant count for hash calculation context
            $participantCount = $event->participants()->count();
            $this->line("👥 Participants: {$participantCount}");

            if ($participantCount > 0) {
                $this->line('💡 Dynamic cache keys depend on participant data hash');
            }
        } else {
            $this->warn('Cache service is not enabled or not properly configured');
        }
    }

    protected function displayOverallCacheStats(): void
    {
        $this->info('🔍 Overall Cache Information');

        // Count events with participants
        $eventsWithParticipants = Event::has('participants')->count();
        $totalParticipants = \App\Models\EventParticipant::count();

        $this->table(['Metric', 'Count'], [
            ['Total Events', Event::count()],
            ['Events with Participants', $eventsWithParticipants],
            ['Total Participants', $totalParticipants],
            ['Cache-Eligible Events', $eventsWithParticipants],
        ]);

        $this->line('');
        $this->line('💡 Use --event-id=X to see detailed cache stats for a specific event');

        if ($eventsWithParticipants > 0) {
            $this->line('📋 Recent events with participants:');
            Event::has('participants')
                ->latest()
                ->take(5)
                ->get(['id', 'name'])
                ->each(function ($event) {
                    $participantCount = $event->participants()->count();
                    $this->line("   • ID {$event->id}: {$event->name} ({$participantCount} participants)");
                });
        }
    }

    protected function supportsTags(): bool
    {
        try {
            return method_exists(Cache::getStore(), 'supportsTags') &&
                   Cache::getStore()->supportsTags();
        } catch (\Exception) {
            return false;
        }
    }
}
