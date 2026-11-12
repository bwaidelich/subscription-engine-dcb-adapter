<?php

declare(strict_types=1);

namespace Wwwision\SubscriptionEngineDcbAdapter;

use InvalidArgumentException;
use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\Query\QueryItem;
use Wwwision\DCBEventStore\ReadOptions as DcbReadOptions;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\SubscriptionEngine\EventStore\EventStoreAdapter;
use Wwwision\SubscriptionEngine\EventStore\ReadOptions;
use Wwwision\SubscriptionEngine\Subscription\Position;

/**
 * Subscribers can declare a {@see Query} as filter. Query items with `onlyLastEvent` are not supported
 *
 * @implements EventStoreAdapter<SequencedEvent, Query>
 */
final readonly class DcbEventStoreAdapter implements EventStoreAdapter
{
    public function __construct(
        private EventStore $eventStore,
    ) {
    }

    public function read(Position $startPosition, ReadOptions $options): iterable
    {
        $query = $options->filter !== null ? self::query($options->filter) : Query::all();
        $events = $this->eventStore->read($query, DcbReadOptions::create(from: $startPosition->value, limit: $options->limit));
        if ($options->upTo === null) {
            return $events;
        }
        return self::upTo($events, $options->upTo);
    }

    public function lastPosition(): Position
    {
        $lastSequencedEvent = $this->eventStore->read(Query::all(), DcbReadOptions::create(backwards: true))->first();
        return Position::fromInteger($lastSequencedEvent->position->value ?? 0);
    }

    public function eventPosition(object $event): Position
    {
        return Position::fromInteger($event->position->value);
    }

    public function matches(object $filter, object $event): bool
    {
        return self::query($filter)->matchesEvent($event->event);
    }

    /**
     * @param iterable<SequencedEvent> $events
     * @return iterable<SequencedEvent>
     */
    private static function upTo(iterable $events, Position $upTo): iterable
    {
        foreach ($events as $event) {
            if ($event->position->value > $upTo->value) {
                return;
            }
            yield $event;
        }
    }

    private static function query(object $filter): Query
    {
        if (!$filter instanceof Query) {
            throw new InvalidArgumentException(sprintf('Expected filter of type %s, got: %s', Query::class, get_debug_type($filter)), 1794470600);
        }
        foreach ($filter as $item) {
            /** @var QueryItem $item */
            if ($item->onlyLastEvent) {
                throw new InvalidArgumentException('Query items with onlyLastEvent are not supported as filter', 1794470601);
            }
        }
        return $filter;
    }
}
