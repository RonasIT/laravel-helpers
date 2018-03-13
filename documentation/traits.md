## Traits

### FixturesTrait
This traits designed for make testing understandable and cleaner.
All auxiliary data such as the results of the operation can be placed in a way corresponding 
to the following mask **base_path("tests/fixtures/{$testClassName}/{$fixture}")**, 
and is easily obtained by the method *$this->getFixture($fixture)* or through *$this->getJsonFixture($fixture)*, 
in which will be performed automatically decode json-data.
Also you can tune your TestCase for restore dump of database witch will be places in 
**base_path("tests/fixtures/{$testClassName}/dump.sql")** and method for comfortable getting of Json-responses

### EntityControlTrait
This trait implement all typical behavior of repositories which should be wrapper under models. It contains all crud 
operations and 