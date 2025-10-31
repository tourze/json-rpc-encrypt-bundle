<?php

declare(strict_types=1);

namespace Tourze\JsonRPCEncryptBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptionFailedException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EncryptionFailedException::class)]
class EncryptionFailedExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return EncryptionFailedException::class;
    }
}
