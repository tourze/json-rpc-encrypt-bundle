<?php

namespace Tourze\JsonRPCEncryptBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Tourze\AccessKeyBundle\Service\ApiCallerService;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppSecretMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptionFailedException;

final class Encryptor
{
    public const APPID_HEADER = 'Encrypt-AppID';

    public function __construct(private readonly ApiCallerService $apiCallerService)
    {
    }

    public function shouldEncrypt(Request $request): bool
    {
        return $request->headers->has(self::APPID_HEADER);
    }

    public function getRequestEncryptAppId(Request $request): string
    {
        $EncryptAppID = $request->headers->get(self::APPID_HEADER);
        if (null === $EncryptAppID || '' === $EncryptAppID) {
            throw new EncryptAppIdMissingException();
        }

        return $EncryptAppID;
    }

    public function encryptByRequest(Request $request, string $rawString): string
    {
        $appId = $this->getRequestEncryptAppId($request);
        $caller = $this->apiCallerService->findValidApiCallerByAppId($appId);
        if (null === $caller) {
            throw new EncryptAppIdNotFoundException();
        }

        $appSecret = $caller->getAppSecret();
        if (null === $appSecret || '' === $appSecret) {
            throw new EncryptAppSecretMissingException('AppSecret cannot be empty for encryption');
        }

        return $this->encryptData($rawString, $appSecret, $caller->getAppId());
    }

    public function decryptByRequest(Request $request, string $cipherText): string|false
    {
        $appId = $this->getRequestEncryptAppId($request);
        $caller = $this->apiCallerService->findValidApiCallerByAppId($appId);
        if (null === $caller) {
            throw new EncryptAppIdNotFoundException();
        }

        $appSecret = $caller->getAppSecret();
        if (null === $appSecret || '' === $appSecret) {
            throw new EncryptAppSecretMissingException('AppSecret cannot be empty for decryption');
        }

        return $this->decryptData($cipherText, $appSecret, $caller->getAppId());
    }

    public function encryptData(string $rawString, string $signSecret, string $signKey): string
    {
        $key = hash('sha256', $signSecret, true); // 生成 256 位的加密密钥
        $iv = md5($signKey, true); // 生成 16 字节的初始向量

        // 使用 PKCS7 填充方式进行加密
        $cipherText = openssl_encrypt($rawString, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if (false === $cipherText) {
            throw new EncryptionFailedException('Encryption failed');
        }
        $cipherText = base64_encode($cipherText); // 将密文转换为 base64 编码

        return trim($cipherText);
    }

    public function decryptData(string $cipherText, string $signSecret, string $signKey): string|false
    {
        $key = hash('sha256', $signSecret, true); // 生成 256 位的加密密钥
        $iv = md5($signKey, true); // 生成 16 字节的初始向量

        // 将密文从 base64 编码转换为二进制数据
        $cipherText = base64_decode($cipherText, true);
        if (false === $cipherText) {
            return false;
        }

        // 使用 PKCS7 填充方式进行解密
        return openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
