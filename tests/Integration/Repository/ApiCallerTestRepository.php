<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\JsonRPCEncryptBundle\Tests\Integration\Entity\TestApiCaller;

/**
 * @method TestApiCaller|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestApiCaller|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestApiCaller[]    findAll()
 * @method TestApiCaller[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiCallerTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestApiCaller::class);
    }
}
