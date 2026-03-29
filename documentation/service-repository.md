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

The `$where` parameter accepts a primary key value (`int` or `string`) or an associative array of conditions:

```php
$this->find(1);
$this->first(['email' => 'user@example.com']);
$this->update(1, ['name' => 'New Name']);
$this->update(['email' => 'user@example.com'], ['name' => 'New Name']);
```

### Create

| Method | Description |
|--------|-------------|
| `create(array $data): Model` | Create a new entity and return it with loaded relations |
| `insert(array $data): bool` | Mass insert entities with automatic timestamps |
| `insertOrIgnore(array $data): int` | Mass insert rows, silently skipping duplicate key errors. Returns count of inserted rows |
| `firstOrCreate(array\|int\|string $where, array $data = []): Model` | Get the first entity matching the condition or create a new one |
| `updateOrCreate(array\|int\|string $where, array $data): Model` | Update an existing entity or create a new one |

### Read

| Method | Description |
|--------|-------------|
| `find(int\|string $id): ?Model` | Find an entity by primary key |
| `findBy(string $field, mixed $value): ?Model` | Find an entity by a specific field value |
| `first(array\|int\|string $where = []): ?Model` | Get the first entity matching the condition |
| `last(array\|int\|string $where = [], string $column = 'created_at'): ?Model` | Get the last entity matching the condition, ordered by `$column` |
| `get(array\|int\|string $where = []): Collection` | Get all entities matching the condition |
| `getByList(array $values, ?string $field = null): Collection` | Get entities whose `$field` value is in `$values` (defaults to primary key) |
| `all(): Collection` | Get all entities without conditions |
| `exists(array\|int\|string $where): bool` | Check entity existence by condition or primary key |
| `existsBy(string $field, mixed $value): bool` | Check entity existence by a specific field value |
| `count(array\|int\|string $where = []): int` | Count entities by condition or primary key |
| `countByList(array $values, ?string $field = null): int` | Count entities whose `$field` value is in `$values` (defaults to primary key) |
| `chunk(int $limit, Closure $callback, array $where = []): void` | Process entities in chunks ordered by primary key |

### Update

| Method | Description |
|--------|-------------|
| `update(array\|int\|string $where, array $data): ?Model` | Update a single entity and return it |
| `updateMany(array\|int\|string $where, array $data): int` | Update all entities matching the condition. Returns count of updated rows |
| `updateByList(array $values, array $data, ?string $field = null): int` | Update entities whose `$field` value is in `$values` (defaults to primary key). Returns count of updated rows |

### Delete

| Method | Description |
|--------|-------------|
| `delete(array\|int\|string $where): int` | Delete entities by condition or primary key. Returns count of deleted rows |
| `deleteByList(array $values, ?string $field = null): int` | Delete entities whose `$field` value is in `$values` (defaults to primary key). Returns count of deleted rows |
| `truncate(): self` | Remove all rows from the table |

### `force(bool $value = true): self`

By default, `create()` and `update()` only fill attributes listed in the model's `$fillable` array. Use `force()` to bypass fillable restrictions and write all model fields (including guarded ones):

```php
$this->force()->create($data);
$this->force()->update($where, $data);
$this->force()->updateMany($where, $data);
$this->force()->updateByList($ids, $data);
```

`force()` is chainable and resets automatically after the next query.

## Soft Delete Support

For models using Laravel's `SoftDeletes` trait, the following methods are available:

### Scoping

| Method | Description |
|--------|-------------|
| `withTrashed(bool $enable = true): self` | Include soft-deleted entities in query results |
| `onlyTrashed(bool $enable = true): self` | Return only soft-deleted entities |

Both methods are chainable and apply to the next query only.

### Restore

| Method | Description |
|--------|-------------|
| `restore(array\|int\|string $where): int` | Restore soft-deleted entities by condition or primary key. Returns count of restored rows |
| `restoreByList(array $values, ?string $field = null): int` | Restore soft-deleted entities whose `$field` value is in `$values` (defaults to primary key). Returns count of restored rows |

### Examples

```php
// Include soft-deleted entities in results
$this->withTrashed()->get();

// Get only soft-deleted entities
$this->onlyTrashed()->get();

// Restore by condition
$this->restore(['status' => 'banned']);

// Restore by list of IDs
$this->restoreByList([1, 2, 3]);

// Permanently delete (bypass soft delete)
$this->force()->delete($where);
$this->force()->deleteByList([1, 2, 3]);
```

## Eager Loading

### `with(array|string $relations): self`

Sets relations to eager load on the next query. Accepts a relation name or an array of relation names. Resets automatically after the query.

```php
$this->with(['posts', 'posts.comments'])->find($id);
```

### `withCount(array|string $relations): self`

Sets relations whose count should be loaded on the next query. Supports nested dot notation — the count is loaded on the intermediate relation. Resets automatically after the query.

```php
// Load post counts on users and comment counts on each post (appended as posts.comments_count)
$this->withCount(['posts', 'posts.comments'])->find($id);
```

Both `with()` and `withCount()` can also be passed as filter keys in `searchQuery()`:

