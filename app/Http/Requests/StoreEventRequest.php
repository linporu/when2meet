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
            'event_name' => [
                'required',
                'string',
                'max:255',
                'min:1',
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
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
            'event_name.max' => 'Event name cannot exceed 255 characters',
            'date.required' => 'Please enter a valid date',
            'date.date' => 'Please enter a valid date',
            'date.after_or_equal' => 'Please enter a valid date',
            'start_time.required' => 'Start time is required',
            'start_time.date_format' => 'Start time must be in HH:MM format',
            'end_time.required' => 'End time is required',
            'end_time.date_format' => 'End time must be in HH:MM format',
            'end_time.after' => 'End time must be later than start time',
            'timezone.required' => 'Timezone is required',
            'timezone.in' => 'Please select a valid timezone',
        ];
    }
}
