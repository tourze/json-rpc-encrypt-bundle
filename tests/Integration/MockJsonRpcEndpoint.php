<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration;

use Symfony\Component\HttpFoundation\Request;
use Tourze\JsonRPC\Core\Contracts\EndpointInterface;

/**
 * Mock的JsonRpc端点实现，用于测试
 */
class MockJsonRpcEndpoint implements EndpointInterface
{
    public function index(string $payload, ?Request $request = null): string
    {
        // 简单的回显实现
        return json_encode([
            'jsonrpc' => '2.0',
            'result' => json_decode($payload, true),
            'id' => rand(1000, 9999)
        ]);
    }

    public function reset(): void
    {
        // 无需实现
    }
} 