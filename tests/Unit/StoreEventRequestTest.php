<?php

use App\Http\Requests\StoreEventRequest;
use Illuminate\Support\Facades\Validator;

describe('StoreEventRequest Validation', function () {
    describe('Required Fields Validation (Core Business Logic)', function () {
        it('validates all required fields are present', function () {
            $rules = (new StoreEventRequest)->rules();

            $validator = Validator::make([], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('event_name'))->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();
            expect($validator->errors()->has('start_time'))->toBeTrue();
            expect($validator->errors()->has('end_time'))->toBeTrue();
            expect($validator->errors()->has('timezone'))->toBeTrue();
        });

        it('validates event name is required and must be string', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test missing event_name
            $validator1 = Validator::make(['date' => '2025-02-15'], $rules);
            expect($validator1->errors()->has('event_name'))->toBeTrue();

            // Test non-string event_name
            $validator2 = Validator::make(['event_name' => 123], $rules);
            expect($validator2->errors()->has('event_name'))->toBeTrue();

            // Test valid event_name
            $validData = [
                'event_name' => 'Valid Meeting',
                'date' => '2025-02-15',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];
            $validator3 = Validator::make($validData, $rules);
            expect($validator3->errors()->has('event_name'))->toBeFalse();
        });

        it('validates date is required and must be valid date format', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test invalid date format
            $invalidData = [
                'event_name' => 'Test Meeting',
                'date' => 'invalid-date',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($invalidData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();
        });

        it('validates time fields are required and in correct format', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test invalid time formats
            $invalidTimeData = [
                'event_name' => 'Test Meeting',
                'date' => '2025-02-15',
                'start_time' => 'invalid-time',
                'end_time' => '25:00', // Invalid hour
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($invalidTimeData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('start_time'))->toBeTrue();
            expect($validator->errors()->has('end_time'))->toBeTrue();
        });
    });

    describe('Business Logic Validation (User Experience Priority)', function () {
        it('validates date must be today or in the future', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test past date (should fail)
            $pastDateData = [
                'event_name' => 'Past Meeting',
                'date' => '2020-01-01',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($pastDateData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();

            // Test today's date (should pass)
            $todayData = [
                'event_name' => 'Today Meeting',
                'date' => today()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator2 = Validator::make($todayData, $rules);
            expect($validator2->errors()->has('date'))->toBeFalse();

            // Test future date (should pass)
            $futureData = [
                'event_name' => 'Future Meeting',
                'date' => today()->addDays(7)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator3 = Validator::make($futureData, $rules);
            expect($validator3->errors()->has('date'))->toBeFalse();
        });

        it('validates date must not exceed year 2200', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test date beyond 2200 (should fail)
            $tooFarFutureData = [
                'event_name' => 'Too Far Future Meeting',
                'date' => '2201-01-01',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($tooFarFutureData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();

            // Test maximum valid date (should pass)
            $maxValidData = [
                'event_name' => 'Max Valid Date Meeting',
                'date' => '2200-12-31',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator2 = Validator::make($maxValidData, $rules);
            expect($validator2->errors()->has('date'))->toBeFalse();
        });

        it('validates end time must be after start time', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test end time before start time (should fail)
            $invalidTimeData = [
                'event_name' => 'Invalid Time Meeting',
                'date' => '2025-02-15',
                'start_time' => '17:00',
                'end_time' => '09:00', // End before start
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($invalidTimeData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('end_time'))->toBeTrue();

            // Test same start and end time (should fail)
            $sameTimeData = [
                'event_name' => 'Same Time Meeting',
                'date' => '2025-02-15',
                'start_time' => '12:00',
                'end_time' => '12:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator2 = Validator::make($sameTimeData, $rules);
            expect($validator2->fails())->toBeTrue();
            expect($validator2->errors()->has('end_time'))->toBeTrue();

            // Test valid time range (should pass)
            $validTimeData = [
                'event_name' => 'Valid Time Meeting',
                'date' => '2025-02-15',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator3 = Validator::make($validTimeData, $rules);
            expect($validator3->errors()->has('end_time'))->toBeFalse();
        });

        it('validates timezone must be from allowed list', function () {
            $rules = (new StoreEventRequest)->rules();

            $allowedTimezones = [
                'Asia/Taipei',
                'Asia/Tokyo',
                'Asia/Shanghai',
                'Asia/Hong_Kong',
                'Asia/Singapore',
                'UTC',
                'America/New_York',
                'America/Los_Angeles',
                'Europe/London',
            ];

            // Test invalid timezone
            $invalidTimezoneData = [
                'event_name' => 'Invalid Timezone Meeting',
                'date' => '2025-02-15',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Invalid/Timezone',
            ];

            $validator = Validator::make($invalidTimezoneData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('timezone'))->toBeTrue();

            // Test all valid timezones
            foreach ($allowedTimezones as $timezone) {
                $validData = [
                    'event_name' => 'Valid Timezone Meeting',
                    'date' => '2025-02-15',
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'timezone' => $timezone,
                ];

                $validator = Validator::make($validData, $rules);
                expect($validator->errors()->has('timezone'))->toBeFalse("Timezone {$timezone} should be valid");
            }
        });
    });

    describe('User Experience - English Error Messages', function () {
        it('provides helpful error messages in English', function () {
            $request = new StoreEventRequest;
            $messages = $request->messages();

            // Test all expected English messages
            expect($messages['event_name.required'])->toBe('Event name is required');
            expect($messages['event_name.max'])->toBe('Event name is too long');
            expect($messages['date.required'])->toBe('Please enter a valid date');
            expect($messages['date.date'])->toBe('Please enter a valid date');
            expect($messages['date.after_or_equal'])->toBe('Please enter a valid date');
            expect($messages['date.before_or_equal'])->toBe('Please enter a valid date');
            expect($messages['start_time.required'])->toBe('Start time is required');
            expect($messages['start_time.date_format'])->toBe('Please enter a valid start time');
            expect($messages['end_time.required'])->toBe('End time is required');
            expect($messages['end_time.date_format'])->toBe('Please enter a valid end time');
            expect($messages['end_time.after'])->toBe('End time must be later than start time');
            expect($messages['timezone.required'])->toBe('Timezone is required');
            expect($messages['timezone.in'])->toBe('Please select a valid timezone');
        });

        it('validates error messages help users understand business rules', function () {
            $rules = (new StoreEventRequest)->rules();
            $messages = (new StoreEventRequest)->messages();

            // Test past date error message is user-friendly
            $pastDateData = [
                'event_name' => 'Test Meeting',
                'date' => '2020-01-01',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($pastDateData, $rules, $messages);
            expect($validator->errors()->get('date')[0])->toBe('Please enter a valid date');

            // Test end time validation message
            $invalidTimeData = [
                'event_name' => 'Test Meeting',
                'date' => '2025-02-15',
                'start_time' => '17:00',
                'end_time' => '09:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator2 = Validator::make($invalidTimeData, $rules, $messages);
            expect($validator2->errors()->get('end_time')[0])->toBe('End time must be later than start time');

            // Test far future date error message
            $farFutureDateData = [
                'event_name' => 'Test Meeting',
                'date' => '2201-01-01',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator3 = Validator::make($farFutureDateData, $rules, $messages);
            expect($validator3->errors()->get('date')[0])->toBe('Please enter a valid date');
        });
    });

    describe('Edge Cases and Boundary Conditions', function () {
        it('accepts minimum valid event name length', function () {
            $rules = (new StoreEventRequest)->rules();

            $minValidData = [
                'event_name' => 'A', // Single character (min:1)
                'date' => '2025-02-15',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($minValidData, $rules);
            expect($validator->errors()->has('event_name'))->toBeFalse();
        });

        it('rejects event names exceeding 255 characters', function () {
            $rules = (new StoreEventRequest)->rules();

            $longName = str_repeat('長', 256); // 256 characters, exceeds max:255

            $invalidData = [
                'event_name' => $longName,
                'date' => '2025-02-15',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $validator = Validator::make($invalidData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('event_name'))->toBeTrue();
        });

        it('handles time format edge cases correctly', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test valid edge time formats
            $validEdgeTimes = [
                ['start_time' => '00:00', 'end_time' => '23:59'], // Day boundaries
                ['start_time' => '09:30', 'end_time' => '17:45'], // Half-hour increments
                ['start_time' => '01:01', 'end_time' => '01:02'], // One minute difference
            ];

            foreach ($validEdgeTimes as $times) {
                $data = [
                    'event_name' => 'Edge Case Meeting',
                    'date' => '2025-02-15',
                    'start_time' => $times['start_time'],
                    'end_time' => $times['end_time'],
                    'timezone' => 'Asia/Taipei',
                ];

                $validator = Validator::make($data, $rules);
                expect($validator->errors()->has('start_time'))->toBeFalse("Start time {$times['start_time']} should be valid");
                expect($validator->errors()->has('end_time'))->toBeFalse("End time {$times['end_time']} should be valid");
            }
        });

        it('validates complex real-world event creation scenarios', function () {
            $rules = (new StoreEventRequest)->rules();

            // Test realistic event data
            $realWorldScenarios = [
                [
                    'event_name' => 'Team Sprint Planning 🚀',
                    'date' => today()->addWeeks(2)->format('Y-m-d'),
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'timezone' => 'Asia/Taipei',
                ],
                [
                    'event_name' => 'Client Meeting - Q1 Review & Planning',
                    'date' => today()->addMonth()->format('Y-m-d'),
                    'start_time' => '14:30',
                    'end_time' => '16:30',
                    'timezone' => 'UTC',
                ],
                [
                    'event_name' => 'All-Hands Company Meeting',
                    'date' => today()->addDays(3)->format('Y-m-d'),
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'timezone' => 'America/New_York',
                ],
            ];

            foreach ($realWorldScenarios as $scenario) {
                $validator = Validator::make($scenario, $rules);
                expect($validator->fails())->toBeFalse("Real-world scenario should be valid: {$scenario['event_name']}");
            }
        });
    });

    describe('Authorization Logic', function () {
        it('always authorizes requests since no authentication required', function () {
            $request = new StoreEventRequest;
            expect($request->authorize())->toBeTrue();
        });
    });
});
