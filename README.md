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
    debug: false                                            # Enable debug logs, in all cases
    skipped_as_true: false                                  # Mark skipped tests as passed
    user_class: 'App\Entity\User'                           # User entity class
    user_identity_property: 'username'                      # Identification property (username/email)
    error_prefix: 'core.error.'                             # Prefix for error messages
    datetime_format: !php/const DateTimeInterface::ATOM     # Default date format
```

### 4. Basic Usage

#### Testing a Complete Entity (CRUD)

```php
<?php

namespace App\Tests\Api;

use EasyApiTests\Core\AbstractApiTestCase;
use EasyApiTests\Crud\CreateTestTrait;
use EasyApiTests\Crud\GetTestTrait;
use EasyApiTests\Crud\UpdateTestTrait;
use EasyApiTests\Crud\DeleteTestTrait;
use EasyApiTests\Crud\GetListTestTrait;

class ProductApiTest extends AbstractApiTestCase
{
    use CreateTestTrait;
    use GetTestTrait; 
    use UpdateTestTrait;
    use DeleteTestTrait;
    use GetListTestTrait;

    // Entity configuration to test
    protected const string baseRouteName = 'api_product';
    protected const string entityClass = 'App\Entity\Product';
    protected const string identifier = 'id';
    protected const string defaultEntityId = '1';
    protected const string defaultEntityNotFoundId = '999';

    // Test users
    protected const string USER_TEST_USERNAME = 'admin@example.com';
    protected const string USER_TEST_PASSWORD = 'password';
    protected const string USER_NORULES_TEST_USERNAME = 'user@example.com';

    // Required fields for validation tests
    protected static function getRequiredFields(): array
    {
        return ['name', 'price'];
    }
}
```

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

The bundle automatically generates expected data files in:
- `tests/Api/{EntityName}/DataSent/` : Data sent during requests
- `tests/Api/{EntityName}/Responses/` : Expected API responses

### 6. Available Features

- ✅ Complete CRUD tests (Create, Read, Update, Delete, List)
- ✅ Authentication and authorization tests
- ✅ Automatic validation of JSON/XML responses
- ✅ Handling of uploaded files
- ✅ Automatic generation of test data
- ✅ Specialized assertions for APIs
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

- Debug logs are displayed with `debug: true` in configuration
- Xdebug is configured for remote debugging
- Tests automatically generate missing data files

## Support

- **Issues:** [GitHub Issues](https://github.com/citizen63000/easy-api-tests/issues)
- **Documentation:** This README and docblocks in the code
- **Examples:** See unit tests in `/tests/Unit/`
