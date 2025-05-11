<?php

namespace Tourze\JsonRPCEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class JsonRPCEncryptBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\JsonRPCCallerBundle\JsonRPCCallerBundle::class => ['all' => true],
        ];
    }
}
