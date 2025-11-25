<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'reason' => ['required', 'string', 'max:500'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start_time' => ['nullable',
                                        'date_format:H:i',
                                        'after_or_equal:start_time',
                                        'before_or_equal:end_time',
                                        'required_with:breaks.*.end_time'],
            'breaks.*.end_time' => ['nullable',
                                    'date_format:H:i',
                                    'after_or_equal:breaks.*.start_time',
                                    'before_or_equal:end_time',
                                    'required_with:breaks.*.start_time'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            $breaks = $this->input('breaks', []);

            if ($startTime && $endTime) {
                foreach ($breaks as $key => $break) {
                    if (!empty($break['start_time']) && !empty($break['end_time'])) {
                        if ($break['start_time'] < $startTime || $break['end_time'] > $endTime) {
                            $validator->errors()->add("breaks.{$key}.start_time", '休憩時間が不適切な値です');
                        }
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
            'end_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.end_time.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.start_time.before_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.start_time.after_or_equal' => '休憩時間が不適切な値です',
        ];
    }
}
