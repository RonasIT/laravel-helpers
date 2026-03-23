[<< Traits][1]
[Services >>][2]

# Service-Repository Architecture

The package provides base classes for implementing the service-repository pattern. This pattern separates business logic (services) from data access (repositories), making the codebase more maintainable, testable, and easier to scale.

Resulting a clean layered architecture:

```
Controller → Service → Repository → Model
```

- **Service** handles business logic, orchestrates application workflows and delegates data operations to the repository.
- **Repository** encapsulates all database queries and CRUD operations.

## Setting Up

### 1. Create a Repository

Extend `BaseRepository` and set the model in the constructor:

```php
use RonasIT\Support\Repositories\BaseRepository;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(User::class);
    }
}
```

`BaseRepository` uses `EntityControlTrait`, which provides all CRUD and search methods automatically.

### 2. Create a Service

Extend `EntityService` and associate it with a repository:

```php
use RonasIT\Support\Services\EntityService;

class UserService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(UserRepository::class);
    }
}
```

`EntityService` uses `__call()` to delegate all method calls to the repository. If a repository method returns `$this` (for chaining), the service returns itself instead, so you can chain through the service seamlessly.

### 3. Use in Controller

```php
class UserController extends Controller
{
    public function __construct(
        protected UserService $service,
    ) {
    }

    public function index(GetUsersRequest $request)
    {
        return $this->service
            ->searchQuery($request->onlyValidated())
            ->filterByQuery(['name', 'email'])
            ->getSearchResults();
    }

    public function show(int $id)
    {
        return $this->service->find($id);
    }

    public function store(CreateUserRequest $request)
    {
        return $this->service->create($request->onlyValidated());
    }

    public function update(int $id, UpdateUserRequest $request)
    {
        return $this->service->update($id, $request->onlyValidated());
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
    }
}
```

## CRUD Operations

All methods below are available on both the repository and the service (via delegation).

### Create

| Method | Description |
|--------|-------------|
| `create(array $data): Model` | Create a single record and return the model |
| `insert(array $data): bool` | Bulk insert multiple records (array of arrays) |
| `firstOrCreate($where, array $data = []): Model` | Find by condition or create if not found |
| `updateOrCreate($where, array $data): Model` | Update existing or create new record |

### Read

| Method | Description |
|--------|-------------|
| `find($id): ?Model` | Find by primary key |
| `findBy(string $field, $value): ?Model` | Find by a specific field |
| `first($where = []): ?Model` | Get first matching record |
| `last(array $where = [], string $column = 'created_at'): ?Model` | Get latest record |
| `get(array $where = []): Collection` | Get all matching records |
| `all(): Collection` | Get all records |
| `exists($where): bool` | Check if a matching record exists |
| `existsBy(string $field, $value): bool` | Check existence by field |
| `count($where = []): int` | Count matching records |
| `chunk(int $limit, Closure $callback, array $where = []): void` | Process records in chunks |

### Update

| Method | Description |
|--------|-------------|
| `update($where, array $data): ?Model` | Update a single record and return the model |
| `updateMany($where, array $data): int` | Update multiple records, return count |

### Delete

| Method | Description |
|--------|-------------|
| `delete($where): int` | Delete matching records, return count |
| `truncate(): self` | Delete all records from the table |

### Batch Operations

These methods operate on lists of values for a given field (defaults to primary key):

| Method | Description |
|--------|-------------|
| `getByList(array $values, ?string $field = null): Collection` | Get records by list of field values |
| `deleteByList(array $values, ?string $field = null): int` | Delete by list |
| `restoreByList(array $values, ?string $field = null): int` | Restore soft-deleted by list |
| `countByList(array $values, ?string $field = null): int` | Count by list |
| `updateByList(array $values, array $data, $field = null): int` | Update by list |

The `$where` parameter in most methods accepts either a primary key value or an associative array of conditions:

```php
$repository->find(1);
$repository->first(['email' => 'user@example.com']);
$repository->update(1, ['name' => 'New Name']);
$repository->update(['email' => 'user@example.com'], ['name' => 'New Name']);
```

## Soft Delete Support

For models using Laravel's `SoftDeletes` trait:

