<?php

namespace App\Http\Requests\Organizer;

use App\Concerns\TicketTypeValidationRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTicketTypeRequest extends FormRequest
{
    use TicketTypeValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->ticketTypeRules();
    }

    /**
     * 販售時間要先依場次時區換成 UTC 才能和現在、場次結束時間比較
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateSaleEndsBeforeSessionEnds($validator);
            },
            function (Validator $validator): void {
                if ($validator->errors()->has('sale_end_at')) {
                    return;
                }

                if ($this->toUtc('sale_end_at')->isPast()) {
                    $validator->errors()->add('sale_end_at', '結束販售時間必須晚於現在');
                }
            },
        ];
    }
}
