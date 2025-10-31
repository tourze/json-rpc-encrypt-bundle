<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EncryptAppIdMissingException::class)]
final class EncryptAppIdMissingExceptionTest extends AbstractExceptionTestCase
{
    public function testConstruct(): void
    {
        $exception = new EncryptAppIdMissingException();

        self::assertSame(-32600, $exception->getCode());
        self::assertSame('缺少必要的加密AppID', $exception->getMessage());
        self::assertSame([], $exception->getErrorData());
    }

    public function testConstructWithCustomMessage(): void
    {
        $customMessage = 'Custom error message';
        $exception = new EncryptAppIdMissingException($customMessage);

        self::assertSame(-32600, $exception->getCode());
        self::assertSame($customMessage, $exception->getMessage());
        self::assertSame([], $exception->getErrorData());
    }

    public function testConstructWithData(): void
    {
        $message = 'Error with data';
        $data = ['field' => 'value'];
        $exception = new EncryptAppIdMissingException($message, $data);

        self::assertSame(-32600, $exception->getCode());
        self::assertSame($message, $exception->getMessage());
        self::assertSame($data, $exception->getErrorData());
    }

    public function testConstructWithPrevious(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new EncryptAppIdMissingException('Current error', [], $previousException);

        self::assertSame(-32600, $exception->getCode());
        self::assertSame('Current error', $exception->getMessage());
        self::assertSame($previousException, $exception->getPrevious());
    }
}
