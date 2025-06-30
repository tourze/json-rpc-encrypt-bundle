<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;

class EncryptAppIdNotFoundExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new EncryptAppIdNotFoundException();
        
        self::assertSame(-32600, $exception->getCode());
        self::assertSame('找不到加密的AppID', $exception->getMessage());
        self::assertSame([], $exception->getErrorData());
    }
    
    public function testConstructWithCustomMessage(): void
    {
        $customMessage = 'Custom not found message';
        $exception = new EncryptAppIdNotFoundException($customMessage);
        
        self::assertSame(-32600, $exception->getCode());
        self::assertSame($customMessage, $exception->getMessage());
        self::assertSame([], $exception->getErrorData());
    }
    
    public function testConstructWithData(): void
    {
        $message = 'AppID not found';
        $data = ['appId' => '12345'];
        $exception = new EncryptAppIdNotFoundException($message, $data);
        
        self::assertSame(-32600, $exception->getCode());
        self::assertSame($message, $exception->getMessage());
        self::assertSame($data, $exception->getErrorData());
    }
    
    public function testConstructWithPrevious(): void
    {
        $previousException = new \Exception('Database error');
        $exception = new EncryptAppIdNotFoundException('AppID lookup failed', [], $previousException);
        
        self::assertSame(-32600, $exception->getCode());
        self::assertSame('AppID lookup failed', $exception->getMessage());
        self::assertSame($previousException, $exception->getPrevious());
    }
}