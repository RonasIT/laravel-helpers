## Traits

### FixturesTrait
This trait is designed to make testing understandable and cleaner.
All auxiliary data such as the results of the operation can be placed in a way corresponding 
to the following mask **base_path("tests/fixtures/{$testClassName}/{$fixture}")**, 
and is easily obtained by the method *$this->getFixture($fixture)* or through *$this->getJsonFixture($fixture)*, 
in which will be performed automatically decode json-data.
Also you can tune your TestCase for restore dump of database witch will be places in 
**base_path("tests/fixtures/{$testClassName}/dump.sql")** and method for comfortable getting of Json-responses

### EntityControlTrait
This trait implement all typical behavior of repositories which should be wrapper under models. It contains all crud 
operations. 

### MockHttpRequestTrait

This trait was designed to make external http sources testing more convenient. This trait 
mocks `HttpRequestService` and give an ability to mock get and post http requests

### SearchTrait

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

### TranslationTrait

Add multi language support for models.
Requirements: translation model have to be named as `{modelName}Translation` and contains locale field.
For example, for model `Product` you should create `ProductTranslation` model and create fields you want translate plus required `locale` field.

### MigrationTrait

Gives you some convenient methods to create foreign keys, bridge tables for many-to-many relationships.
Methods list: 
* `addForeignKey($fromEntity, $toEntity, $needAddField = false)` - creates foreign key from table to table
* `dropForeignKey($fromEntity, $toEntity, $needDropField = false)` - drops foreign key from table to table
* `createBridgeTable($fromEntity, $toEntity)` - creates bridge table for many-to-many relation