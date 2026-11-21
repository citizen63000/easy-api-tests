# Easy API Tests

A Symfony bundle designed to facilitate testing REST APIs. This bundle provides a set of traits and classes to automatically test CRUD operations of an API with JWT authentication.

## Installation in a third-party project

### 1. Installation via Composer

```bash
composer require --dev citizen63000/easy-api-tests
```

### 2. Bundle Configuration

Add the bundle to `config/bundles.php` (for the test environment only):

```php
<?php

return [
    // ... other bundles
    EasyApiTests\EasyApiTestsBundle::class => ['test' => true],
];
```

### 3. Configuration

Create the file `config/packages/test/easy_api_tests.yaml`:

```yaml
easy_api_tests:
    debug: false                                            # Enable debug logs, in all cases (can also be enabled with the `--debug` CLI flag)
    skipped_as_true: false                                  # Mark skipped tests as passed
    user_class: 'App\Entity\User'                           # User entity class (null = resolved from the `app_user_provider` security provider)
    user_identity_property: 'username'                      # Identification property (username/email)
    error_prefix: 'core.error.'                             # Prefix for error messages
    use_cache: true                                         # Cache parsed fixture yml files and the "tables to clean" query (clear the cache after editing fixtures!)
    datetime_format: !php/const DateTimeInterface::ATOM     # Default date format
```

### 4. Basic Usage

#### Testing a Complete Entity (CRUD)

The recommended pattern is one **abstract class per resource** holding the configuration, extended by concrete test classes using the CRUD traits:

```php
<?php

namespace App\Tests\Functional\Product;

use EasyApiTests\Core\AbstractApiTestCase;

abstract class AbstractProduct extends AbstractApiTestCase
{
    protected const string baseRouteName = 'api_product';   // route prefix of the resource
    protected const string entityClass = 'App\Entity\Product';
    protected const string identifier = 'id';
    protected const string defaultEntityId = '1';
    protected const string defaultEntityNotFoundId = '999';

    protected static array $additionalInitFiles = ['App/Product.yml']; // extra fixture yml files, loaded in addition to init.yml

    protected static function getRequiredFields(): array
    {
        return ['name', 'price']; // required fields of the create form (used to generate createWithOnlyRequiredFields.json)
    }
}
```

```php
<?php

namespace App\Tests\Functional\Product;

use EasyApiTests\Crud\CreateTestTrait;
use EasyApiTests\Crud\GetTestTrait;
use EasyApiTests\Crud\GetListTestTrait;
use EasyApiTests\Crud\UpdateTestTrait;
use EasyApiTests\Crud\DeleteTestTrait;

class ProductTest extends AbstractProduct
{
    use CreateTestTrait;
    use GetTestTrait;
    use GetListTestTrait;
    use UpdateTestTrait;
    use DeleteTestTrait;
}
```

#### Route naming convention

The CRUD traits resolve routes from `baseRouteName` using these suffixes:

| Route constant       | Suffix          | HTTP method | Purpose            |
|----------------------|-----------------|-------------|--------------------|
| `get{BaseRouteName}` | `_get`          | GET         | Get one entity     |
|                      | `_list`         | GET         | Filtered list      |
|                      | `_create`       | POST        | Create             |
|                      | `_clone`        | POST        | Clone              |
|                      | `_update`       | PUT         | Update             |
|                      | `_delete`       | DELETE      | Delete             |
|                      | `_download`     | GET         | Download a file    |
|                      | `_describe_form`| GET         | Describe POST/PUT form |

#### Testing a Specific Endpoint

```php
<?php

namespace App\Tests\Api;

use EasyApiTests\Core\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomApiTest extends AbstractApiTestCase
{
    public function testCustomEndpoint(): void
    {
        $apiOutput = self::httpGetWithLogin(['name' => 'api_custom_route']);

        $this->assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $this->assertArrayHasKey('data', $apiOutput->getData());
    }
}
```

