# DCB event store subscription engine adapter

[wwwision/dcb-eventstore](https://packagist.org/packages/wwwision/dcb-eventstore) adapter for [wwwision/subscription-engine](https://github.com/bwaidelich/subscription-engine)

## Usage

```php
$eventStore = DoctrineEventStore::create($dbalConnection, eventTableName: 'events');

$subscriptionEngine = new SubscriptionEngine(
    eventStoreAdapter: new DcbEventStoreAdapter($eventStore),
    subscriptionStore: new DoctrineSubscriptionStore($dbalConnection, tableName: 'subscriptions'),
    subscribers: Subscribers::fromArray([
        Subscriber::create(
            id: 'some-projection',
            handler: fn (EventEnvelope $eventEnvelope) => print($eventEnvelope->event->type->value),
            reset: fn () => print('resetting projection for replay'),
        ),
        Subscriber::create(
            id: 'some-process',
            handler: fn (EventEnvelope $eventEnvelope) => print('invoking process...'),
            runMode: RunMode::FROM_NOW,
            setup: fn () => print('setting up process...'),
        ),
    ])
);
```

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/subscription-engine-dcb-adapter/issues) or [pull requests](https://github.com/bwaidelich/subscription-engine-dcb-adapter/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)