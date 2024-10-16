<?php

namespace EasyApiTests;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractApi extends WebTestCase
{
    use ApiTestRequesterTrait;
    use ApiTestDataLoaderTrait;
    use ApiTestAssertionsTrait;
    use TestUtilsTrait;

    // region Constants

    protected const ?string baseRouteName = null;
    protected const ?string entityClass = null;

    /** @var array associative array with error message or fields list for example ['firstname', 'age' => 'core.error.age.invalid'] */
    protected const array requiredFields = [];
    protected const string defaultEntityId = '1';
    protected const string identifier = 'id';

    public const int USER_TEST_ID = 1;
    public const string USER_TEST_USERNAME = '[API-TESTS]';
    public const string USER_TEST_EMAIL = 'api-tests@example.com';
    public const string USER_TEST_PASSWORD = 'IloveToBreakYourHopes!';
    public const int USER_ADMIN_TEST_ID = 2;
    public const string USER_ADMIN_TEST_USERNAME = '[API-TESTS-ADMIN]';
    public const string USER_ADMIN_TEST_EMAIL = 'api-tests-admin@example.com';
    public const string USER_ADMIN_TEST_PASSWORD = 'IloveToBreakYourHopes!';
    public const int USER_NORULES_ADMIN_TEST_ID = 3;
    public const string USER_NORULES_TEST_USERNAME = '[API-TESTS-NO-RULES]';
    public const string USER_NORULES_TEST_EMAIL = 'api-tests-no-rules@example.com';
    public const string USER_NORULES_TEST_PASSWORD = 'u-norules-pwd';

    public const string TOKEN_ROUTE_NAME = 'api_login';

    public const int DEBUG_LEVEL_SIMPLE = 1;
    public const int DEBUG_LEVEL_ADVANCED = 2;

    public const string ARTIFACT_DIR = DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'artifacts';

    protected const array initFiles = ['init.yml'];

    public const string regexp_uuid = '[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+\-[a-zA-Z0-9]+';
    public const string regexp_uid = '[a-zA-Z0-9]+';

    /** @var string[] */
    protected const array assessableFunctions = [
        'assertDateTime',
        'assertDate',
        'assertDateTimeNow',
        'assertFileUrl',
        'assertFileName',
        'assertUUID',
    ];

    // endregion

    // region Settings

    protected static ?string $artifactTestDir = null;
    protected static array $additionalInitFiles = [];
    protected static array $excludedTablesToClean = [];
    protected static array $additionalAssessableFunctions = [];

    /**
     * @todo set private in php 7.4
     */
    protected static bool $debug = false;
    protected static int $debugLevel = self::DEBUG_LEVEL_ADVANCED;
    protected static bool $showQuery = false;
    protected static int $debugTop = 0;
    public static array $defaultTokens = [];

    /**
     * Symfony env, should be TEST.
     */
    protected static ?string $env = 'TEST';

    /**
     * Indicates if you want launch setup on all tests in your test class.
     */
    protected static ?bool $executeSetupOnAllTest = null;

    /**
     * Indicates if you want launch setup on all tests in your test class.
     */
    protected static ?bool $loadDataOnSetup = null;

    /**
     * Indicates if the first launch need to launch.
     */
    protected static bool $launchFirstSetup = true;

    // endregion

    // region Parameters

    /**
     * User API username.
     */
    protected static ?string $user = self::USER_TEST_USERNAME;
    /**
     * User API password.
     */
    protected static ?string $password = self::USER_TEST_PASSWORD;
    /**
     * User API token.
     */
    protected static ?string $token = null;

    // endregion

    // region Utils

    /**
     * simulates a browser and makes requests to a Kernel object.
     */
    protected static ?KernelBrowser $client = null;
    protected static ?Router $router;
    protected static ?string $projectDir;

    /**
     * Check if engine is initialized.
     * @throws \Exception
     */
    final protected static function isInitialized(): bool
    {
        return
            null !== self::$client
            && null !== static::getEntityManager()
            && null !== self::$router
            ;
    }

    /**
     * Initialize engine.
     */
    protected static function initialize(): void
    {
        self::logStep();
        static::rebootClient();

        self::$projectDir = static::getContainer()->getParameter('kernel.project_dir');

        static::initExecuteSetupOnAllTest();
        static::initLoadDataOnSetup();
        self::initializeLoader();
        self::initializeRequester();

        global $argv;
        static::$debug = static::getContainer()->getParameter('easy_api_tests.debug') || in_array('--debug', $argv, true);
    }

    protected static function rebootClient(): void
    {
        static::ensureKernelShutdown();
        static::$client = self::createClient(['debug' => static::$useProfiler]);
        if (static::$useProfiler) {
            static::$client->enableProfiler();
        }
        self::$router = static::getContainer()->get('router');
    }

    /**
     * Show where you are (Class::method()).
     *
     * @param bool $debugNewLine Adds a new line before debug log
     */
    protected static function logStep(bool $debugNewLine = false): void
    {
        if (true === static::$debug) {
            $backTrace = debug_backtrace()[1];
            self::logDebug("\e[42;31m[STEP]\e[0m 👁️ \e[92m{$backTrace['class']}::{$backTrace['function']}()\e[0m", self::DEBUG_LEVEL_ADVANCED, $debugNewLine);
        }
    }

