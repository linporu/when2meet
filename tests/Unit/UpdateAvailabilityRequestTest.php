<?php

use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\Validator;

describe('UpdateAvailabilityRequest Validation - Test God Level', function () {
    describe('Authorization Logic (Cross-Event Access Prevention)', function () {
        it('authorizes participant access within same event', function () {
            // Arrange
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Create a mock request with proper route parameters
            $request = \Mockery::mock(UpdateAvailabilityRequest::class)->makePartial();
            $request->shouldReceive('route')->with('event')->andReturn($event);
            $request->shouldReceive('route')->with('participant')->andReturn($participant);

            // Act & Assert
            expect($request->authorize())->toBeTrue();
        });

        it('denies participant access across different events', function () {
            // Arrange
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event2)->create();

            // Create a mock request with participant from different event
            $request = \Mockery::mock(UpdateAvailabilityRequest::class)->makePartial();
            $request->shouldReceive('route')->with('event')->andReturn($event1);
            $request->shouldReceive('route')->with('participant')->andReturn($participant);

            // Act & Assert
            expect($request->authorize())->toBeFalse();
        });
    });

    describe('Basic Validation Rules', function () {
        it('validates required participant name', function () {
            $rules = (new UpdateAvailabilityRequest)->rules();

            $validator = Validator::make([], $rules);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('participant_name'))->toBeTrue();
        });

        it('validates participant name string type and length', function () {
            $rules = (new UpdateAvailabilityRequest)->rules();

            // Test string type validation
            $validator1 = Validator::make([
                'participant_name' => 123, // Not a string
            ], $rules);
            expect($validator1->fails())->toBeTrue();

            // Test max length validation
            $longName = str_repeat('a', 256);
            $validator2 = Validator::make([
                'participant_name' => $longName,
            ], $rules);
            expect($validator2->fails())->toBeTrue();

            // Test valid name
            $validator3 = Validator::make([
                'participant_name' => 'Valid Name',
            ], $rules);
            expect($validator3->errors()->has('participant_name'))->toBeFalse();
        });

        it('validates availability array structure', function () {
            $rules = (new UpdateAvailabilityRequest)->rules();

            $validData = [
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ],
            ];

            $validator = Validator::make($validData, $rules);
            expect($validator->fails())->toBeFalse();
        });

        it('validates time format in availability data', function () {
            $rules = (new UpdateAvailabilityRequest)->rules();

            // Invalid time format
            $invalidData = [
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => 'invalid-time', 'end_time' => '12:00'],
                    ],
                ],
            ];

            $validator = Validator::make($invalidData, $rules);
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('availability.2025-01-15.0.start_time'))->toBeTrue();
        });
    });

    describe('Custom Error Messages (User Experience)', function () {
        it('provides helpful error messages in Traditional Chinese', function () {
            $request = new UpdateAvailabilityRequest;
            $messages = $request->messages();

            expect($messages['participant_name.required'])->toBe('Please enter your name.');
            expect($messages['participant_name.string'])->toBe('Your name must be a valid text.');
            expect($messages['participant_name.max'])->toBe('Your name cannot exceed 255 characters.');
            expect($messages['availability.*.*.start_time.date_format'])->toBe('Start time must be in HH:MM format.');
            expect($messages['availability.*.*.end_time.date_format'])->toBe('End time must be in HH:MM format.');
        });
    });

    describe('Time Range Logic Validation (Business Logic)', function () {
        it('validates that both start and end time must be provided', function () {
            $request = new UpdateAvailabilityRequest;
            $request->replace([
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '09:00', 'end_time' => ''], // Missing end time
                        ['start_time' => '', 'end_time' => '12:00'],  // Missing start time
                    ],
                ],
            ]);

            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('availability.2025-01-15.0'))->toBeTrue();
            expect($validator->errors()->has('availability.2025-01-15.1'))->toBeTrue();
            expect($validator->errors()->get('availability.2025-01-15.0')[0])->toBe('Both start time and end time must be selected.');
        });

        it('validates that end time must be later than start time', function () {
            $request = new UpdateAvailabilityRequest;
            $request->replace([
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '14:00', 'end_time' => '09:00'], // End before start
                        ['start_time' => '12:00', 'end_time' => '12:00'],  // Same time
                    ],
                ],
            ]);

            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('availability.2025-01-15.0'))->toBeTrue();
            expect($validator->errors()->has('availability.2025-01-15.1'))->toBeTrue();
            expect($validator->errors()->get('availability.2025-01-15.0')[0])->toBe('End time must be later than start time.');
        });

        it('allows valid time ranges', function () {
            $request = new UpdateAvailabilityRequest;
            $request->replace([
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                    '2025-01-16' => [
                        ['start_time' => '10:00', 'end_time' => '15:00'],
                    ],
                ],
            ]);

            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeFalse();
        });
    });

    describe('Formatted Availability Data Processing', function () {
        it('formats availability data correctly for storage', function () {
            $availabilityData = [
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                    '2025-01-16' => [
                        ['start_time' => '10:00', 'end_time' => '15:00'],
                    ],
                ],
            ];

            // Mock the request to return validated data
            $request = \Mockery::mock(UpdateAvailabilityRequest::class)->makePartial();
            $request->shouldReceive('validated')->andReturn($availabilityData);

            $formattedData = $request->getFormattedAvailability();

            expect($formattedData)->toHaveCount(3);
            expect($formattedData[0])->toBe([
                'date' => '2025-01-15',
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]);
            expect($formattedData[1])->toBe([
                'date' => '2025-01-15',
                'start_time' => '14:00',
                'end_time' => '17:00',
            ]);
            expect($formattedData[2])->toBe([
                'date' => '2025-01-16',
                'start_time' => '10:00',
                'end_time' => '15:00',
            ]);
        });

        it('filters out empty time ranges in formatted data', function () {
            $availabilityData = [
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'], // Valid
                        ['start_time' => '', 'end_time' => ''],           // Empty - should be filtered
                        ['start_time' => '14:00', 'end_time' => '17:00'],  // Valid
                    ],
                ],
            ];

            $request = \Mockery::mock(UpdateAvailabilityRequest::class)->makePartial();
            $request->shouldReceive('validated')->andReturn($availabilityData);

            $formattedData = $request->getFormattedAvailability();

            // Should only have 2 entries, empty one filtered out
            expect($formattedData)->toHaveCount(2);
            expect($formattedData[0]['start_time'])->toBe('09:00');
            expect($formattedData[1]['start_time'])->toBe('14:00');
        });

        it('handles empty availability array', function () {
            $request = \Mockery::mock(UpdateAvailabilityRequest::class)->makePartial();
            $request->shouldReceive('validated')->andReturn(['availability' => []]);

            $formattedData = $request->getFormattedAvailability();

            expect($formattedData)->toBeArray();
            expect($formattedData)->toHaveCount(0);
        });
    });

    describe('Edge Cases and User Experience', function () {
        it('handles complex nested availability structures gracefully', function () {
            $request = new UpdateAvailabilityRequest;
            $complexData = [
                'participant_name' => 'Power User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '06:00', 'end_time' => '08:00'],
                        ['start_time' => '09:30', 'end_time' => '11:45'],
                        ['start_time' => '13:15', 'end_time' => '16:30'],
                        ['start_time' => '18:00', 'end_time' => '22:00'],
                    ],
                    '2025-01-16' => [
                        ['start_time' => '08:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '18:00'],
                    ],
                    '2025-01-17' => [
                        ['start_time' => '10:00', 'end_time' => '16:00'],
                    ],
                ],
            ];

            $validator = Validator::make($complexData, $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeFalse();
        });

        it('provides clear validation errors for business rule violations', function () {
            $request = new UpdateAvailabilityRequest;
            $request->replace([
                'participant_name' => 'Test User',
                'availability' => [
                    '2025-01-15' => [
                        ['start_time' => '17:00', 'end_time' => '09:00'], // Clearly invalid
                    ],
                ],
            ]);

            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue();
            $errors = $validator->errors();
            expect($errors->get('availability.2025-01-15.0')[0])->toBe('End time must be later than start time.');
        });
    });
});
