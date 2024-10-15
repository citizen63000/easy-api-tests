<?php

namespace EasyApiTests;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait TestUtilsTrait
{
    /**
     * @return ContainerInterface
     */
    abstract protected static function getContainer();

    /**
     * @return object
     *
     * @throws \Exception
     */
    protected static function get(string $id, int $invalidBehavior = Container::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return static::getContainer()->get($id, $invalidBehavior);
    }

    /**
     * @return ManagerRegistry|null
     */
    protected static function getDoctrine()
    {
        try {
            return static::getContainer()->get('doctrine');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return ObjectManager|object
     *
     * @throws \Exception
     */
    protected static function getEntityManager(string $name = null)
    {
        return self::getDoctrine()->getManager($name);
    }

    /**
     * @return ObjectRepository
     */
    protected static function getRepository(string $repository)
    {
        return self::getEntityManager()->getRepository($repository);
    }

    /**
     * @param $entity
     *
     * @return mixed
     */
    protected static function persistAndFlush($entity)
    {
        $em = self::getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * @throws \Exception
     */
    protected static function getCache(): AdapterInterface
    {
        return static::getContainer()->get('cache.app');
    }
}
