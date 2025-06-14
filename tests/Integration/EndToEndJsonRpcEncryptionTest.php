<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\JsonRPC\Core\Event\RequestStartEvent;
use Tourze\JsonRPC\Core\Event\ResponseSendingEvent;
use Tourze\JsonRPCCallerBundle\JsonRPCCallerBundle;
use Tourze\JsonRPCEncryptBundle\EventSubscriber\EncryptSubscriber;
use Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;
use Tourze\JsonRPCEncryptBundle\Tests\Integration\Service\MockApiCallerRepository;
use Tourze\JsonRPCEndpointBundle\JsonRPCEndpointBundle;

/**
 * JsonRPC加解密端到端集成测试
 *
 * 模拟完整的请求-处理-响应流程
 */
class EndToEndJsonRpcEncryptionTest extends KernelTestCase
{
    private MockApiCallerRepository $mockRepository;
    private Encryptor $encryptor;
    private EncryptSubscriber $subscriber;
    private RequestStack $requestStack;
    private Request $request;

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
        // 启动内核
        self::bootKernel();
        $container = static::getContainer();

        // 设置测试数据
        $this->mockRepository = new MockApiCallerRepository();
        $this->mockRepository->addMockedApiCaller('test-app-id', 'test-app-secret', true);

        // 创建请求
        $this->requestStack = new RequestStack();
        $this->request = new Request();
        $this->request->headers = new HeaderBag();
        $this->requestStack->push($this->request);

        // 设置服务
        $this->encryptor = new Encryptor($this->mockRepository);
        $this->subscriber = new EncryptSubscriber(
            $this->encryptor,
            $this->requestStack,
            $container->get('logger')
        );
    }

    /**
     * 测试完整的请求-响应加解密流程
     */
    public function testCompleteEncryptionFlow(): void
    {
        // ========== 客户端加密阶段 ==========

        // 1. 构建JSON-RPC请求数据
        $originalRequest = [
            'jsonrpc' => '2.0',
            'method' => 'echo.message',
            'params' => ['message' => '这是一条需要加密的测试消息'],
            'id' => 123456
        ];
        $jsonRequest = json_encode($originalRequest);

        // 2. 客户端使用密钥加密
        $encryptedRequest = $this->encryptor->encryptData(
            $jsonRequest,
            'test-app-secret',
            'test-app-id'
        );

        // 3. 设置请求头并发送请求
        $this->request->headers->set(Encryptor::APPID_HEADER, 'test-app-id');

        // ========== 服务端解密阶段 ==========

        // 4. 服务端接收加密请求
        $requestEvent = new RequestStartEvent();
        $requestEvent->setPayload($encryptedRequest);

        // 5. 触发请求解密事件
        $this->subscriber->onRequestStart($requestEvent);

        // 6. 验证解密结果
        $decryptedRequest = $requestEvent->getPayload();
        $this->assertJson($decryptedRequest);
        $decodedRequest = json_decode($decryptedRequest, true);
        $this->assertEquals($originalRequest['method'], $decodedRequest['method']);
        $this->assertEquals($originalRequest['params'], $decodedRequest['params']);

        // ========== 业务处理阶段(模拟) ==========

        // 7. 模拟业务处理，生成响应
        $responseData = [
            'jsonrpc' => '2.0',
            'result' => [
                'echo' => $decodedRequest['params']['message'],
                'timestamp' => time()
            ],
            'id' => $decodedRequest['id']
        ];
        $jsonResponse = json_encode($responseData);

        // ========== 服务端加密阶段 ==========

        // 8. 创建响应事件
        $responseEvent = new ResponseSendingEvent();
        $responseEvent->setResponseString($jsonResponse);

        // 9. 触发响应加密事件
        $this->subscriber->onResponseSending($responseEvent);

        // 10. 获取加密后的响应
        $encryptedResponse = $responseEvent->getResponseString();
        $this->assertNotEquals($jsonResponse, $encryptedResponse); // 确认已加密

        // ========== 客户端解密阶段 ==========

        // 11. 客户端解密响应
        $decryptedResponse = $this->encryptor->decryptData(
            $encryptedResponse,
            'test-app-secret',
            'test-app-id'
        );

        // 12. 验证解密结果
        $this->assertJson($decryptedResponse);
        $decodedResponse = json_decode($decryptedResponse, true);
        $this->assertEquals($responseData['result']['echo'], $decodedResponse['result']['echo']);
        $this->assertEquals($responseData['id'], $decodedResponse['id']);
    }

    /**
     * 测试使用错误密钥解密的情况
     */
    public function testDecryptionWithWrongKey(): void
    {
        // 1. 构建JSON-RPC请求数据
        $originalRequest = [
            'jsonrpc' => '2.0',
            'method' => 'echo.message',
            'params' => ['message' => '这是一条测试消息'],
            'id' => 123456
        ];
        $jsonRequest = json_encode($originalRequest);

        // 2. 客户端使用密钥加密
        $encryptedRequest = $this->encryptor->encryptData(
            $jsonRequest,
            'test-app-secret',
            'test-app-id'
        );

        // 3. 使用错误的密钥解密
        $badDecryption = $this->encryptor->decryptData(
            $encryptedRequest,
            'wrong-secret',
            'test-app-id'
        );

        // 4. 验证解密失败
        $this->assertNotEquals($jsonRequest, $badDecryption);
        $this->assertFalse(json_validate((string)$badDecryption));
    }

    /**
     * 测试不同AppID和密钥的隔离性
     */
    public function testMultipleAppIdIsolation(): void
    {
        // 添加多个AppID
        $this->mockRepository->addMockedApiCaller('app-1', 'secret-1', true);
        $this->mockRepository->addMockedApiCaller('app-2', 'secret-2', true);

        // 原始数据
        $originalData = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        // 使用app-1加密
        $encrypted1 = $this->encryptor->encryptData($originalData, 'secret-1', 'app-1');

        // 使用app-2加密
        $encrypted2 = $this->encryptor->encryptData($originalData, 'secret-2', 'app-2');

        // 验证两次加密结果不同
        $this->assertNotEquals($encrypted1, $encrypted2);

        // 验证使用正确的密钥解密
        $decrypted1 = $this->encryptor->decryptData($encrypted1, 'secret-1', 'app-1');
        $this->assertEquals($originalData, $decrypted1);

        $decrypted2 = $this->encryptor->decryptData($encrypted2, 'secret-2', 'app-2');
        $this->assertEquals($originalData, $decrypted2);

        // 验证错误的密钥不能解密
        $wrongDecrypted = $this->encryptor->decryptData($encrypted1, 'secret-2', 'app-1');
        $this->assertNotEquals($originalData, $wrongDecrypted);
    }
}
