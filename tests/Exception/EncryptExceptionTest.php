<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;

class EncryptExceptionTest extends TestCase
{
    public function testEncryptAppIdNotFoundException(): void
    {
        $exception = new EncryptAppIdNotFoundException();

        $this->assertEquals(-32600, $exception->getCode());
        $this->assertEquals('找不到加密的AppID', $exception->getMessage());

        // 测试自定义消息
        $message = '自定义错误消息';
        $data = ['key' => 'value'];
        $exception = new EncryptAppIdNotFoundException($message, $data);

        $this->assertEquals(-32600, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testEncryptAppIdMissingException(): void
    {
        $exception = new EncryptAppIdMissingException();

        $this->assertEquals(-32600, $exception->getCode());
        $this->assertEquals('缺少必要的加密AppID', $exception->getMessage());

        // 测试自定义消息
        $message = '自定义错误消息';
        $data = ['key' => 'value'];
        $exception = new EncryptAppIdMissingException($message, $data);

        $this->assertEquals(-32600, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new EncryptAppIdNotFoundException();
        $this->assertInstanceOf(\Tourze\JsonRPC\Core\Exception\JsonRpcException::class, $exception);

        $exception = new EncryptAppIdMissingException();
        $this->assertInstanceOf(\Tourze\JsonRPC\Core\Exception\JsonRpcException::class, $exception);
    }
}
