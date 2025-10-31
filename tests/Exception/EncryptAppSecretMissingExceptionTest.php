<?php

declare(strict_types=1);

namespace Tourze\JsonRPCEncryptBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppSecretMissingException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EncryptAppSecretMissingException::class)]
class EncryptAppSecretMissingExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return EncryptAppSecretMissingException::class;
    }
}
