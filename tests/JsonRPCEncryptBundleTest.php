<?php

declare(strict_types=1);

namespace Tourze\JsonRPCEncryptBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRPCEncryptBundle::class)]
#[RunTestsInSeparateProcesses]
final class JsonRPCEncryptBundleTest extends AbstractBundleTestCase
{
}
