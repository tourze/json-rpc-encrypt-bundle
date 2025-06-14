<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\JsonRPC\Core\Contracts\EndpointInterface;

/**
 * JsonRPC加密包测试专用内核
 */
class TestKernel extends IntegrationTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        
        $loader->load(function (ContainerBuilder $container) {
            // 注册 MockJsonRpcEndpoint 作为 EndpointInterface 的实现
            $container->register(MockJsonRpcEndpoint::class)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true);
                
            $container->setAlias(EndpointInterface::class, MockJsonRpcEndpoint::class)
                ->setPublic(true);
        });
    }
}