<?php

namespace App\Http\Requests\Organizer;

use App\Concerns\EventSessionValidationRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreEventSessionRequest extends FormRequest
{
    use EventSessionValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->eventSessionRules();
    }

    /**
     * 「晚於現在」要先依場次時區換成 UTC 才能比較，所以不用 after:now
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['starts_at', 'timezone'])) {
                    return;
                }

                if ($this->toUtc('starts_at')->isPast()) {
                    $validator->errors()->add('starts_at', '開始時間必須晚於現在');
                }
            },
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

    private function toUtc(string $field): Carbon
    {
        return Carbon::createFromFormat(
            self::LOCAL_DATETIME_FORMAT,
            $this->string($field)->toString(),
            $this->string('timezone')->toString(),
        )->utc();
    }
}
