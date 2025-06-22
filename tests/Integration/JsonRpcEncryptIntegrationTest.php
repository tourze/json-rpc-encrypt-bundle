<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\JsonRPCCallerBundle\JsonRPCCallerBundle;
use Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;
use Tourze\JsonRPCEncryptBundle\Tests\Integration\Service\MockApiCallerRepository;
use Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle;

/**
 * JsonRPC加解密集成测试
 *
 * 验证JsonRPCEncryptBundle是否能正确处理加密请求与响应
 */
class JsonRpcEncryptIntegrationTest extends KernelTestCase
{
    private MockApiCallerRepository $apiCallerRepository;
    private Encryptor $encryptor;
    private string $testAppId = 'test-app-id';
    private string $testAppSecret = 'test-app-secret';

    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected static function createKernel(array $options = []): IntegrationTestKernel
    {
        $appendBundles = [
            FrameworkBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            JsonRPCCallerBundle::class => ['all' => true],
            JsonRPCEndpointBundle::class => ['all' => true],
            JsonRPCEncryptBundle::class => ['all' => true],
        ];
        
        $entityMappings = [
            'Tourze\JsonRPCEncryptBundle\Tests\Integration\Entity' => __DIR__ . '/Entity',
        ];

        return new IntegrationTestKernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? true,
            $appendBundles,
            $entityMappings
        );
    }

    protected function setUp(): void
    {
        // 启动Symfony测试内核
        self::bootKernel();

        // 创建模拟仓库
        $this->apiCallerRepository = new MockApiCallerRepository();
        $this->apiCallerRepository->addMockedApiCaller($this->testAppId, $this->testAppSecret, true);

        // 创建加密服务
        $this->encryptor = new Encryptor($this->apiCallerRepository);
    }

    /**
     * 测试加密请求处理流程
     */
    public function testEncryptedRequestProcessing(): void
    {
        // 创建测试JsonRPC请求
        $jsonRpcRequest = [
            'jsonrpc' => '2.0',
            'method' => 'test.method',
            'params' => ['param1' => 'value1'],
            'id' => 123,
        ];

        $plainRequest = json_encode($jsonRpcRequest);

        // 使用Encryptor加密请求
        $encryptedRequest = $this->encryptor->encryptData(
            $plainRequest,
            $this->testAppSecret,
            $this->testAppId
        );

        // 创建带加密头的请求
        $request = new Request();
        $request->headers = new HeaderBag();
        $request->headers->set(Encryptor::APPID_HEADER, $this->testAppId);

        // 测试解密流程
        $decryptedRequest = $this->encryptor->decryptByRequest($request, $encryptedRequest);

        // 验证解密结果
        $this->assertEquals($plainRequest, $decryptedRequest);
        $this->assertJson($decryptedRequest);

        $decoded = json_decode($decryptedRequest, true);
        $this->assertEquals($jsonRpcRequest['method'], $decoded['method']);
    }

    /**
     * 测试响应加密流程
     */
    public function testEncryptedResponseProcessing(): void
    {
        // 创建测试JsonRPC响应
        $jsonRpcResponse = [
            'jsonrpc' => '2.0',
            'result' => ['key' => 'value'],
            'id' => 123,
        ];

        $plainResponse = json_encode($jsonRpcResponse);

        // 创建带加密头的请求
        $request = new Request();
        $request->headers = new HeaderBag();
        $request->headers->set(Encryptor::APPID_HEADER, $this->testAppId);

        // 使用Encryptor加密响应
        $encryptedResponse = $this->encryptor->encryptByRequest($request, $plainResponse);

        // 测试解密流程
        $decryptedResponse = $this->encryptor->decryptData(
            $encryptedResponse,
            $this->testAppSecret,
            $this->testAppId
        );

        // 验证解密结果
        $this->assertEquals($plainResponse, $decryptedResponse);
        $this->assertJson($decryptedResponse);

        $decoded = json_decode($decryptedResponse, true);
        $this->assertEquals($jsonRpcResponse['result'], $decoded['result']);
    }

    /**
     * 测试完整的加解密流程
     */
    public function testFullEncryptionDecryptionCycle(): void
    {
        // 创建测试JsonRPC请求
        $jsonRpcRequest = [
            'jsonrpc' => '2.0',
            'method' => 'test.echo',
            'params' => ['message' => 'Hello, encrypted world!'],
            'id' => 123,
        ];

        $plainRequest = json_encode($jsonRpcRequest);

        // 使用Encryptor加密请求
        $encryptedRequest = $this->encryptor->encryptData(
            $plainRequest,
            $this->testAppSecret,
            $this->testAppId
        );

        // 创建带加密头的请求
        $request = new Request();
        $request->headers = new HeaderBag();
        $request->headers->set(Encryptor::APPID_HEADER, $this->testAppId);

        // 使用Encryptor再次加密响应
        $encryptedResponse = $this->encryptor->encryptByRequest($request, $plainRequest);

        // 完整解密过程
        $decryptedResponse = $this->encryptor->decryptData(
            $encryptedResponse,
            $this->testAppSecret,
            $this->testAppId
        );

        // 验证完整流程
        $this->assertEquals($plainRequest, $decryptedResponse);
    }

    /**
     * 测试无效AppID场景
     */
    public function testInvalidAppId(): void
    {
        $jsonRpcRequest = [
            'jsonrpc' => '2.0',
            'method' => 'test.method',
            'params' => [],
            'id' => 123,
        ];

        $plainRequest = json_encode($jsonRpcRequest);

        // 创建带无效AppID的请求
        $request = new Request();
        $request->headers = new HeaderBag();
        $request->headers->set(Encryptor::APPID_HEADER, 'invalid-app-id');

        // 期望抛出异常
        $this->expectException(\Tourze\JsonRPCEncryptBundle\Exception\EncryptAppIdNotFoundException::class);
        $this->encryptor->encryptByRequest($request, $plainRequest);
    }

    /**
     * 测试错误的加密密钥
     */
    public function testWrongSecret(): void
    {
        $jsonRpcRequest = [
            'jsonrpc' => '2.0',
            'method' => 'test.method',
            'params' => [],
            'id' => 123,
        ];

        $plainRequest = json_encode($jsonRpcRequest);

        // 使用正确密钥加密
        $encryptedRequest = $this->encryptor->encryptData(
            $plainRequest,
            $this->testAppSecret,
            $this->testAppId
        );

        // 使用错误密钥解密
        $decryptedRequest = $this->encryptor->decryptData(
            $encryptedRequest,
            'wrong-secret',
            $this->testAppId
        );

        // 验证解密失败
        $this->assertNotEquals($plainRequest, $decryptedRequest);
    }
}
