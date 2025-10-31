<?php

declare(strict_types=1);

namespace Tourze\JsonRPCEncryptBundle\Exception;

use Tourze\JsonRPC\Core\Exception\JsonRpcExceptionInterface;

class EncryptAppIdNotFoundException extends \Exception implements JsonRpcExceptionInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $message = '找不到加密的AppID', private array $data = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, -32600, $previous);
    }

    public function getErrorCode(): int
    {
        return $this->getCode();
    }

    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->data;
    }
}
