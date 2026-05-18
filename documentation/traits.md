[<< Helpers][1]
[Versioning >>][2]

# Traits

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
