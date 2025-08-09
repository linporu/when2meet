<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Event name validation: max:255 characters for database field limitation
            'event_name' => 'required|string|max:255|min:1',
            // Date validation: must be today or future, max year 2200
            'date' => 'required|date|after_or_equal:today|before_or_equal:2200-12-31',
            // Time validation: H:i format required (24-hour format)
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            // Timezone validation: limited to supported timezones
            'timezone' => [
                'required',
                'string',
                Rule::in([
                    'Asia/Taipei',
                    'Asia/Tokyo',
                    'Asia/Shanghai',
                    'Asia/Hong_Kong',
                    'Asia/Singapore',
                    'UTC',
                    'America/New_York',
                    'America/Los_Angeles',
                    'Europe/London',
                ]),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_name.required' => 'Event name is required',
            'event_name.max' => 'Event name is too long',
            'date.required' => 'Please enter a valid date',
            'date.date' => 'Please enter a valid date',
            'date.after_or_equal' => 'Please enter a valid date',
            'date.before_or_equal' => 'Please enter a valid date',
            'start_time.required' => 'Start time is required',
            'start_time.date_format' => 'Please enter a valid start time',
            'end_time.required' => 'End time is required',
            'end_time.date_format' => 'Please enter a valid end time',
            'end_time.after' => 'End time must be later than start time',
            'timezone.required' => 'Timezone is required',
            'timezone.in' => 'Please select a valid timezone',
        ];
    }
}
