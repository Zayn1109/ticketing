<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

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

    /**
     * 驗證後的資料，時間欄位已依場次時區轉成 UTC
     *
     * @return array<string, mixed>
     */
    public function sessionAttributes(): array
    {
        $attributes = $this->validated();

        foreach (['starts_at', 'ends_at', 'doors_open_at'] as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = $this->toUtc($field);
            }
        }

        return $attributes;
    }

    /**
     * 把 datetime-local 的輸入依 timezone 欄位解讀，再轉成 UTC
     */
    protected function toUtc(string $field): Carbon
    {
        return Carbon::createFromFormat(
            self::LOCAL_DATETIME_FORMAT,
            $this->string($field)->toString(),
            $this->string('timezone')->toString(),
        )->utc();
    }
}
