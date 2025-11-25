<?php

namespace EasyApiTests\Core;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait TestUtilsTrait
{
    /**
     * @return ContainerInterface
     */
    abstract protected static function getContainer();

    /**
     * @throws \Exception
     *
     * @return object
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
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @throws \Exception
     *
     * @return ObjectManager|object
     */
    protected static function getEntityManager(?string $name = null)
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
    protected static function getCache(): CacheItemPoolInterface
    {
        return static::getContainer()->get('cache.app');
    }
}
