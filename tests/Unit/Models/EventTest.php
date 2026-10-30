<?php

namespace Tests\Unit\Models;

use App\Enums\EventStatus;
use App\Models\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @return array<string, array{EventStatus, bool}>
     */
    public static function editableByStatus(): array
    {
        return [
            '草稿可以編輯' => [EventStatus::Draft, true],
            '已發布可以編輯' => [EventStatus::Published, true],
            '已取消不能編輯' => [EventStatus::Cancelled, false],
        ];
    }

    #[DataProvider('editableByStatus')]
    public function test_is_editable_depends_on_status(EventStatus $status, bool $editable): void
    {
        $event = new Event;
        $event->status = $status;

        $this->assertSame($editable, $event->isEditable());
    }
}
