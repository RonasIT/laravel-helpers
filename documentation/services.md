[<< Traits][1]
[Iterators >>][2]

# Services

## EntityService

The base class for service classes related to database entities. It allows to use pseudo inheritance
between the service class and related repository class;

### setRepository($repository)

Associate service class with the repository class which will using in pseudo inheritance.
- $repository - valid class name of repository class.

### getEntityName(): string

Proxied to the repository, returns the short class name of the model handled by the repository, e.g. `Event`.

### Binding the model class

`BaseRepository` and `EntityService` declare the model template type. Bind it once per class, and `find()`, `first()`,
`findBy()`, `last()`, `update()` return `Event|null`, while `create()`, `firstOrCreate()`, `updateOrCreate()` return
`Event`, both on the repository and on the service, including after chainable calls like `with()`:

```php
/**
 * @extends BaseRepository<Event>
 */
class EventRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(Event::class);
    }
}

/**
 * @extends EntityService<Event>
 */
class EventService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(EventRepository::class);
    }
}
```

Classes without `@extends` keep resolving to `Model`.

### Route entity loading

`BaseRequest::loadRouteEntity(string $serviceClass, string $routeParam = 'id', array $relations = [], ?string $entityName = null)`
loads the entity named by the route parameter once, from `init()`, before the `beforeAuthorization()` handlers and
`authorize()`. When the route parameter is missing or no row matches, it throws `NotFoundHttpException` with the
`:entity does not exist.` message, where `:entity` is `$entityName` or the short model class name. Override the text
in the project JSON translation file, e.g. `lang/en.json`:

```json
{
    ":entity does not exist.": "The :entity is not found."
}
```

The request stores the result in its own non-nullable property:

```php
class GetEventRequest extends BaseRequest
{
    protected Event $event;

    public function authorize(): bool
    {
        return $this->event->user_id === $this->user()->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    protected function init(): void
    {
        $this->event = $this->loadRouteEntity(EventService::class, relations: ['user']);
    }
}
```

The controller passes the model to the Action or service instead of the id:

```php
public function get(GetEventRequest $request, GetEventAction $action): EventResource
{
    $event = $action->run($request->getEvent());

    return EventResource::make($event);
}
```

The Action loads the missing relations on the received model instead of searching for it again:

```php
public function run(Event $event): Event
{
    return $event->loadMissing('media');
}
```

## HttpRequestService

Service to working with http/https protocols based on Guzzle library.

Features:
- service can be injected via the `app()` helper, which allows to mock it in testing;
- debug mode, write all requests into the log file. Enabling by setting config `defaults.http_service_debug` to `true`.

### get(string $url, array $data = [], array $headers = []): self

Method to send `GET` request to `$url`.

Make request, save response to the protected field and return self.

### post(string $url, array $data, array $headers = []): self

Method to send `POST` request to `$url`.

### delete(string $url, array $headers = []): self

Method to send `DELETE` request to `$url`.

### put(string $url, array $data, array $headers = []): self 

Method to send `PUT` request to `$url`.

### patch(string $url, array $data, array $headers = []): self

Method to send `PATCH` request to `$url`.

### getResponse(): ResponseInterface

Return last response object.

### json(): array

Return last response's content as associative array.

### saveCookieSession()

Saving cookies from request.

### getCookie()

Return saved cookies.

[<< Traits][1]
[Iterators >>][2]

[1]:traits.md
[2]:iterators.md
