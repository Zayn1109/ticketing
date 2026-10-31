<?php

namespace App\Http\Requests\Organizer;

use App\Concerns\EventSessionValidationRules;
use App\Models\EventSession;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEventSessionRequest extends FormRequest
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
     * 只有開始時間被改動時，才要求新的開始時間晚於現在；
     * 否則已開始的場次連場館名稱都無法修改
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

                $session = $this->route('session');

                if (! $session instanceof EventSession) {
                    return;
                }

                $startsAt = $this->toUtc('starts_at');

                if ($startsAt->equalTo($session->starts_at->copy()->startOfMinute())) {
                    return;
                }

                if ($startsAt->isPast()) {
                    $validator->errors()->add('starts_at', '開始時間必須晚於現在');
                }
            },
        ];
    }
}
