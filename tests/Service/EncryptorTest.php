<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AccessKeyBundle\Entity\AccessKey;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdMissingException;
use Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(Encryptor::class)]
#[RunTestsInSeparateProcesses]
final class EncryptorTest extends AbstractIntegrationTestCase
{
    private Encryptor $encryptor;

    protected function onSetUp(): void
    {
        $this->encryptor = self::getService(Encryptor::class);
    }

    public function testShouldEncrypt(): void
    {
        /*
         * 使用真实的 Request 对象而不是 Mock 的原因：
         * 1. Request 对象的 headers 属性有特定的类型要求（HeaderBag），直接赋值 Mock 会导致类型不匹配
         * 2. Symfony Request 对象设计为使用真实实例，其内部组件（如 HeaderBag）相互依赖
         * 3. 测试加密功能时，重点是验证对 HTTP 头存在性的判断，而不是 Request 对象本身的实现
         * 4. 使用真实 Request 对象更符合实际使用场景，测试更接近生产环境
         */

        // 测试有加密头的情况 - 使用真实 Request 对象并设置实际的 header
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, 'test-app-id');
        $this->assertTrue($this->encryptor->shouldEncrypt($request));

        // 测试无加密头的情况 - 使用不包含加密头的真实 Request 对象
        $request = new Request();
        $this->assertFalse($this->encryptor->shouldEncrypt($request));
    }

    public function testGetRequestEncryptAppId(): void
    {
        $appId = 'test-app-id';

        // 测试正常获取AppID - 使用真实 Request 对象并设置实际的 header
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);
        $this->assertEquals($appId, $this->encryptor->getRequestEncryptAppId($request));

        // 测试空AppID抛出异常 - 使用真实 Request 对象设置空值
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, '');

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

        // 创建真实的 ApiCaller 实体并保存到数据库
        $apiCaller = new AccessKey();
        $apiCaller->setTitle('Test API Caller');
        $apiCaller->setAppId($appId);
        $apiCaller->setAppSecret($appSecret);
        $apiCaller->setValid(true);

        $entityManager = self::getEntityManager();
        $entityManager->persist($apiCaller);
        $entityManager->flush();

        // 使用真实 Request 对象
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);

        $encryptedData = $this->encryptor->encryptByRequest($request, $rawString);
        $this->assertNotEmpty($encryptedData);
    }

    public function testEncryptByRequestWithInvalidAppId(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appId = 'invalid-app-id';

        // 使用真实 Request 对象
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);

        $this->expectException(EncryptAppIdNotFoundException::class);
        $this->encryptor->encryptByRequest($request, $rawString);
    }

    public function testDecryptByRequest(): void
    {
        $rawString = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $appId = 'test-app-id';
        $appSecret = 'test-secret';

        // 创建真实的 ApiCaller 实体并保存到数据库
        $apiCaller = new AccessKey();
        $apiCaller->setTitle('Test API Caller');
        $apiCaller->setAppId($appId);
        $apiCaller->setAppSecret($appSecret);
        $apiCaller->setValid(true);

        $entityManager = self::getEntityManager();
        $entityManager->persist($apiCaller);
        $entityManager->flush();

        // 使用真实 Request 对象
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);

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

        // 使用真实 Request 对象
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);

        $this->expectException(EncryptAppIdNotFoundException::class);
        $this->encryptor->decryptByRequest($request, $encryptedData);
    }

    public function testEncryptData(): void
    {
        $rawString = 'Hello, World!';
        $signSecret = 'test-secret';
        $signKey = 'test-key';

        $encryptedData = $this->encryptor->encryptData($rawString, $signSecret, $signKey);

        $this->assertIsString($encryptedData);
        $this->assertNotEmpty($encryptedData);
        $this->assertNotEquals($rawString, $encryptedData);

        // 验证可以正确解密
        $decryptedData = $this->encryptor->decryptData($encryptedData, $signSecret, $signKey);
        $this->assertEquals($rawString, $decryptedData);
    }

    public function testDecryptData(): void
    {
        $rawString = 'Test decryption data';
        $signSecret = 'test-secret-for-decrypt';
        $signKey = 'test-key-for-decrypt';

        // 先加密数据
        $encryptedData = $this->encryptor->encryptData($rawString, $signSecret, $signKey);

        // 测试解密功能
        $decryptedData = $this->encryptor->decryptData($encryptedData, $signSecret, $signKey);

        $this->assertEquals($rawString, $decryptedData);
    }

    public function testDecryptDataWithInvalidData(): void
    {
        $invalidCipherText = 'invalid-cipher-text';
        $signSecret = 'test-secret';
        $signKey = 'test-key';

        $result = $this->encryptor->decryptData($invalidCipherText, $signSecret, $signKey);

        $this->assertFalse($result);
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $originalData = '{"jsonrpc":"2.0","method":"test","params":{"data":"sensitive"},"id":1}';
        $signSecret = 'super-secret-key';
        $signKey = 'unique-app-id';

        // 加密
        $encrypted = $this->encryptor->encryptData($originalData, $signSecret, $signKey);
        $this->assertNotEquals($originalData, $encrypted);

        // 解密
        $decrypted = $this->encryptor->decryptData($encrypted, $signSecret, $signKey);
        $this->assertEquals($originalData, $decrypted);
    }
}
