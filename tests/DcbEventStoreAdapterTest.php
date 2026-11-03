<?php

declare(strict_types=1);

namespace Wwwision\SubscriptionEngineDcbAdapter\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\InMemoryEventStore\InMemoryEventStore;
use Wwwision\SubscriptionEngine\Subscription\Position;
use Wwwision\SubscriptionEngineDcbAdapter\DcbEventStoreAdapter;

#[CoversClass(DcbEventStoreAdapter::class)]
final class DcbEventStoreAdapterTest extends TestCase
{
    private DcbEventStoreAdapter $adapter;

    protected function setUp(): void
    {
        $eventStore = InMemoryEventStore::create();
        for ($i = 1; $i <= 4; $i++) {
            $eventStore->append(Event::create('SomeEvent', 'event ' . $i));
        }
        $this->adapter = new DcbEventStoreAdapter($eventStore);
    }

    public function test_read_without_limit_returns_all_events_from_start_position(): void
    {
        self::assertSame([2, 3, 4], $this->readPositions(Position::fromInteger(2)));
    }

    public function test_read_with_limit_returns_at_most_limit_events_from_start_position(): void
    {
        self::assertSame([2, 3], $this->readPositions(Position::fromInteger(2), 2));
    }

    public function test_read_with_limit_higher_than_remaining_events_returns_remaining_events(): void
    {
        self::assertSame([3, 4], $this->readPositions(Position::fromInteger(3), 10));
    }

    /**
     * @return array<int>
     */
    private function readPositions(Position $startPosition, int|null $limit = null): array
    {
        $positions = [];
        foreach ($this->adapter->read($startPosition, $limit) as $event) {
            $positions[] = $this->adapter->eventPosition($event)->value;
        }
        return $positions;
    }
}
