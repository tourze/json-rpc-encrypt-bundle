<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration\Service;

use Tourze\JsonRPCCallerBundle\Entity\ApiCaller;
use Tourze\JsonRPCCallerBundle\Repository\ApiCallerRepository;

/**
 * 用于测试的ApiCallerRepository模拟类
 */
class MockApiCallerRepository extends ApiCallerRepository
{
    private array $mockedApiCallers = [];

    /**
     * 重写构造函数，不需要原始依赖
     */
    public function __construct()
    {
        // 不调用父类构造函数
    }

    /**
     * 添加模拟的ApiCaller
     */
    public function addMockedApiCaller(string $appId, string $appSecret, bool $valid = true): void
    {
        $apiCaller = new ApiCaller();
        $apiCaller->setAppId($appId);
        $apiCaller->setAppSecret($appSecret);
        $apiCaller->setValid($valid);

        $this->mockedApiCallers[$appId] = $apiCaller;
    }

    /**
     * 重写findOneBy方法，返回模拟数据
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?ApiCaller
    {
        // 处理查找appId情况
        if (isset($criteria['appId'])) {
            $appId = $criteria['appId'];

            // 检查有效性条件
            if (isset($criteria['valid']) && isset($this->mockedApiCallers[$appId])) {
                $apiCaller = $this->mockedApiCallers[$appId];
                if ($apiCaller->isValid() === $criteria['valid']) {
                    return $apiCaller;
                }
                return null;
            }

            return $this->mockedApiCallers[$appId] ?? null;
        }

        // 其他情况返回null
        return null;
    }

    /**
     * 清除所有模拟数据
     */
    public function clearMockedData(): void
    {
        $this->mockedApiCallers = [];
    }
}
