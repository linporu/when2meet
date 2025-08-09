<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if participant belongs to this event
        return $this->route('participant')->event_id === $this->route('event')->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'participant_name' => 'required|string|max:255',
            'availability' => 'array',
            'availability.*' => 'array',
            'availability.*.*' => 'array',
            'availability.*.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.*.end_time' => 'nullable|date_format:H:i',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'participant_name.required' => 'Please enter your name.',
            'participant_name.string' => 'Your name must be a valid text.',
            'participant_name.max' => 'Your name is too long.',
            'availability.*.*.start_time.date_format' => 'Please enter a valid start time.',
            'availability.*.*.end_time.date_format' => 'Please enter a valid end time.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateTimeRangeLogic($validator);
        });
    }

    /**
     * Validate time ranges logic.
     */
    private function validateTimeRangeLogic($validator): void
    {
        $availability = $this->input('availability', []);

        foreach ($availability as $date => $timeRanges) {
            foreach ($timeRanges as $index => $timeRange) {
                $startTime = $timeRange['start_time'] ?? null;
                $endTime = $timeRange['end_time'] ?? null;

                // Both start and end time must be provided if one is provided
                if (empty($startTime) || empty($endTime)) {
                    $validator->errors()->add(
                        "availability.{$date}.{$index}",
                        'Both start time and end time must be selected.'
                    );

                    continue;
                }

                // End time must be later than start time
                if ($startTime >= $endTime) {
                    $validator->errors()->add(
                        "availability.{$date}.{$index}",
                        'End time must be later than start time.'
                    );
                }
            }
        }
    }

    /**
     * Get formatted availability data for storage.
     */
    public function getFormattedAvailability(): array
    {
        $availability = $this->validated()['availability'] ?? [];
        $result = [];

        foreach ($availability as $date => $timeRanges) {
            foreach ($timeRanges as $timeRange) {
                if (! empty($timeRange['start_time']) && ! empty($timeRange['end_time'])) {
                    $result[] = [
                        'date' => $date,
                        'start_time' => $timeRange['start_time'],
                        'end_time' => $timeRange['end_time'],
                    ];
                }
            }
        }

        return $result;
    }
}
