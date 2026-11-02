<?php

namespace Tests\Unit\Models;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\TicketType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketTypeTest extends TestCase
{
    /**
     * @return array<string, array{EventSessionStatus, EventStatus, bool}>
     */
    public static function editableByStatus(): array
    {
        return [
            '排定場次的票種可以修改' => [EventSessionStatus::Scheduled, EventStatus::Published, true],
            '已取消場次的票種不能修改' => [EventSessionStatus::Cancelled, EventStatus::Published, false],
            '已取消活動的票種不能修改' => [EventSessionStatus::Scheduled, EventStatus::Cancelled, false],
        ];
    }

    #[DataProvider('editableByStatus')]
    public function test_is_editable_follows_session(
        EventSessionStatus $sessionStatus,
        EventStatus $eventStatus,
        bool $editable,
    ): void {
        $this->assertSame($editable, $this->makeTicketType($sessionStatus, $eventStatus)->isEditable());
    }

    /**
     * @return array<string, array{EventStatus, bool}>
     */
    public static function deletableByEventStatus(): array
    {
        return [
            '草稿活動的票種可以刪除' => [EventStatus::Draft, true],
            '已發布活動的票種不能刪除' => [EventStatus::Published, false],
        ];
    }

    #[DataProvider('deletableByEventStatus')]
    public function test_is_deletable_only_for_draft_event(EventStatus $eventStatus, bool $deletable): void
    {
        $ticketType = $this->makeTicketType(EventSessionStatus::Scheduled, $eventStatus);

        $this->assertSame($deletable, $ticketType->isDeletable());
    }

    private function makeTicketType(EventSessionStatus $sessionStatus, EventStatus $eventStatus): TicketType
    {
        $event = new Event;
        $event->status = $eventStatus;

        $session = new EventSession;
        $session->status = $sessionStatus;
        $session->setRelation('event', $event);

        $ticketType = new TicketType;
        $ticketType->setRelation('eventSession', $session);

        return $ticketType;
    }
}
