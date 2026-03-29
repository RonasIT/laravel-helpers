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

---

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

> [!NOTE]
> `force()` is chainable and resets automatically after the next query.

---

## Soft Delete Support

For models using Laravel's `SoftDeletes` trait, the following methods are available:

### Scoping

| Method | Description |
|--------|-------------|
| `withTrashed(bool $enable = true): self` | Include soft-deleted entities in query results |
| `onlyTrashed(bool $enable = true): self` | Return only soft-deleted entities |

> [!NOTE]
> Both methods are chainable and apply to the next query only.

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

---

## Eager Loading

| Method | Description |
|--------|-------------|
| `with(array\|string $relations): self` | Sets relations to eager load on the next query. Resets after the query |
| `withCount(array\|string $relations): self` | Loads relation counts. Supports dot notation. Resets after the query |

---

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

| Suffix | Operator | Example filter key | Example filter value |
|--------|----------|--------------------|----------------------|
| `_in_list` | `whereIn` | `status_in_list` | `['active', 'pending']` |
| `_not_in_list` | `whereNotIn` | `status_not_in_list` | `[1, 2]` |
| `_gte` | `>=` | `age_gte` | `18` |
| `_gt` | `>` | `price_gt` | `100` |
| `_lte` | `<=` | `age_lte` | `65` |
| `_lt` | `<` | `price_lt` | `1000` |
| `_from` | `>=` | `created_at_from` | `'2024-01-01'` |
| `_to` | `<=` | `created_at_to` | `'2024-12-31'` |
| *(none)* | `=` | `status` | `'active'` |

### Reserved Filter Names

These filter keys are handled internally and should not be used as field filters: `with`, `with_count`, `with_trashed`, `only_trashed`, `query`, `order_by`, `all`, `per_page`, `page`, `desc`.

To add custom reserved filter names, call `setAdditionalReservedFilters()` in the repository constructor:

```php
$this->setAdditionalReservedFilters('coach_id', 'contact_id');
```

> [!NOTE]
> These keys will be skipped by the auto-filter logic and can be handled manually in a custom `search()` method.

### Manual Filter Methods

Use these after `searchQuery()` for fine-grained control:

| Method | Description |
|--------|-------------|
| `filterBy(string $field, ?string $filterName = null): self` | Exact match filter. Supports dot notation for relations (e.g., `role.name`) |
| `filterByList(string $field, ?string $filterName = null): self` | `whereIn` filter |
| `filterByQuery(array $fields, string $mask = "'%{{ value }}%'"): self` | `LIKE` search across multiple fields. Supports relation fields via dot notation |
| `filterGreater(string $field, bool $isStrict = true, ?string $filterName = null): self` | `>` / `>=` filter. Reads from `$filters[$filterName]`, defaults to `'from'` |
| `filterLess(string $field, bool $isStrict = true, ?string $filterName = null): self` | `<` / `<=` filter. Reads from `$filters[$filterName]`, defaults to `'to'` |
| `filterValue(string $field, string $sign, mixed $value): self` | Applies a comparison condition with a given value directly. Skips if empty |
| `orderBy(?string $default = null, bool $defaultDesc = false): self` | Applies ordering by `$filters['order_by']`. Supports dot notation for relation fields |

### Pagination

`getSearchResults()` returns a `LengthAwarePaginator`. Pagination is controlled via filters:

| Filter | Description | Default |
|--------|-------------|---------|
| `per_page` | Items per page | `config('defaults.items_per_page')` |
| `page` | Current page | `1` |
| `all` | Return all results without pagination | `false` |
| `order_by` | Field to sort by | Primary key |
| `desc` | Sort descending | `false` |

### Advanced Methods

| Method | Description |
|--------|-------------|
| `paginate(): LengthAwarePaginator` | Executes the query with `per_page` and `page` from `$filters`. Called internally by `getSearchResults()`, but available for custom pagination flows |
| `wrapPaginatedData(Collection $data): LengthAwarePaginator` | Wraps a `Collection` into a single-page `LengthAwarePaginator`. Used internally when `all: true`. Override to customise the paginator structure |
| `getModifiedPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator` | Hook called on every paginator before it is returned. No-op by default. Override to transform results (e.g. append computed fields) |
| `getSearchQuery(): Query` | Returns the current Eloquent query builder. Useful for raw query modifications or debugging after `searchQuery()` |

[<< Traits][1]
[Services >>][2]

[1]:traits.md
[2]:services.md
