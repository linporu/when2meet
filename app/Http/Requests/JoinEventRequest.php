<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class JoinEventRequest extends FormRequest
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
            'participant_name' => 'required|string|max:255',
            'availability' => 'array',
            'availability.*' => 'array',
            'availability.*.*' => 'array',
            'availability.*.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.*.end_time' => 'nullable|date_format:H:i',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'participant_name.required' => 'Please enter your name.',
            'participant_name.string' => 'Your name must be a valid text.',
            'participant_name.max' => 'Your name cannot exceed 255 characters.',
            'availability.*.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'availability.*.*.end_time.date_format' => 'End time must be in HH:MM format.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateTimeRanges($validator);
        });
    }

    /**
     * Validate time ranges logic.
     */
    protected function validateTimeRanges(Validator $validator): void
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
     * Get validated availability data in flat array format for storage.
     */
    public function getValidatedAvailability(): array
    {
        $availability = $this->validated()['availability'] ?? [];
        $result = [];

        foreach ($availability as $date => $timeRanges) {
            foreach ($timeRanges as $timeRange) {
                $result[] = [
                    'date' => $date,
                    'start_time' => $timeRange['start_time'],
                    'end_time' => $timeRange['end_time'],
                ];
            }
        }

        return $result;
    }
}
