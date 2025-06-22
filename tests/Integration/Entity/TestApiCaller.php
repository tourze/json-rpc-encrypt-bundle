<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_caller', options: ['comment' => '测试API调用者'])]
class TestApiCaller implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, options: ['comment' => '主键ID'])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 60, options: ['comment' => '标题'])]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '应用ID'])]
    private string $appId;

    #[ORM\Column(type: Types::STRING, length: 120, options: ['comment' => '应用密钥'])]
    private string $appSecret;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效'])]
    private bool $valid = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): self
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;
        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title . ' (' . $this->appId . ')';
    }
} 