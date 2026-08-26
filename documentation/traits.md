[<< Helpers][1]
[Versioning >>][2]

# Traits

## EntityControlTrait

Provides CRUD-based methods to work with database-related entities.

## FixturesTrait

This trait is designed to make testing understandable and cleaner.
All auxiliary data such as the results of the operation can be placed in a way corresponding 
to the following mask **base_path("tests/fixtures/{$testClassName}/{$fixture}")**, 
and is easily obtained by the method *$this->getFixture($fixture)* or through *$this->getJsonFixture($fixture)*, 
in which will be performed automatically decode json-data.
Also you can tune your TestCase for restore dump of database witch will be places in 
**base_path("tests/fixtures/{$testClassName}/dump.sql")** and method for comfortable getting of Json-responses

## MockHttpRequestTrait

This trait was designed to make external http sources testing more convenient. This trait 
mocks `HttpRequestService` and give an ability to mock get and post http requests

## NotificationsMockTrait

This trait was designed to make notifications testing more convenient. Instead of asserting that a
notification class was sent, it puts every sent notification into a json fixture, so a test covers
the whole payload: the notification properties, the channels, the notifiable and the locale.

`RonasIT\Support\Testing\TestCase` already uses this trait and calls `Notification::fake()`, so a
test only calls the assertion:

```php
public function testCreate()
{
    $response = $this->actingAs(self::$user)->json('post', '/orders', $this->getJsonFixture('create_order_request'));

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertNotificationsSent('create_order_notifications');
}
```

Methods list:
* `assertNotificationsSent($fixture, $options = [], $exportMode = false)` - compares all the sent
notifications with the `$fixture`, exporting it instead of comparing when `$exportMode` is enabled,
the same way the other fixture assertions of the package work.

Every entry of the fixture is placed under the notification class name and contains the
`notification` properties of any visibility except `id`, the `channels` returned by `via()`, the
`notifiable` and the `locale`. The `notifiable` always keeps its `class` next to the `attributes`, so
notifiables of different classes sharing a primary key stay distinguishable. The attributes of a
model are its primary key, of any other notifiable, e.g. `Illuminate\Notifications\AnonymousNotifiable`,
its public properties:

```json
{
    "App\\Notifications\\OrderCreatedNotification": [
        {
            "notification": {
                "orderId": 1
            },
            "channels": [
                "mail"
            ],
            "notifiable": {
                "class": "App\\Models\\User",
                "attributes": {
                    "id": 1
                }
            },
            "locale": null
        }
    ]
}
```

Entries are ordered the way the Notification fake groups them: by notifiable class, then by
notifiable key, and only then by the send order within that group. Sending a notification to one
notifiable, then to another one, and then to the first one again puts the third entry before the
second one, so the fixture must not be read as a chronological sequence.

The properties of a notification rarely describe what a channel delivers, the channel methods do.
The `$options` argument adds such data to every entry, describing each field by a chain of steps
resolved on the notification:

* a step ending with `()` is a method call, any other step is a property access, resolving public
properties, the magic ones exposed via `__isset()` and the attributes of an Eloquent model,
* the first step is applied to the notification, every next one to the result of the previous step,
* the notifiable is passed as the first argument to every method that accepts at least one parameter,
the way Laravel dispatches channel methods, so parameterless methods of nested objects, e.g.
`DateTimeImmutable::getTimestamp()`, are called without arguments,
* a non-public member is not resolvable, a step pointing at one fails the test,
* field names must not collide with the reserved ones: `notification`, `channels`, `notifiable`,
`locale`.

A misspelled step, a non-public member and a step applied to a non-object value fail the test with a
message naming the class the step was resolved on, instead of silently exporting `null`.

```php
$this->assertNotificationsSent(
    fixture: 'create_order_notifications',
    options: [
        'message' => ['toExpoPush()', 'toArray()'], // $notification->toExpoPush($notifiable)->toArray($notifiable)
        'subject' => ['toMail()', 'subject'],       // $notification->toMail($notifiable)->subject
    ],
);
```

The chain of a channel is the same in every test, so declare an assertion per channel in the project
`TestCase` and keep the tests free of the chain definitions.

**Example**

```php
#TestCase.php

protected function assertPushNotificationsSent(string $fixture, array $options = [], bool $exportMode = false): void
{
    $this->assertNotificationsSent(
        fixture: "push_notifications/{$fixture}",
        options: array_merge(['message' => ['toExpoPush()', 'toArray()']], $options),
        exportMode: $exportMode,
    );
}

protected function assertBroadcastNotificationsSent(string $fixture, bool $exportMode = false): void
{
    $this->assertNotificationsSent(
        fixture: "broadcast_notifications/{$fixture}",
        options: [
            'broadcast_on' => ['broadcastOn()'],
            'broadcast_data' => ['toBroadcast()', 'data'],
            'broadcast_as' => ['broadcastAs()'],
        ],
        exportMode: $exportMode,
    );
}

#OrderTest.php

$this->assertPushNotificationsSent('order_created');
```

## ReflectionTrait

An internal helper of `NotificationsMockTrait`, not meant to be used directly.

Methods list:
* `getObjectAttributes($object)` - dumps the object properties of any visibility into an array,
skipping the static ones and the typed ones that were never initialized. Private properties declared
on parent classes are not captured, since they are not accessible on the object class reflection.

## SearchTrait

This trait implements `search` data function. It contains methods for filtering by fields of model,
by model relations, searching by row data as query.

Just init search with `$this->getSearchQuery()` and then you can chain different filters to make
search you need. Available search methods are:
* `filterBy()` - filtering by model field
* `filterByQuery(['field1', 'field2', ...])` - filtering by model field1 and field2 with "LIKE" operator
* `filterByQueryOnRelation($relation, [fields])` - filtering with "LIKE" operator by related model fields
* `filterByRelation($relation, $field)` - filtering by related model field.

Also, you can specify results order using `order_by()` method and specify relations by `with()`
method if you want retrieve related data too.

To get results call `getSearchResults()` method, that's it. You can pass `all` filter to get all results, or use
`page` or `per_page` if you need paginate your results.

**Example**

```php
#UserRepository.php

public function search()
{
    $filters = [
        'order_by' => 'udated_at',
        'email' => 'test@example.com',
        'role_id' => 2,
        'with' => ['posts', 'posts.comments'],
        'per_page' => 20
    ];
    
    $this->getSearchQuery($filters)
         ->filterBy('email')
         ->filterByQuery(['name'])
         ->filterByRelation('role', 'role_id')
         ->order_by()
         ->with()
         ->getSearchResults();
}
```

## TranslationTrait

Add multi-language support for models.
Requirements: translation model have to be named as `{modelName}Translation` and contains locale field.
For example, for model `Product` you should create `ProductTranslation` model and create fields you want translate plus required `locale` field.

## MigrationTrait

Gives you some convenient methods to create foreign keys, bridge tables for many-to-many relationships.
Methods list: 
* `addForeignKey($fromEntity, $toEntity, $needAddField = false)` - creates foreign key from table to table
* `dropForeignKey($fromEntity, $toEntity, $needDropField = false)` - drops foreign key from table to table
* `createBridgeTable($fromEntity, $toEntity)` - creates bridge table for many-to-many relation

[<< Helpers][1]
[Versioning >>][2]

[1]:helpers.md
[2]:versioning.md
