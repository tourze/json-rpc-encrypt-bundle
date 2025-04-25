<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCEncryptBundle\DependencyInjection\JsonRPCEncryptExtension;
use Tourze\JsonRPCEncryptBundle\EventSubscriber\EncryptSubscriber;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class JsonRPCEncryptExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new JsonRPCEncryptExtension();
        $container = new ContainerBuilder();

        $extension->load([], $container);

        // 验证服务定义是否正确加载
        $this->assertTrue($container->hasDefinition(EncryptSubscriber::class) || $container->hasAlias(EncryptSubscriber::class));
        $this->assertTrue($container->hasDefinition(Encryptor::class) || $container->hasAlias(Encryptor::class));
    }
}
