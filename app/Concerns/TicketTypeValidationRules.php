<?php

namespace App\Concerns;

use App\Models\EventSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

trait TicketTypeValidationRules
{
    /**
     * datetime-local 欄位送出的格式（不含時區，依所屬場次的時區解讀）
     */
    protected const string LOCAL_DATETIME_FORMAT = 'Y-m-d\TH:i';

    /**
     * Get the validation rules used to validate ticket type details.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ticketTypeRules(): array
    {
        $datetime = 'date_format:'.self::LOCAL_DATETIME_FORMAT;
        $capacity = $this->eventSession()->capacity;

        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'quota' => array_filter([
                'required', 'integer', 'min:1',
                $capacity !== null ? 'max:'.$capacity : null,
            ]),
            'sale_start_at' => ['required', $datetime],
            'sale_end_at' => ['required', $datetime, 'after:sale_start_at'],
            'max_per_user' => ['nullable', 'integer', 'min:1', 'lte:quota'],
        ];
    }

    /**
     * 結束販售不可晚於場次結束（決定 2）
     */
    protected function validateSaleEndsBeforeSessionEnds(Validator $validator): void
    {
        if ($validator->errors()->has('sale_end_at')) {
            return;
        }

        if ($this->toUtc('sale_end_at')->greaterThan($this->eventSession()->ends_at)) {
            $validator->errors()->add('sale_end_at', '結束販售時間不可晚於場次結束時間');
        }
    }

    /**
     * 驗證後的資料，販售時間已依場次時區轉成 UTC
     *
     * @return array<string, mixed>
     */
    public function ticketTypeAttributes(): array
    {
        return [
            ...$this->validated(),
            'sale_start_at' => $this->toUtc('sale_start_at'),
            'sale_end_at' => $this->toUtc('sale_end_at'),
        ];
    }

    /**
     * 把 datetime-local 的輸入依所屬場次的時區解讀，再轉成 UTC
     */
    protected function toUtc(string $field): Carbon
    {
        return Carbon::createFromFormat(
            self::LOCAL_DATETIME_FORMAT,
            $this->string($field)->toString(),
            $this->eventSession()->timezone,
        )->utc();
    }

    /**
     * 網址中的場次（scopeBindings 已確認屬於該活動與主辦方）
     */
    protected function eventSession(): EventSession
    {
        $session = $this->route('session');

        abort_unless($session instanceof EventSession, 404);

        return $session;
    }
}
