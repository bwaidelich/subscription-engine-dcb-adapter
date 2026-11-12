# DCB event store adapter for the Subscription Engine

[![Latest version](https://img.shields.io/packagist/v/wwwision/subscription-engine-dcb-adapter)](https://packagist.org/packages/wwwision/subscription-engine-dcb-adapter)
[![License](https://img.shields.io/packagist/l/wwwision/subscription-engine-dcb-adapter)](./LICENSE)

**Keep your projections up to date with the events of a [wwwision/dcb-eventstore](https://github.com/bwaidelich/dcb-eventstore).**

This package connects the event store to the [Subscription Engine](https://github.com/bwaidelich/subscription-engine). Your subscribers receive each event as a `SequencedEvent`.

## Installation

Install the adapter together with an event store implementation and a [subscription store](https://github.com/bwaidelich/subscription-engine#which-packages-do-i-need), for example:

```bash
composer require wwwision/subscription-engine-dcb-adapter wwwision/dcb-eventstore-doctrine wwwision/subscription-engine-doctrine
```

## Usage

```php
<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Wwwision\DCBEventStore\Event\Event;
use Wwwision\DCBEventStore\Event\Events;
use Wwwision\DCBEventStore\SequencedEvent\SequencedEvent;
use Wwwision\DCBEventStoreDoctrine\DoctrineEventStore;
use Wwwision\SubscriptionEngine\Subscriber\Subscriber;
use Wwwision\SubscriptionEngine\Subscriber\Subscribers;
use Wwwision\SubscriptionEngine\Subscription\RunMode;
use Wwwision\SubscriptionEngine\SubscriptionEngine;
use Wwwision\SubscriptionEngineDcbAdapter\DcbEventStoreAdapter;
use Wwwision\SubscriptionEngineDoctrine\DoctrineSubscriptionStore;

require __DIR__ . '/vendor/autoload.php';

$connection = DriverManager::getConnection((new DsnParser())->parse('pdo-sqlite:///' . __DIR__ . '/demo.sqlite'));
$eventStore = DoctrineEventStore::create($connection, eventTableName: 'events');

$engine = new SubscriptionEngine(
    eventStoreAdapter: new DcbEventStoreAdapter($eventStore),
    subscriptionStore: new DoctrineSubscriptionStore($connection, tableName: 'subscriptions'),
    subscribers: Subscribers::fromArray([
        Subscriber::create(
            id: 'some-projection',
            handler: fn (SequencedEvent $event) => print("Projecting {$event->event->type->value} at position {$event->position->value}\n"),
            reset: fn () => print("Resetting projection for replay\n"),
        ),
        Subscriber::create(
            id: 'some-process',
            handler: fn (SequencedEvent $event) => print("Invoking process for {$event->event->type->value}\n"),
            runMode: RunMode::FROM_NOW,
            setup: fn () => print("Setting up process\n"),
        ),
    ]),
);

// Once per deployment
$eventStore->setup();
$engine->setup();
$engine->boot();

// Store an event and pass it to the subscribers
$eventStore->append(Events::fromArray([Event::create('SomethingHappened', '{"foo": "bar"}')]));
$engine->catchUpActive();
```

```console
Setting up process
Invoking process for SomethingHappened
Projecting SomethingHappened at position 1
```

## Filtered subscribers

A subscriber can declare a `Query` as filter to receive only matching events:

```php
use Wwwision\DCBEventStore\Query\Query;
use Wwwision\DCBEventStore\Query\QueryItem;

Subscriber::create(
    id: 'orders',
    handler: $handler,
    filter: Query::fromItems(QueryItem::create(eventTypes: ['OrderPlaced', 'OrderCancelled'])),
);
```

Query items with `onlyLastEvent` are not supported. See [Filtered subscribers](https://github.com/bwaidelich/subscription-engine/blob/main/docs/how-it-works.md#filtered-subscribers) for how the engine applies filters.

## Next steps

The [guides of the Subscription Engine](https://github.com/bwaidelich/subscription-engine#guides) explain how to rebuild projections, handle failures and more. To process events in the background, see [wwwision/subscription-engine-worker](https://github.com/bwaidelich/subscription-engine-worker).

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/subscription-engine-dcb-adapter/issues) or [pull requests](https://github.com/bwaidelich/subscription-engine-dcb-adapter/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)
