<?php

declare(strict_types=1);

namespace Wwwision\SubscriptionEngineDcbAdapter;

use Wwwision\DCBEventStore\EventStore;
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\ReadOptions;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\SubscriptionEngine\EventStore\EventStoreAdapter;
use Wwwision\SubscriptionEngine\Subscription\Position;

/**
 * @implements EventStoreAdapter<SequencedEvent>
 */
final readonly class DcbEventStoreAdapter implements EventStoreAdapter
{
    public function __construct(
        private EventStore $eventStore,
    ) {
    }

    public function read(Position $startPosition): iterable
    {
        return $this->eventStore->read(Query::all(), ReadOptions::create(from: $startPosition->value));
    }

    public function lastPosition(): Position
    {
        $lastSequencedEvent = $this->eventStore->read(Query::all(), ReadOptions::create(backwards: true))->first();
        return Position::fromInteger($lastSequencedEvent->position->value ?? 0);
    }

    public function eventPosition(object $event): Position
    {
        return Position::fromInteger($event->position->value);
    }
}