### 5. Structure of Test Data Files

Files are created **next to the concrete test class** (not in a central directory):

- `DataSent/Create/*.json`, `DataSent/Update/*.json` : data sent with POST/PUT requests (e.g. `createWithAllFields.json`, `createWithOnlyRequiredFields.json`). On first run they are **auto-generated** from the corresponding form class (`entityCreateTypeClass` / `entityUpdateTypeClass` of the controller) via `FormSerializer`: each field gets a default value according to its type (string, lorem ipsum for texts, random int, ...). Edit them afterwards to define the real test datasets; they must be committed.
- `Responses/{Create,Get,GetList,Update,Clone,Download,DescribeForm}/*.json` : expected API responses. On first run they are **auto-generated** from the actual response (dates replaced by `\assertDateTime()` and the uuid by `\assertUUID()`), then replayed as the reference on subsequent runs. They must be committed.

Special values in expected responses (assessable functions, see `AbstractApiTestCase::$assessableFunctions`):

- `\assertDateTime()`, `\assertDate()`, `\assertDateTimeNow()` : date assertions
- `\assertUUID()` : uuid format assertion
- `\assertRegex()`, `\assertId()`, `\assertFileUrl()`, `\assertFileName()` : regex / id / file assertions

Additional functions can be registered with `addAdditionalAssessableFunction()` / `$additionalAssessableFunctions`.

### 6. Loading Test Data (fixtures)

At setup, the bundle loads the files listed in `initFiles` (default `['init.yml']`) plus `$additionalInitFiles`, resolved under `tests/data/csv/`:

- each `.yml` file maps an **entity manager name** to its data files, each data file being mapped to a **table name**:

  ```yaml
  default:
      'App/Product/product.csv': 'product'   # the csv file always carries the table name
  ```

- `.csv` files are converted to `LOAD DATA LOCAL INFILE ... INTO TABLE <table>` queries (the header line defines the columns), `.sql` files are executed as-is.
- **The value (table name) is mandatory**: an empty value generates an invalid `LOAD DATA ... INTO TABLE` and fails the whole setup.
- If a csv has a foreign key to another entity, also load that entity's yml file in `$additionalInitFiles`, in dependency order.
- The database is cleaned between suites (`DELETE FROM` + `AUTO_INCREMENT=1` on all non-empty tables, `FOREIGN_KEY_CHECKS` disabled during the process). Tables listed in `$excludedTablesToClean` are never emptied (use it for referential tables such as `cgi_*`, `bofip_article`), and `$schemas` restricts the schemas to inspect.
- The parsed yml files and the "not empty tables" query are cached (`use_cache: true`): **after any change to a fixture yml file, clear the cache** (e.g. `rm -rf var/cache/*`), otherwise the change is not taken into account.

### 7. Setup behavior

- `$executeSetupOnAllTest` (default `true`, set via `initExecuteSetupOnAllTest()`) controls whether the setup (DB cleaning + data loading) is replayed **before each test** of the class. The read-only traits (`GetTestTrait`, `GetListTestTrait`, `DeleteTestTrait`, `DownloadTestTrait`, `GetDescribeFormTestTrait`) set it to `false` so data is loaded only once.
- `true` = database reloaded before each test (independent tests but slower, required when a test modifies data, e.g. a delete test); `false` = data loaded once at first setup (faster, but tests must be read-only or run in a specific order).
- Override it in a concrete test class:

  ```php
  protected static function initExecuteSetupOnAllTest(): void
  {
      static::$executeSetupOnAllTest = true; // or false
  }
  ```

- `$loadDataOnSetup` / `initLoadDataOnSetup()` control the same way the reloading of the data only (without the full cleaning).

### 8. Available CRUD traits

One concrete test file per trait:

