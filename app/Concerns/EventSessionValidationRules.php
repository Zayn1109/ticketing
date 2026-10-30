<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait EventSessionValidationRules
{
    /**
     * datetime-local 欄位送出的格式（不含時區，依 timezone 欄位解讀）
     */
    protected const string LOCAL_DATETIME_FORMAT = 'Y-m-d\TH:i';

    /**
     * Get the validation rules used to validate event session details.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function eventSessionRules(): array
    {
        $datetime = 'date_format:'.self::LOCAL_DATETIME_FORMAT;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'venue_name' => ['required', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone:all_with_bc'],
            'starts_at' => ['required', $datetime],
            'ends_at' => ['required', $datetime, 'after:starts_at'],
            'doors_open_at' => ['nullable', $datetime, 'before_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
