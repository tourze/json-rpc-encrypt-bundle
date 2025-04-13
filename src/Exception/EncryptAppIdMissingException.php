<?php

namespace Tourze\JsonRPCEncryptBundle\Exception;

use Tourze\JsonRPC\Core\Exception\JsonRpcException;

class EncryptAppIdMissingException extends JsonRpcException
{
    public function __construct(string $message = '缺少必要的加密AppID', array $data = [], ?\Throwable $previous = null)
    {
        parent::__construct(-32600, $message, $data, $previous);
    }
}
