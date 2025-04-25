<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Tourze\JsonRPCCallerBundle\Entity\ApiCaller;
use Tourze\JsonRPCCallerBundle\Repository\ApiCallerRepository;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class EncryptorTest extends TestCase
{
    private Encryptor $encryptor;
    private ApiCallerRepository|MockObject $apiCallerRepository;

    protected function setUp(): void
    {
        $this->apiCallerRepository = $this->createMock(ApiCallerRepository::class);
        $this->encryptor = new Encryptor($this->apiCallerRepository);
    }

    public function testShouldEncrypt(): void
    {
        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);

        // 测试有加密头的情况
        $request->headers->method('has')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn(true);
        $this->assertTrue($this->encryptor->shouldEncrypt($request));

        // 测试无加密头的情况
        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('has')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn(false);
        $this->assertFalse($this->encryptor->shouldEncrypt($request));
    }

    public function testGetRequestEncryptAppId(): void
    {
        $appId = 'test-app-id';
        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);

        // 测试正常获取AppID
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn($appId);
        $this->assertEquals($appId, $this->encryptor->getRequestEncryptAppId($request));

        // 测试空AppID抛出异常
        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn('');

        $this->expectException(EncryptAppIdMissingException::class);
        $this->encryptor->getRequestEncryptAppId($request);
    }

    public function testEncryptAndDecryptData(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appSecret = 'test-secret';
        $appId = 'test-app-id';

        // 测试加密和解密完整流程
        $encryptedData = $this->encryptor->encryptData($rawString, $appSecret, $appId);
        $this->assertNotEmpty($encryptedData);

        $decryptedData = $this->encryptor->decryptData($encryptedData, $appSecret, $appId);
        $this->assertEquals($rawString, $decryptedData);

        // 测试密钥不匹配的情况
        $wrongDecrypted = $this->encryptor->decryptData($encryptedData, 'wrong-secret', $appId);
        $this->assertNotEquals($rawString, $wrongDecrypted);
    }

    public function testEncryptByRequest(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appId = 'test-app-id';
        $appSecret = 'test-secret';

        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn($appId);

        $apiCaller = $this->createMock(ApiCaller::class);
        $apiCaller->method('getAppSecret')->willReturn($appSecret);
        $apiCaller->method('getAppId')->willReturn($appId);

        $this->apiCallerRepository->method('findOneBy')
            ->with([
                'appId' => $appId,
                'valid' => true,
            ])
            ->willReturn($apiCaller);

        $encryptedData = $this->encryptor->encryptByRequest($request, $rawString);
        $this->assertNotEmpty($encryptedData);
    }

    public function testEncryptByRequestWithInvalidAppId(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appId = 'invalid-app-id';

        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn($appId);

        $this->apiCallerRepository->method('findOneBy')
            ->with([
                'appId' => $appId,
                'valid' => true,
            ])
            ->willReturn(null);

        $this->expectException(EncryptAppIdNotFoundException::class);
        $this->encryptor->encryptByRequest($request, $rawString);
    }

    public function testDecryptByRequest(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appId = 'test-app-id';
        $appSecret = 'test-secret';

        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn($appId);

        $apiCaller = $this->createMock(ApiCaller::class);
        $apiCaller->method('getAppSecret')->willReturn($appSecret);
        $apiCaller->method('getAppId')->willReturn($appId);

        $this->apiCallerRepository->method('findOneBy')
            ->with([
                'appId' => $appId,
                'valid' => true,
            ])
            ->willReturn($apiCaller);

        // 先加密数据
        $encryptedData = $this->encryptor->encryptData($rawString, $appSecret, $appId);

        // 测试解密功能
        $decryptedData = $this->encryptor->decryptByRequest($request, $encryptedData);
        $this->assertEquals($rawString, $decryptedData);
    }

    public function testDecryptByRequestWithInvalidAppId(): void
    {
        $encryptedData = 'encrypted-data';
        $appId = 'invalid-app-id';

        $request = $this->createMock(Request::class);
        $request->headers = $this->createMock(HeaderBag::class);
        $request->headers->method('get')
            ->with(Encryptor::APPID_HEADER)
            ->willReturn($appId);

        $this->apiCallerRepository->method('findOneBy')
            ->with([
                'appId' => $appId,
                'valid' => true,
            ])
            ->willReturn(null);

        $this->expectException(EncryptAppIdNotFoundException::class);
        $this->encryptor->decryptByRequest($request, $encryptedData);
    }
}
