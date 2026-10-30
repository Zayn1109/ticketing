<?php

namespace Tests\Unit\Models;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EventSessionTest extends TestCase
{
    /**
     * @return array<string, array{EventSessionStatus, EventStatus, bool}>
     */
    public static function editableByStatus(): array
    {
        return [
            '草稿活動的排定場次可以修改' => [EventSessionStatus::Scheduled, EventStatus::Draft, true],
            '已發布活動的排定場次可以修改' => [EventSessionStatus::Scheduled, EventStatus::Published, true],
            '已取消的場次不能修改' => [EventSessionStatus::Cancelled, EventStatus::Published, false],
            '活動已取消時場次不能修改' => [EventSessionStatus::Scheduled, EventStatus::Cancelled, false],
        ];
    }

    #[DataProvider('editableByStatus')]
    public function test_is_editable_depends_on_session_and_event_status(
        EventSessionStatus $sessionStatus,
        EventStatus $eventStatus,
        bool $editable,
    ): void {
        $session = $this->makeSession($sessionStatus, $eventStatus);

        $this->assertSame($editable, $session->isEditable());
    }

    /**
     * @return array<string, array{EventStatus, bool}>
     */
    public static function deletableByEventStatus(): array
    {
        return [
            '草稿活動的場次可以刪除' => [EventStatus::Draft, true],
            '已發布活動的場次不能刪除' => [EventStatus::Published, false],
            '已取消活動的場次不能刪除' => [EventStatus::Cancelled, false],
        ];
    }

    #[DataProvider('deletableByEventStatus')]
    public function test_is_deletable_only_for_draft_event(EventStatus $eventStatus, bool $deletable): void
    {
        $session = $this->makeSession(EventSessionStatus::Scheduled, $eventStatus);

        $this->assertSame($deletable, $session->isDeletable());
    }

    private function makeSession(EventSessionStatus $sessionStatus, EventStatus $eventStatus): EventSession
    {
        $event = new Event;
        $event->status = $eventStatus;

        $session = new EventSession;
        $session->status = $sessionStatus;
        $session->setRelation('event', $event);

        return $session;
    }
}