| Trait                       | Tests provided                                                                                              |
|-----------------------------|-------------------------------------------------------------------------------------------------------------|
| `CreateTestTrait`           | `testCreateWithAllFields`, `testCreateWithOnlyRequiredFields`, `testCreateWithoutRequiredFields`, `testCreateWithoutAuthentication`, `testCreateWithoutRights` |
| `GetTestTrait`              | `testGet`, `testGetNotFound`, `testGetWithoutAuthentication`, `testGetWithoutRight`                          |
| `GetListTestTrait`          | `testGet`, `testGetAll`, `testGetPageLimit`, `testGetWithoutAuthentication`, `testGetWithoutRight`           |
| `UpdateTestTrait`           | `testPutExistingEntity`, `testPutUnexistingEntity`, `testPutWithoutAuthentication`, `testPutWithoutRight`    |
| `DeleteTestTrait`           | `testDelete`, `testDeleteNotFound`, `testDeleteWithoutAuthentication`, `testDeleteWithoutRight403`           |
| `GetDescribeFormTestTrait`  | `testForPost`, `testForPut`, `testGetWithoutAuthentication`, `testGetWithoutRightC403`                       |
| `CloneTestTrait`            | `testClone`, `testCloneWithoutAuthentication`, `testCloneWithoutRights`                                     |
| `DownloadTestTrait`         | `testDownload`, `testGetNotFound`, `testGetWithoutAuthentication`, `testGetWithoutRight`                    |
| `AuthenticationTestTrait`   | `testAuthenticateWithGoodCredentials`, `testAuthenticateWithBadCredentials`, `testRefreshToken`, `testRefreshTokenWithBadRefreshToken`, `testLogout` |
| `Generic401TestTrait`       | `testGet401`                                                                                                 |

Each trait delegates to reusable `doTest*()` functions (in `src/Crud/Functions/`), e.g. `doTestCreate($filename, $params, $testGetAfterCreate, $userLogin)`, `doTestCreateInvalid(...)`, `doTestCreateWithoutAuthentication()`, `doTestCreateWithoutRight($params, $userLogin)`, `doTestGetListFiltered($filename, $page, $limit, $filters, $sort, ...)`, `doTestGetListPaginate(...)`, `doTestUpdate(...)`, `doTestDelete(...)`, `doTestClone(...)`, `doTestDownload(...)`, `doTestGetDescribeFormForPost/Put()` — call them from your own test methods to cover specific cases.

**Rights rule**: when a route is restricted to one or more roles in `security.yaml` (e.g. POST/PUT/DELETE reserved to `ROLE_EXPERT`/`ROLE_ADMIN`), test **each role in both directions**: a user without the role (e.g. `self::USER_TEST_USERNAME` → 403, via `doTestCreateWithoutRight` / `doTestUpdateWithoutRight` / `doTestDeleteWithoutRight`) **and** a user with the role (e.g. `self::USER_TEST_EXPERT_USERNAME` → success).

If the provided traits do not cover all the business cases, **add manual tests** in the concrete test class to guarantee good coverage (edge cases, rights, specific validations, etc.).

### 9. Configuration properties of `AbstractApiTestCase`

Constants: `baseRouteName`, `entityClass`, `requiredFields`, `defaultEntityId` (`'1'`), `defaultEntityNotFoundId` (`'99999999'`), `identifier` (`'id'`), `initFiles` (`['init.yml']`), `regexp_uuid`, `strictComparison`, `assessableFunctions`, test users `USER_TEST_ID`/`USER_TEST_USERNAME`/`USER_TEST_PASSWORD`, `USER_ADMIN_TEST_*`, `USER_NORULES_TEST_*`, `TOKEN_ROUTE_NAME` (`'api_login'`).

Static properties: `$additionalInitFiles`, `$excludedTablesToClean`, `$additionalAssessableFunctions`, `$user` / `$password` / `$token`, `$executeSetupOnAllTest`, `$loadDataOnSetup`, `$debug`, `$debugLevel` (`DEBUG_LEVEL_SIMPLE` / `DEBUG_LEVEL_ADVANCED`), `$artifactTestDir`, `$schemas`.

