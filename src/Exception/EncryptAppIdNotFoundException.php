<?php

namespace Tourze\JsonRPCEncryptBundle\Exception;

use Tourze\JsonRPC\Core\Exception\JsonRpcException;

class EncryptAppIdNotFoundException extends JsonRpcException
{
    public function __construct(string $message = '找不到加密的AppID', array $data = [], ?\Throwable $previous = null)
    {
        parent::__construct(-32600, $message, $data, $previous);
    }
}
