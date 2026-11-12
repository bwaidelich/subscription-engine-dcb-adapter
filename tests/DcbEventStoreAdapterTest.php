<?php

declare(strict_types=1);

namespace Wwwision\SubscriptionEngineDcbAdapter\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\InMemoryEventStore\InMemoryEventStore;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\Query\QueryItem;
use Wwwision\SubscriptionEngine\EventStore\ReadOptions;
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
            $eventStore->append(Event::create($i % 2 === 1 ? 'Odd' : 'Even', 'event ' . $i));
        }
        $this->adapter = new DcbEventStoreAdapter($eventStore);
    }

    public function test_read_without_limit_returns_all_events_from_start_position(): void
    {
        self::assertSame([2, 3, 4], $this->readPositions(Position::fromInteger(2), ReadOptions::create()));
    }

    public function test_read_with_limit_returns_at_most_limit_events_from_start_position(): void
    {
        self::assertSame([2, 3], $this->readPositions(Position::fromInteger(2), ReadOptions::create(limit: 2)));
    }

    public function test_read_with_limit_higher_than_remaining_events_returns_remaining_events(): void
    {
        self::assertSame([3, 4], $this->readPositions(Position::fromInteger(3), ReadOptions::create(limit: 10)));
    }

    public function test_read_with_upTo_returns_no_events_after_it(): void
    {
        self::assertSame([2, 3], $this->readPositions(Position::fromInteger(2), ReadOptions::create(upTo: Position::fromInteger(3))));
    }

    public function test_read_with_filter_returns_matching_events_only(): void
    {
        self::assertSame([2, 4], $this->readPositions(Position::fromInteger(1), ReadOptions::create(filter: self::even())));
    }

    public function test_read_with_filter_counts_matching_events_for_the_limit(): void
    {
        self::assertSame([1, 3], $this->readPositions(Position::fromInteger(1), ReadOptions::create(limit: 2, filter: Query::fromItems(QueryItem::create(eventTypes: 'Odd')))));
    }

    public function test_read_with_filter_and_upTo(): void
    {
        self::assertSame([2], $this->readPositions(Position::fromInteger(1), ReadOptions::create(upTo: Position::fromInteger(3), filter: self::even())));
    }

    public function test_matches_decides_like_read(): void
    {
        $matches = [];
        foreach ($this->adapter->read(Position::fromInteger(1), ReadOptions::create()) as $event) {
            $matches[] = $this->adapter->matches(self::even(), $event);
        }
        self::assertSame([false, true, false, true], $matches);
    }

    public function test_read_throws_for_filters_of_another_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->readPositions(Position::fromInteger(1), ReadOptions::create(filter: new stdClass()));
    }

    public function test_read_throws_for_filters_with_onlyLastEvent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->readPositions(Position::fromInteger(1), ReadOptions::create(filter: Query::fromItems(QueryItem::create(eventTypes: 'Even', onlyLastEvent: true))));
    }

    private static function even(): Query
    {
        return Query::fromItems(QueryItem::create(eventTypes: 'Even'));
    }

    /**
     * @return array<int>
     */
    private function readPositions(Position $startPosition, ReadOptions $options): array
    {
        $positions = [];
        foreach ($this->adapter->read($startPosition, $options) as $event) {
            $positions[] = $this->adapter->eventPosition($event)->value;
        }
        return $positions;
    }
}
