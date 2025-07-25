<?php

use App\Http\Requests\JoinEventRequest;
use Illuminate\Support\Facades\Validator;

test('getValidatedAvailability converts data structure correctly', function () {
    // Create a mock of JoinEventRequest
    $request = \Mockery::mock(JoinEventRequest::class)->makePartial();

    // Mock the validated method to return valid data (empty ranges should not pass validation)
    $request->shouldReceive('validated')->andReturn([
        'participant_name' => 'John Doe',
        'availability' => [
            '2025-01-15' => [
                0 => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
                1 => [
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                ],
            ],
            '2025-01-16' => [
                0 => [
                    'start_time' => '10:00',
                    'end_time' => '15:00',
                ],
            ],
        ],
    ]);

    $validatedAvailability = $request->getValidatedAvailability();

    expect($validatedAvailability)->toHaveCount(3);
    expect($validatedAvailability[0])->toBe([
        'date' => '2025-01-15',
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);
    expect($validatedAvailability[1])->toBe([
        'date' => '2025-01-15',
        'start_time' => '14:00',
        'end_time' => '17:00',
    ]);
    expect($validatedAvailability[2])->toBe([
        'date' => '2025-01-16',
        'start_time' => '10:00',
        'end_time' => '15:00',
    ]);
});

test('validation rules are correct', function () {
    $request = new JoinEventRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKey('participant_name');
    expect($rules)->toHaveKey('availability');
    expect($rules['participant_name'])->toBe('required|string|max:255');
    expect($rules['availability'])->toBe('array');
});

test('custom validation messages are provided', function () {
    $request = new JoinEventRequest;

    $messages = $request->messages();

    expect($messages)->toHaveKey('participant_name.required');
    expect($messages['participant_name.required'])->toBe('Please enter your name.');
});

test('time range validation works with validator', function () {
    $data = [
        'participant_name' => 'John Doe',
        'availability' => [
            '2025-01-15' => [
                0 => [
                    'start_time' => '12:00',
                    'end_time' => '09:00', // Invalid: end before start
                ],
            ],
        ],
    ];

    $request = new JoinEventRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    // Apply custom validation
    $request->replace($data);
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('availability.2025-01-15.0'))->toBeTrue();
});

test('empty availability data is handled correctly', function () {
    // Create a mock of JoinEventRequest
    $request = \Mockery::mock(JoinEventRequest::class)->makePartial();

    // Mock the validated method
    $request->shouldReceive('validated')->andReturn([
        'participant_name' => 'John Doe',
        'availability' => [],
    ]);

    $validatedAvailability = $request->getValidatedAvailability();

    expect($validatedAvailability)->toBeEmpty();
});

test('multiple valid time ranges are processed correctly', function () {
    // Create a mock of JoinEventRequest
    $request = \Mockery::mock(JoinEventRequest::class)->makePartial();

    // Mock the validated method
    $request->shouldReceive('validated')->andReturn([
        'participant_name' => 'John Doe',
        'availability' => [
            '2025-01-15' => [
                0 => [
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
                1 => [
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                ],
            ],
            '2025-01-16' => [
                0 => [
                    'start_time' => '10:00',
                    'end_time' => '15:00',
                ],
            ],
        ],
    ]);

    $validatedAvailability = $request->getValidatedAvailability();

    expect($validatedAvailability)->toHaveCount(3);
    expect($validatedAvailability[0]['date'])->toBe('2025-01-15');
    expect($validatedAvailability[1]['date'])->toBe('2025-01-15');
    expect($validatedAvailability[2]['date'])->toBe('2025-01-16');
});