    /**
     * Show a debug line, if debug activated.
     *
     * @param string $message      The message to log
     * @param int    $debugLevel   Debug level
     * @param bool   $debugNewLine Adds a new line before debug log
     */
    protected static function logDebug(string $message, int $debugLevel = self::DEBUG_LEVEL_SIMPLE, bool $debugNewLine = false): void
    {
        if (true === static::$debug && $debugLevel <= static::$debugLevel) {
            fwrite(
                STDOUT,
                ($debugNewLine ? "\n" : '')
                ."\e[33m🐞"
                .((self::DEBUG_LEVEL_ADVANCED === static::$debugLevel) ? ' ['.str_pad(++self::$debugTop, 3, '0', STR_PAD_LEFT).']' : '')
                ."\e[0m"
                ."{$message}\n"
            );
        }
    }

    /**
     * Show an error line and write it in log file.
     */
    protected static function logError(string $message): void
    {
        fwrite(STDOUT, "\e[31m✘\e[91m {$message}\e[0m\n");

        try {
            $logger = static::getContainer()->get('logger');
            $logger->error(str_replace("\t", '', $message));
        } catch (\Exception $exception) {
            fwrite(STDOUT, "\e[31m✘\e[91m {$exception->getMessage()}\e[0m\n");
        }
    }

    /**
     * Count entities.
     *
     * @throws NonUniqueResultException|NoResultException
     */
    protected static function countEntities(string $entityName, $condition = null, array $parameters = []): int
    {
        $qb = static::getEntityManager()->getRepository($entityName)
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
        ;
        if (null !== $condition) {
            $qb->where($condition);
        }
        if (!empty($parameters)) {
            $qb->setParameters($parameters);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception|\Exception
     */
    protected static function getLastEntityId(string $className = null): int
    {
        $tableName = static::getEntityManager()->getClassMetadata($className ?? static::entityClass)->getTableName();
        $schemaName = static::getEntityManager()->getClassMetadata($className ?? static::entityClass)->getSchemaName();
        $stmt = static::getEntityManager()->getConnection()->prepare("SELECT max(id) as id FROM {$schemaName}.{$tableName}");

        return (int) $stmt->execute()->fetchOne(0);
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    protected static function getNextEntityId(string $className = null): ?int
    {
        return ($id = self::getLastEntityId($className)) ? ++$id : null;
    }

    // endregion

    // region User management

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::logStep();
        self::doSetup();

        self::$launchFirstSetup = false;
    }

    /**
     * Initialize $executeSetupOnAllTest, override it to change it.
     */
    protected static function initExecuteSetupOnAllTest(): void
    {
        if (null === static::$executeSetupOnAllTest) {
            static::$executeSetupOnAllTest = true;
        }
    }

    /**
     * Initialize $loadDataOnSetup, override it to change it.
     */
    protected static function initLoadDataOnSetup(): void
    {
        if (null === static::$loadDataOnSetup) {
            static::$loadDataOnSetup = true;
        }
    }

    /**
     * {@inheritdoc}
     * @throws OptimisticLockException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        self::logStep();

        static::rebootClient();

        if (true === static::$loadDataOnSetup && (true === static::$executeSetupOnAllTest || (false === static::$executeSetupOnAllTest && false === static::$launchFirstSetup))) {
            static::loadData();
        }

        if (false === static::isInitialized() || (true === static::$executeSetupOnAllTest && true === static::$launchFirstSetup)) {
            self::doSetup();
        } elseif (true === static::$launchFirstSetup) {
            // If no reset rollback user test & its rights
            self::defineUserPassword();
        }

        static::$launchFirstSetup = true;
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        self::logStep();
        static::$executeSetupOnAllTest = null;
        self::$token = null;
    }

    /**
     * Performs setup operations.
     * @throws \Exception
     */
    final protected static function doSetup(): void
    {
        static::logStep();
        if (!static::isInitialized()) {
            static::initialize();
        } else {
            self::initExecuteSetupOnAllTest();
        }
    }

    /**
     * Define user & password for tests.
     */
    protected static function defineUserPassword(string $user = null, string $password = null): void
    {
        static::logStep();
        if (!self::$user || !$user && !$password) {
            self::$user = self::USER_TEST_USERNAME;
            self::$password = self::USER_TEST_PASSWORD;
        } else {
            static::logDebug("\e[32m[USR]\e[0m😀 New user : \e[32m{$user}\e[0m with password \e[32m{$password}\e[0m");
            self::$user = $user;
            self::$password = $password;
        }

        static::$token = null;
    }

    // endregion

    // region Requests management

    /**
     * Get FileBag for the filename.
     */
    protected function getFileBag(array $filenames): FileBag
    {
        $fileDir = static::getContainer()->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'artifacts';
        $fileBag = new FileBag();
        foreach ($filenames as $field => $filename) {
            $fileBag->addFile($field, $fileDir.DIRECTORY_SEPARATOR.$filename, $filename);
        }

        return $fileBag;
    }

    // endregion

    protected static function getArtifactsDir(): string
    {
        $artifactTestDir = static::$artifactTestDir ? DIRECTORY_SEPARATOR.static::$artifactTestDir : '';

        return self::$projectDir.self::ARTIFACT_DIR.$artifactTestDir;
    }

    /**
     * @return bool|string
     */
    protected static function getArtifactFileContent(string $filename): false|string
    {
        return file_get_contents(static::getArtifactsDir().DIRECTORY_SEPARATOR.$filename);
    }

    protected static function getRequiredFields(): array
    {
        return static::requiredFields;
    }

    protected static function getDomainUrl(): string
    {
        $scheme = static::getContainer()->getParameter('router.request_context.scheme');
        $host = static::getContainer()->getParameter('router.request_context.host');

        return "{$scheme}://{$host}";
    }

    protected static function getKernel(): KernelInterface
    {
        if (null == static::$kernel) {
            static::$kernel = static::createKernel();
        }

        return static::$kernel;
    }
}
