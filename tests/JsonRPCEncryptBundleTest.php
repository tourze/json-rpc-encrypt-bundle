<?php

namespace Tourze\JsonRPCEncryptBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle;

class JsonRPCEncryptBundleTest extends TestCase
{
    public function testBundleInstance(): void
    {
        $bundle = new JsonRPCEncryptBundle();

        $this->assertInstanceOf(Bundle::class, $bundle);
    }
}
