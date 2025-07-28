<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;
use App\Services\GroupAvailabilityBenchmark;
use App\Services\GroupAvailabilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PerformanceTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:performance {--scenario=all : Test scenario (small|medium|large|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performance test for GroupAvailabilityService optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scenario = $this->option('scenario');

        $this->info('🚀 GroupAvailabilityService Performance Test');
        $this->info('================================================');

        $scenarios = match ($scenario) {
            'small' => ['small'],
            'medium' => ['medium'],
            'large' => ['large'],
            'all' => ['small', 'medium', 'large'],
            default => ['small', 'medium', 'large']
        };

        foreach ($scenarios as $scenarioName) {
            $this->runScenario($scenarioName);
        }

        return Command::SUCCESS;
    }

    private function runScenario(string $scenario): void
    {
        $config = $this->getScenarioConfig($scenario);

        $this->newLine();
        $this->info("📊 Running {$scenario} scenario: {$config['participants']} participants, {$config['days']} days, {$config['hours_per_day']} hours per day");
        $this->info('----------------------------------------');

        // Create test data
        $event = $this->createTestData($config);

        // Test optimized version
        $optimizedResult = $this->testOptimizedVersion($event);

        // Test original version
        $originalResult = $this->testOriginalVersion($event);

        // Display results
        $this->displayResults($scenario, $originalResult, $optimizedResult);

        // Cleanup
        $event->delete();
    }

    private function getScenarioConfig(string $scenario): array
    {
        return match ($scenario) {
            'small' => ['participants' => 5, 'days' => 3, 'hours_per_day' => 8],
            'medium' => ['participants' => 50, 'days' => 5, 'hours_per_day' => 10],
            'large' => ['participants' => 500, 'days' => 7, 'hours_per_day' => 12],
            default => ['participants' => 5, 'days' => 3, 'hours_per_day' => 8], // Default to small
        };
    }

    private function createTestData(array $config): Event
    {
        DB::beginTransaction();

        // Create event
        $event = Event::create(['name' => 'Performance Test Event']);

        // Create time slots
        for ($day = 0; $day < $config['days']; $day++) {
            $date = now()->addDays($day)->format('Y-m-d');
            EventTimeSlot::create([
                'event_id' => $event->id,
                'date' => $date,
                'start_time' => '09:00:00',
                'end_time' => sprintf('%02d:00:00', 9 + $config['hours_per_day']),
            ]);
        }

        // Create participants with varying availability patterns
        for ($i = 1; $i <= $config['participants']; $i++) {
            $participant = EventParticipant::create([
                'event_id' => $event->id,
                'name' => "Participant {$i}",
            ]);

            // Create availability (60-80% coverage)
            for ($day = 0; $day < $config['days']; $day++) {
                if (rand(1, 100) <= 70) { // 70% chance of availability per day
                    $date = now()->addDays($day)->format('Y-m-d');
                    $startHour = rand(9, 12); // Random start time
                    $duration = rand(2, 6); // Random duration 2-6 hours

                    ParticipantAvailability::create([
                        'event_id' => $event->id,
                        'participant_id' => $participant->id,
                        'date' => $date,
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', min($startHour + $duration, 9 + $config['hours_per_day'])),
                    ]);
                }
            }
        }

        DB::commit();

        return $event;
    }

    private function testOptimizedVersion(Event $event): array
    {
        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_peak_usage(true);

        $service = new GroupAvailabilityService;
        $result = $service->calculateGroupAvailability($event);

        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'execution_time' => ($endTime - $startTime) * 1000, // milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'query_count' => count($queries),
            'result_count' => count($result),
        ];
    }

    private function testOriginalVersion(Event $event): array
    {
        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_peak_usage(true);

        $benchmark = new GroupAvailabilityBenchmark;
        $result = $benchmark->calculateGroupAvailabilityOriginal($event);

        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'execution_time' => ($endTime - $startTime) * 1000, // milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'query_count' => count($queries),
            'result_count' => count($result),
        ];
    }

    private function displayResults(string $scenario, array $original, array $optimized): void
    {
        $this->table(
            ['Metric', 'Original', 'Optimized', 'Improvement'],
            [
                [
                    'Execution Time (ms)',
                    number_format($original['execution_time'], 2),
                    number_format($optimized['execution_time'], 2),
                    $original['execution_time'] > 0 ?
                        number_format((($original['execution_time'] - $optimized['execution_time']) / $original['execution_time']) * 100, 1).'%' :
                        'N/A',
                ],
                [
                    'Memory Usage (bytes)',
                    number_format($original['memory_usage']),
                    number_format($optimized['memory_usage']),
                    $original['memory_usage'] > 0 ?
                        number_format((($original['memory_usage'] - $optimized['memory_usage']) / $original['memory_usage']) * 100, 1).'%' :
                        'N/A',
                ],
                [
                    'Query Count',
                    $original['query_count'],
                    $optimized['query_count'],
                    $original['query_count'] > 0 ?
                        number_format((($original['query_count'] - $optimized['query_count']) / $original['query_count']) * 100, 1).'%' :
                        'N/A',
                ],
                [
                    'Result Count',
                    $original['result_count'],
                    $optimized['result_count'],
                    $original['result_count'] == $optimized['result_count'] ? '✅ Match' : '❌ Mismatch',
                ],
            ]
        );
    }
}