```php
// Include soft-deleted records in results
$repository->withTrashed()->get();

// Get only soft-deleted records
$repository->onlyTrashed()->get();

// Restore a soft-deleted record
$repository->restore($where);

// Permanently delete (bypass soft delete)
$repository->force()->delete($where);
```

## Force Mode

By default, `create()` and `update()` only fill attributes listed in the model's `$fillable`. Use `force()` to fill all model fields (including guarded):

```php
$repository->force()->create($data);
$repository->force()->update($where, $data);
```

## Eager Loading

```php
// Load relations
$repository->with(['posts', 'posts.comments'])->get();

// Load relation counts
$repository->withCount(['posts'])->get();

// Nested relation counts (e.g., count comments on each post)
$repository->withCount(['posts.comments'])->get();
```

## Search and Filtering

The `SearchTrait` (included via `EntityControlTrait`) provides a powerful search/filter pipeline.

### Basic Usage

```php
public function search(array $filters)
{
    return $this->searchQuery($filters)
        ->filterByQuery(['name', 'email'])
        ->getSearchResults();
}
```

### searchQuery(array $filters)

Initializes the search pipeline. Automatically applies:
- `with` / `with_count` — eager loading from filters
- `with_trashed` / `only_trashed` — soft delete scoping
- **Auto-filters by suffix** — filters not in the reserved list are automatically applied based on their suffix:

| Suffix | Behavior | Example filter |
|--------|----------|----------------|
| `_in_list` | `whereIn` | `status_in_list: ['active', 'pending']` |
| `_not_in_list` | `whereNotIn` | `role_not_in_list: [1, 2]` |
| `_gte` | `>=` | `age_gte: 18` |
| `_gt` | `>` | `price_gt: 100` |
| `_lte` | `<=` | `age_lte: 65` |
| `_lt` | `<` | `price_lt: 1000` |
| `_from` | `>=` | `created_at_from: '2024-01-01'` |
| `_to` | `<=` | `created_at_to: '2024-12-31'` |
| *(none)* | `=` (exact match) | `status: 'active'` |

### Manual Filter Methods

Use these after `searchQuery()` for additional control:

| Method | Description |
|--------|-------------|
| `filterBy(string $field, ?string $filterName = null)` | Exact match filter. Supports dot notation for relations (e.g., `role.name`) |
| `filterByList(string $field, ?string $filterName = null)` | `whereIn` filter |
| `filterByQuery(array $fields, string $mask = ...)` | `LIKE` search across multiple fields. Supports relation fields via dot notation |
| `filterGreater(string $field, bool $isStrict = true, ...)` | Greater than filter |
| `filterLess(string $field, bool $isStrict = true, ...)` | Less than filter |
| `orderBy(?string $default = null, bool $defaultDesc = false)` | Apply ordering. Supports relation fields via dot notation |

### Pagination

`getSearchResults()` returns a `LengthAwarePaginator`. Pagination is controlled via filters:

| Filter | Description | Default |
|--------|-------------|---------|
| `per_page` | Items per page | Value from `config('defaults.items_per_page')` |
| `page` | Current page | `1` |
| `all` | Return all results (no pagination) | `false` |
| `order_by` | Field to sort by | Primary key |
| `desc` | Sort descending | `false` |

### Reserved Filter Names

These filter keys are handled internally and should not be used as field filters:
`with`, `with_count`, `with_trashed`, `only_trashed`, `query`, `order_by`, `all`, `per_page`, `page`, `desc`.

### Full Search Example

```php
class UserRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(User::class);
    }

    public function search(array $filters)
    {
        return $this->searchQuery($filters)
            ->filterBy('email')
            ->filterBy('role.name', 'role_name')
            ->filterByQuery(['name', 'email', 'profile.bio'])
            ->getSearchResults();
    }
}
```

Usage with filters:

```php
$userRepository->search([
    'role_name' => 'admin',
    'query' => 'john',
    'created_at_from' => '2024-01-01',
    'order_by' => 'created_at',
    'desc' => true,
    'per_page' => 20,
    'with' => ['posts', 'role'],
    'with_count' => ['posts'],
]);
```

[<< Traits][1]
[Services >>][2]

[1]:traits.md
[2]:services.md
