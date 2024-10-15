<?php

namespace EasyApiTests\crud\functions;

use EasyApiCore\Util\Forms\FormSerializer;
use Symfony\Component\HttpFoundation\Response;

trait crudFunctionsTestTrait
{
    protected static string $getActionType = 'Get';
    protected static string $getListActionType = 'GetList';
    protected static string $createActionType = 'Create';
    protected static string $cloneActionType = 'Clone';
    protected static string $updateActionType = 'Update';
    protected static string $downloadActionType = 'Download';

    protected function getCurrentDir(): array|bool|string|null
    {
        try {
            $rc = new \ReflectionClass($this);
            return str_replace("/{$rc->getShortName()}.php", '', $rc->getFilename());
        } catch (\Exception $e) {
            echo $e->getMessage();
            return null;
        }
    }

    protected function getExpectedResponse(string $filename, string $type, array $result, bool $dateProtection = false): array
    {
        $dir = "{$this->getCurrentDir()}/Responses/{$type}";
        $filePath = "{$dir}/{$filename}";

        if(!file_exists($filePath)) {
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // created_at / updated_at fields
            if($dateProtection) {
                if(self::$createActionType === $type || self::$updateActionType === $type || self::$cloneActionType === $type) {
                    if(array_key_exists('createdAt', $result)) {
                        $result['createdAt'] = '\assertDateTime()';
                    }
                    if(array_key_exists('updatedAt', $result)) {
                        $result['updatedAt'] = '\assertDateTime()';
                    }
                }
            }

            file_put_contents($filePath, self::generateJson($result));
        }

        return json_decode(file_get_contents($filePath), true);
    }

    protected function getExpectedFileResponse(string $filename, string $result): bool|string
    {
        $dir = "{$this->getCurrentDir()}/Responses/".self::$downloadActionType;
        $filePath = "{$dir}/{$filename}";

        if(!file_exists($filePath)) {

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($filePath, $result);
        }

        return file_get_contents($filePath);
    }

    /**
     * Retrieve data from file $filename or create it.
     * @throws \Exception
     */
    protected function getDataSent(string $filename, string $type, array $defaultContent = null): array
    {
        $dir = "{$this->getCurrentDir()}/DataSent/$type";
        $filePath = "$dir/$filename";

        if(!file_exists($filePath)) {
            if(!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            if(null === $defaultContent) {
                $defaultContent = static::generateDataSentDefault($type);
            }

            file_put_contents($filePath, self::generateJson($defaultContent));
        }

        if($json = json_decode(file_get_contents($filePath), true)) {
            return $json;
        }

        throw new \Exception("Invalid json in file $filename");
    }

    protected static function generateJson($content): array|bool|string
    {
        $json = json_encode($content, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        return str_replace('{}', '[]', $json);
    }

    protected static function generateDataSentDefault(string $type): array
    {
        $router = static::$container->get('router');

        if($type === self::$createActionType) {
            $route = $router->getRouteCollection()->get(self::getCreateRouteName());
            $controllerAction = $route->getDefault('_controller');
            $controllerClassName = explode('::', $controllerAction)[0];
            $formClass = constant("{$controllerClassName}::entityCreateTypeClass");
        } else {
            $route = $router->getRouteCollection()->get(self::getUpdateRouteName());
            $controllerAction = $route->getDefault('_controller');
            $controllerClassName = explode('::', $controllerAction)[0];
            $formClass = constant("{$controllerClassName}::entityUpdateTypeClass");
        }

        $describer = new FormSerializer(
            static::$container->get('form.factory'),
            static::$container->get('router'),
            static::$container->get('doctrine')
        );

        $normalizedForm = $describer->normalize(static::$container->get('form.factory')->create($formClass));

        $fields = [];
        foreach ($normalizedForm->getFields() as $field) {

            $defaultValue = '';
            switch ($field->getType()) {
                case 'string':
                    if('date' === $field->getFormat()) {
                        $defaultValue = (new \DateTime())->format('Y-m-d');
                    } elseif('date-time' === $field->getFormat()) {
                        $defaultValue = (new \DateTime())->format('Y-m-d h:i:s');
                    } else {
                        $defaultValue = 'string';
                    }
                    break;
                case 'text':
                    $defaultValue = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt';
                    break;
                case 'integer':
                    $defaultValue = random_int(0, 5000);
                    break;
                case 'number':
                    $defaultValue = random_int(1, 50000) / 10;
                    break;
                case 'array':
                    $defaultValue = [];
                    break;
                case 'boolean':
                case 'entity':
                    $nbValues = count($field->getValues());
                    if ('array' === $field->getFormat()) {
                        if ($nbValues >= 2 && isset($field->getValues()[0]['id']) && isset($field->getValues()[1]['id'])) {
                            $defaultValue = [$field->getValues()[0]['id'], $field->getValues()[1]['id']];
                        } elseif ($nbValues == 1 && isset($field->getValues()[0]['id'])) {
                            $defaultValue = [$field->getValues()[0]['id']];
                        } else {
                            $defaultValue = [1];
                        }
                    } else {
                        if ($nbValues == 1) {
                            $defaultValue = [$field->getValues()[0]['id']];
                        } else {
                            $defaultValue = 1;
                        }
                    }
                    break;
            }
            $fields[$field->getName()] = $defaultValue;
        }

        return $fields;
    }

    protected static function getGetRouteName(): string
    {
        return static::baseRouteName.'_get';
    }

    protected static function generateGetRouteParameters(array $params = []): array
    {
        return ['name' => static::getGetRouteName(), 'params' => $params];
    }

    protected static function getGetListRouteName(): string
    {
        return static::baseRouteName.'_list';
    }

    protected static function generateGetListRouteParameters(array $params = []): array
    {
        return ['name' => static::getGetListRouteName(), 'params' => $params];
    }

    protected static function getCreateRouteName(): string
    {
        return static::baseRouteName.'_create';
    }

    protected static function getCloneRouteName(): string
    {
        return static::baseRouteName.'_clone';
    }

    protected static function getUpdateRouteName(): string
    {
        return static::baseRouteName.'_update';
    }

    protected static function getDeleteRouteName(): string
    {
        return static::baseRouteName.'_delete';
    }

    protected static function generateDeleteRouteParameters(array $params = []): array
    {
        return ['name' => static::getDeleteRouteName(), 'params' => $params];
    }

    protected static function getDownloadRouteName(): string
    {
        return static::baseRouteName.'_download';
    }

    protected static function getDescribeFormRouteName(): string
    {
        return static::baseRouteName.'_describe_form';
    }

    protected static function getDataClassShortName(): string
    {
        return lcfirst(substr(static::entityClass, strrpos(static::entityClass, '\\') + 1));
    }

    protected function doTestGetAfterSave(string $id, string $filename, string $userLogin = null): void
    {
        $apiOutput = self::httpGetWithLogin(['name' => static::getGetRouteName(), 'params' => [static::identifier => $id]], $userLogin);
        static::assertEquals(Response::HTTP_OK, $apiOutput->getStatusCode());
        $result = $apiOutput->getData();
        $expectedResult = $this->getExpectedResponse($filename, static::$createActionType, $result, true);
        static::assertAssessableContent($expectedResult, $result);
        static::assertEquals($expectedResult, $result, "Get after saving failed for file {$filename}");
    }
}