## Search and Filtering

`SearchTrait` (included via `EntityControlTrait`) provides a search pipeline with automatic filter resolution, manual filter methods, ordering, and pagination.

### Basic Usage

```php
public function search(array $filters): LengthAwarePaginator
{
    return $this
        ->searchQuery($filters)
        ->filterByQuery(['name', 'email'])
        ->getSearchResults();
}
```

### `searchQuery(array $filters): self`

Initializes the query with eager loading and soft-delete scoping from `$filters`, then auto-applies all non-reserved filters by suffix:

| Suffix | Operator | Example filter key |
|--------|----------|--------------------|
| `_in_list` | `whereIn` | `status_in_list` |
| `_not_in_list` | `whereNotIn` | `status_not_in_list` |
| `_gte` | `>=` | `age_gte` |
| `_gt` | `>` | `price_gt` |
| `_lte` | `<=` | `age_lte` |
| `_lt` | `<` | `price_lt` |
| `_from` | `>=` | `created_at_from` |
| `_to` | `<=` | `created_at_to` |
| *(none)* | `=` | `status` |

Reserved filter keys (`with`, `with_count`, `with_trashed`, `only_trashed`, `query`, `order_by`, `all`, `per_page`, `page`, `desc`) are processed separately and never applied as field conditions.

To add custom reserved filter names, call `setAdditionalReservedFilters()` in the repository constructor:

```php
$this->setAdditionalReservedFilters('coach_id', 'contact_id');
```

These keys will be skipped by the auto-filter logic and can be handled manually in a custom `search()` method.

### Manual Filter Methods

Use these after `searchQuery()` for fine-grained control:

| Method | Description |
|--------|-------------|
| `filterBy(string $field, ?string $filterName = null): self` | Exact match (`=`). `$filterName` defaults to the last segment of `$field`. Supports dot notation for relations via `whereHas` |
| `filterByList(string $field, ?string $filterName = null): self` | `whereIn` condition. Same dot-notation and filter name resolution as `filterBy` |
| `filterByQuery(array $fields, string $mask = "'%{{ value }}%'"): self` | Full-text search using `LIKE`/`ILIKE` across multiple fields when `$filters['query']` is set. Fields joined with `OR`. `$mask` controls the pattern — `{{ value }}` is replaced with the search term. Supports dot notation |
| `filterGreater(string $field, bool $isStrict = true, ?string $filterName = null): self` | `>` (strict) or `>=` (non-strict) condition. Reads from `$filters[$filterName]`. `$filterName` defaults to `'from'` |
| `filterLess(string $field, bool $isStrict = true, ?string $filterName = null): self` | `<` (strict) or `<=` (non-strict) condition. Reads from `$filters[$filterName]`. `$filterName` defaults to `'to'` |
| `filterValue(string $field, string $sign, mixed $value): self` | Applies any comparison operator with an explicit `$value` (not from `$filters`). Skips if `$value` is empty |
| `orderBy(?string $default = null, bool $defaultDesc = false): self` | Sorts by `$filters['order_by']` / `$filters['desc']`. Falls back to `$default` (or primary key). Supports dot notation via `orderByRelated`. Appends secondary sort by default field as tiebreaker |

### `getSearchResults(): LengthAwarePaginator`

Finalizes the search by calling `orderBy()`, then returns paginated results. Pagination is controlled via filters:

| Filter | Description | Default |
|--------|-------------|---------|
| `per_page` | Items per page | `config('defaults.items_per_page')` |
| `page` | Current page | `1` |
| `all` | Return all results without pagination | `false` |
| `order_by` | Field to sort by | Primary key |
| `desc` | Sort descending | `false` |

When `all: true`, all results are fetched and wrapped in a single-page `LengthAwarePaginator` via `wrapPaginatedData()`.

### `paginate(): LengthAwarePaginator`

Executes the current query with `per_page` and `page` from `$filters`. Called internally by `getSearchResults()`, but can be used directly for custom pagination flows.

### `wrapPaginatedData(Collection $data): LengthAwarePaginator`

Wraps an already-fetched `Collection` into a `LengthAwarePaginator` (single page, total = collection size). Used internally when `all: true`. Override in a subclass to customise the paginator structure.

### `getModifiedPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator`

Hook called on every paginator before it is returned by `getSearchResults()` or `wrapPaginatedData()`. The default implementation is a no-op. Override in a repository to transform results — for example, to append computed fields:

```php
public function getModifiedPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
{
    $paginator->getCollection()->transform(function (User $user) {
        $user->full_name = "{$user->first_name} {$user->last_name}";
        return $user;
    });

    return parent::getModifiedPaginator($paginator);
}
```

### `getSearchQuery(): Query`

Returns the current Eloquent query builder instance. Useful for applying raw query modifications or debugging after `searchQuery()` has been called.

```php
$query = $this->searchQuery($filters)->filterBy('status')->getSearchQuery();
$query->toSql(); // inspect generated SQL
```

### Usage with filters:

```php
$service->search([
    'role_name' => 'admin',
    'query' => 'john',
    'age_from' => 18,
    'age_to' => 65,
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
