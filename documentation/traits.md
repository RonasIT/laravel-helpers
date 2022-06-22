[<< Helpers][1]
[Services >>][2]

# Traits

## EntityControlTrait

Provides CRUD-based methods to work with database-related entities.

## FixturesTrait

This is a powerful tool which makes testing process understandable and cleaner.

### Fixtures

All additional data such as the results of the operation or input data can be
presented via `.json` files and grouped by the test case name in the path
`/tests/fixtures/{$testClassName}`.

The main fixture helpers are following:
- `getFixture($fn, $failIfNotExists = true)`
- `getJsonFixture($fn, $assoc = true)`
- `assertEqualsFixture($fixture, $data, bool $exportMode = false)`
- `exportJson($fixture, $data)`
- `exportContent($content, $fixture)`
- `exportFile($response, $fixture)`

### DB prefilling

 Trait also provides an ability to use the same initial database state before
each test running. Testing database will be automatically cleared in the `setUp`
method and restored from the `/tests/fixtures/{$testClassName}/dump.sql` file.

## MockHttpRequestTrait

This trait was designed to make external http sources testing more convenient. This trait 
mocks `HttpRequestService` and give an ability to mock get and post http requests

## SearchTrait

This trait implements `search` data function. It contains methods for filtering by fields of model,
by model relations, searching by row data as query.

Just init search with `$this->getSearchQuery()` and then you can chain different filters to make
search you need. Available search methods are:
* `filterBy()` - filtering by model field
* `filterByQuery(['field1', 'field2', ...])` - filtering by model field1 and field2 with "LIKE" operator
* `filterByQueryOnRelation($relation, [fields])` - filtering with "LIKE" operator by related model fields
* `filterByRelation($relation, $field)` - filtering by related model field.

Also you can specify results order using `order_by()` method and specify relations by `with()`
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

Add multi language support for models.
Requirements: translation model have to be named as `{modelName}Translation` and contains locale field.
For example, for model `Product` you should create `ProductTranslation` model and create fields you want translate plus required `locale` field.

## MigrationTrait

Gives you some convenient methods to create foreign keys, bridge tables for many-to-many relationships.
Methods list: 
* `addForeignKey($fromEntity, $toEntity, $needAddField = false)` - creates foreign key from table to table
* `dropForeignKey($fromEntity, $toEntity, $needDropField = false)` - drops foreign key from table to table
* `createBridgeTable($fromEntity, $toEntity)` - creates bridge table for many-to-many relation

[<< Helpers][1]
[Services >>][2]

[1]:helpers.md
[2]:services.md
