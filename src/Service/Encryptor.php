<?php

namespace Tourze\JsonRPCEncryptBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Tourze\JsonRPCCallerBundle\Repository\ApiCallerRepository;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;

class Encryptor
{
    public const APPID_HEADER = 'Encrypt-AppID';

    public function __construct(private readonly ApiCallerRepository $apiCallerRepository)
    {
    }

    public function shouldEncrypt(Request $request): bool
    {
        return $request->headers->has(self::APPID_HEADER);
    }

    public function getRequestEncryptAppId(Request $request): string
    {
        $EncryptAppID = $request->headers->get(self::APPID_HEADER);
        if (empty($EncryptAppID)) {
            throw new EncryptAppIdMissingException();
        }

        return $EncryptAppID;
    }

    public function encryptByRequest(Request $request, string $rawString): string
    {
        $appId = $this->getRequestEncryptAppId($request);
        $caller = $this->apiCallerRepository->findOneBy([
            'appId' => $appId,
            'valid' => true,
        ]);
        if (!$caller) {
            throw new EncryptAppIdNotFoundException();
        }

        return $this->encryptData($rawString, $caller->getAppSecret(), $caller->getAppId());
    }

    public function decryptByRequest(Request $request, string $cipherText): string|false
    {
        $appId = $this->getRequestEncryptAppId($request);
        $caller = $this->apiCallerRepository->findOneBy([
            'appId' => $appId,
            'valid' => true,
        ]);
        if (!$caller) {
            throw new EncryptAppIdNotFoundException();
        }

        return $this->decryptData($cipherText, $caller->getAppSecret(), $caller->getAppId());
    }

    public function encryptData(string $rawString, string $signSecret, string $signKey): string
    {
        $key = hash('sha256', $signSecret, true); // 生成 256 位的加密密钥
        $iv = md5($signKey, true); // 生成 16 字节的初始向量

        // 使用 PKCS7 填充方式进行加密
        $cipherText = openssl_encrypt($rawString, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $cipherText = base64_encode($cipherText); // 将密文转换为 base64 编码

        return trim($cipherText);
    }

    public function decryptData(string $cipherText, string $signSecret, string $signKey): string|false
    {
        $key = hash('sha256', $signSecret, true); // 生成 256 位的加密密钥
        $iv = md5($signKey, true); // 生成 16 字节的初始向量

        // 将密文从 base64 编码转换为二进制数据
        $cipherText = base64_decode($cipherText);

        // 使用 PKCS7 填充方式进行解密
        return openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
