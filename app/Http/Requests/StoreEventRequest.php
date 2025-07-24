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
            'event_name.required' => '活動名稱為必填項目',
            'event_name.max' => '活動名稱不能超過 255 個字符',
            'date.required' => '日期為必填項目',
            'date.date' => '請選擇有效的日期',
            'date.after_or_equal' => '不能選擇過去的日期',
            'start_time.required' => '開始時間為必填項目',
            'start_time.date_format' => '開始時間格式不正確',
            'end_time.required' => '結束時間為必填項目',
            'end_time.date_format' => '結束時間格式不正確',
            'end_time.after' => '結束時間必須晚於開始時間',
            'timezone.required' => '時區為必填項目',
            'timezone.in' => '請選擇有效的時區',
        ];
    }
}