Helpers: `httpGet` / `httpPost` / `httpPut` / `httpDelete` and their `...WithLogin($route, $userLogin, ...)` variants (JWT token generated through the `lexik_jwt_authentication.jwt_manager` service), `executeRequest($method, $route, $content, $withToken, $formatIn, $formatOut, $extraHttpHeaders)`, `getUrl()`, `getToken()` / `generateToken()`, `getFileBag()` for file uploads (`FileBag`), `execCommand()` / `callCommand()` to run console commands, plus assertions `assertApiEntityResult()`, `assertApiEntityListResult()`, `assertApiProblemError()`, `assertFields()`, `assertAssessableContent()`, `countEntities()`, `getLastEntityId()` / `getNextEntityId()`.

Debug logs are printed when `debug: true` or when phpunit is run with `--debug`; artifacts (uploaded files) are stored under `tests/artifacts`.

### 10. Available Features

- ✅ Complete CRUD tests (Create, Read, Update, Delete, List, Clone, Download, DescribeForm)
- ✅ Authentication and authorization tests (JWT)
- ✅ Automatic validation of JSON/XML responses
- ✅ Handling of uploaded files
- ✅ Automatic generation of test data (DataSent) and expected responses (Responses)
- ✅ Specialized assertions for APIs (`\assertDateTime()`, `\assertUUID()`, ...)
- ✅ Integrated JWT support

## Local Development with Docker

### Prerequisites

- Docker and Docker Compose installed

### Installation for Development

1. **Clone the repository :**
   ```bash
   git clone https://github.com/citizen63000/easy-api-tests.git
   cd easy-api-tests
   ```

2. **Start the Docker environment:**
   ```bash
   docker compose up -d
   ```

3. **Install dependencies:**
   ```bash
   docker compose exec app composer install
   ```

### Development Commands

#### Run all tests
```bash
docker compose exec app composer test
```

#### Run Core tests only
```bash
docker compose exec app composer test -- tests/Unit/Core/
```

#### Run Crud tests only  
```bash
docker compose exec app composer test -- tests/Unit/Crud/
```

#### Tests with a detailed report
```bash
docker compose exec app composer test -- --testdox
```

#### Check code style
```bash
docker compose exec app composer clean-code
```

#### Access the container for debugging
```bash
docker compose exec app sh
```

### Development Project Structure

```
easy-api-tests/
├── src/                          # Bundle source code
│   ├── Core/                     # Main classes
│   │   ├── AbstractApiTestCase.php
│   │   ├── ApiOutput.php
│   │   └── ...
│   ├── Crud/                     # Traits for CRUD tests
│   │   ├── CreateTestTrait.php
│   │   ├── Functions/            # Test implementations
│   │   └── ...
│   └── DependencyInjection/      # Symfony configuration
├── tests/                        # Unit tests
│   └── Unit/
│       ├── Core/                 # Core classes tests
│       └── Crud/                 # Crud classes tests
├── docker-compose.yml            # Docker configuration
├── Dockerfile                    # PHP Docker image
└── README.md
```

### Environment Variables

The `docker-compose.yml` file automatically configures:
- PHP 8.3 with required extensions
- Xdebug for debugging
- Volumes for real-time development

### Contribution workflow

1. Create a feature branch
2. Develop and test with `docker compose exec app php vendor/bin/phpunit`
3. Check code style with `composer clean-code`
4. Commit changes
5. Create a Pull Request

### Debug and development

- Debug logs are displayed with `debug: true` in configuration (or the `--debug` CLI flag)
- Xdebug is configured for remote debugging
- Tests automatically generate missing data files

## Support

- **Issues:** [GitHub Issues](https://github.com/citizen63000/easy-api-tests/issues)
- **Documentation:** This README and docblocks in the code
- **Examples:** See unit tests in `/tests/Unit/`
