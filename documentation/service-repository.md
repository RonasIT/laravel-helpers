[<< Traits][1]
[Services >>][2]

# Service-Repository Architecture

The package provides base classes for implementing the service-repository pattern. This pattern separates business logic (services) from data access (repositories), making the codebase more maintainable, testable, and easier to scale.

This results in a clean layered architecture:

```
Controller → Service → Repository → Model
```

- **Service** handles business logic, orchestrates application workflows, and delegates data operations to the repository.
- **Repository** encapsulates all database queries and CRUD operations.

## Setting Up

### 1. Create a Repository

Extend `BaseRepository` and set the model in the constructor:

```php
use RonasIT\Support\Repositories\BaseRepository;

final class UserRepository extends BaseRepository
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

final class UserService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(UserRepository::class);
    }
}
```

`EntityService` uses `__call()` to delegate method calls to the repository. If a repository method returns `$this` (for chaining), the service returns itself instead, allowing seamless method chaining through the service layer.

## CRUD Operations

All methods below are available on both the repository and the service (via delegation).

### Create

| Method | Description |
|--------|-------------|
| `create(array $data): Model` | Create a new entity and return the model |
| `insert(array $data): bool` | Mass insert entities with automatic timestamps |
| `firstOrCreate(array\|int\|string $where, array $data = []): Model` | Get the first entity matching the condition or create a new one |
| `updateOrCreate(array\|int\|string $where, array $data): Model` | Update an existing entity or create a new one |

### Read

| Method | Description |
|--------|-------------|
| `find(int\|string $id): ?Model` | Find an entity by primary key |
| `findBy(string $field, mixed $value): ?Model` | Find an entity by a specific field value |
| `first(array\|int\|string $where = []): ?Model` | Get the first entity matching the condition |
| `last(array\|int\|string $where = [], string $column = 'created_at'): ?Model` | Get the last entity matching the condition |
| `get(array\|int\|string $where = []): Collection` | Get entities by condition |
| `all(): Collection` | Get all entities without conditions |
| `exists(array\|int\|string $where): bool` | Check entity existence by condition or primary key |
| `existsBy(string $field, mixed $value): bool` | Check entity existence by a specific field value |
| `count(array\|int\|string $where = []): int` | Count entities by condition or primary key |
| `chunk(int $limit, Closure $callback, array $where = []): void` | Process entities in chunks ordered by primary key |

### Update

| Method | Description |
|--------|-------------|
| `update(array\|int\|string $where, array $data): ?Model` | Update a single entity by condition or primary key |
| `updateMany(array\|int\|string $where, array $data): int` | Update multiple entities by condition or primary key |

### Delete

| Method | Description |
|--------|-------------|
| `delete(array\|int\|string $where): int` | Delete entities by condition or primary key |
| `truncate(): self` | Remove all rows from the table |

### Batch Operations

These methods operate on lists of values for a given field (defaults to primary key):

| Method | Description |
|--------|-------------|
| `getByList(array $values, ?string $field = null): Collection` | Get entities by list of field values |
| `deleteByList(array $values, ?string $field = null): int` | Delete entities by list of field values |
| `restoreByList(array $values, ?string $field = null): int` | Restore soft-deleted entities by list of field values |
| `countByList(array $values, ?string $field = null): int` | Count entities by list of field values |
| `updateByList(array $values, array $data, ?string $field = null): int` | Update entities by list of field values |

The `$where` parameter in most methods accepts a primary key value (`int` or `string`) or an associative array of conditions:

```php
$repository->find(1);
$repository->first(['email' => 'user@example.com']);
$repository->update(1, ['name' => 'New Name']);
$repository->update(['email' => 'user@example.com'], ['name' => 'New Name']);
```

## Soft Delete Support

For models using Laravel's `SoftDeletes` trait:

```php
// Include soft-deleted entities in results
$repository->withTrashed()->get();

// Get only soft-deleted entities
$repository->onlyTrashed()->get();

// Restore a soft-deleted entity
$repository->restore($where);

// Permanently delete (bypass soft delete)
$repository->force()->delete($where);
```

## Force Mode

By default, `create()` and `update()` only fill attributes listed in the model's `$fillable` array. Use `force()` to bypass fillable restrictions and fill all model fields (including guarded):

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

The `SearchTrait` (included via `EntityControlTrait`) provides a built-in search and filtering pipeline with pagination, ordering, and automatic filter resolution by suffix convention.

### Basic Usage

```php
public function search(array $filters)
{
    return $this->searchQuery($filters)
        ->filterByQuery(['name', 'email'])
        ->getSearchResults();
}
```

### `searchQuery(array $filters)`

Initializes the search query, applies eager loading and soft-delete scoping from filters, then auto-applies remaining filters based on their suffix:
- `with` / `with_count` — eager loading
- `with_trashed` / `only_trashed` — soft-delete scoping
- All other filters are resolved automatically by their suffix:

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

Use these after `searchQuery()` for fine-grained control over filtering:

| Method | Description |
|--------|-------------|
| `filterBy(string $field, ?string $filterName = null)` | Filter by exact match. Supports dot notation for relations (e.g., `role.name`) |
| `filterByList(string $field, ?string $filterName = null)` | Filter by a list of values (whereIn). Supports dot notation for relations |
| `filterByQuery(array $fields, string $mask = ...)` | Search by text query (LIKE) across multiple fields. Supports dot notation for relations |
| `filterGreater(string $field, bool $isStrict = true, ...)` | Filter where field is greater than (or equal to) the filter value |
| `filterLess(string $field, bool $isStrict = true, ...)` | Filter where field is less than (or equal to) the filter value |
| `orderBy(?string $default = null, bool $defaultDesc = false)` | Sort results by the `order_by` filter. Supports dot notation for relations |

### Pagination

`getSearchResults()` finalizes the search, applies ordering, and returns a `LengthAwarePaginator`. Pagination is controlled via filters:

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
