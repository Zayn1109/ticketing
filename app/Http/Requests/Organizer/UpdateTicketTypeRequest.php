<?php

namespace App\Http\Requests\Organizer;

use App\Concerns\TicketTypeValidationRules;
use App\Models\TicketType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTicketTypeRequest extends FormRequest
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
     * 只有結束販售時間被改動時，才要求新的時間晚於現在；
     * 否則已停售的票種連名稱都無法修改
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

                $ticketType = $this->route('ticketType');

                if (! $ticketType instanceof TicketType) {
                    return;
                }

                $saleEndAt = $this->toUtc('sale_end_at');

                if ($saleEndAt->equalTo($ticketType->sale_end_at->copy()->startOfMinute())) {
                    return;
                }

                if ($saleEndAt->isPast()) {
                    $validator->errors()->add('sale_end_at', '結束販售時間必須晚於現在');
                }
            },
        ];
    }
}
