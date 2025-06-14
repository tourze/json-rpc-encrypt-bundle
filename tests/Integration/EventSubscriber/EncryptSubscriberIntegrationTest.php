<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\Integration\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
 * EncryptSubscriber集成测试
 */
class EncryptSubscriberIntegrationTest extends KernelTestCase
{
    private MockApiCallerRepository $mockRepository;
    private Encryptor $encryptor;
    private RequestStack $requestStack;
    private LoggerInterface $logger;
    private EncryptSubscriber $subscriber;
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
            'Tourze\JsonRPCEncryptBundle\Tests\Integration\Entity' => dirname(__DIR__) . '/Entity',
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
        
        // 创建模拟仓库
        $this->mockRepository = new MockApiCallerRepository();
        $this->mockRepository->addMockedApiCaller('test-app', 'test-secret', true);
        
        // 获取服务
        $container = static::getContainer();
        $this->logger = $container->get(LoggerInterface::class);
        
        // 创建请求栈和请求对象
        $this->requestStack = new RequestStack();
        $this->request = new Request();
        $this->request->headers = new HeaderBag();
        $this->requestStack->push($this->request);
        
        // 创建Encryptor服务
        $this->encryptor = new Encryptor($this->mockRepository);
        
        // 创建EncryptSubscriber
        $this->subscriber = new EncryptSubscriber(
            $this->encryptor,
            $this->requestStack,
            $this->logger
        );
    }

    /**
     * 测试正常解密请求场景
     */
    public function testRequestDecryption(): void
    {
        // 设置加密标识
        $this->request->headers->set(Encryptor::APPID_HEADER, 'test-app');
        
        // 创建测试数据
        $originalData = '{"jsonrpc":"2.0","method":"test.method","params":{},"id":1}';
        $encryptedData = $this->encryptor->encryptByRequest($this->request, $originalData);
        
        // 创建RequestStartEvent
        $event = new RequestStartEvent();
        $event->setPayload($encryptedData);
        
        // 执行事件处理
        $this->subscriber->onRequestStart($event);
        
        // 验证解密结果
        $this->assertEquals($originalData, $event->getPayload());
        $this->assertJson($event->getPayload());
    }

    /**
     * 测试响应加密
     */
    public function testResponseEncryption(): void
    {
        // 设置加密标识
        $this->request->headers->set(Encryptor::APPID_HEADER, 'test-app');
        
        // 创建测试响应数据
        $originalResponse = '{"jsonrpc":"2.0","result":{"status":"success"},"id":1}';
        
        // 创建ResponseSendingEvent
        $event = new ResponseSendingEvent();
        $event->setResponseString($originalResponse);
        
        // 执行事件处理
        $this->subscriber->onResponseSending($event);
        
        // 验证加密后的结果
        $encryptedResponse = $event->getResponseString();
        $this->assertNotEquals($originalResponse, $encryptedResponse);
        
        // 解密验证
        $decryptedResponse = $this->encryptor->decryptByRequest($this->request, $encryptedResponse);
        $this->assertEquals($originalResponse, $decryptedResponse);
    }

    /**
     * 测试解密失败的情况
     */
    public function testDecryptionFailure(): void
    {
        // 设置加密标识
        $this->request->headers->set(Encryptor::APPID_HEADER, 'test-app');
        
        // 设置错误的加密数据
        $invalidEncryptedData = 'invalid-encrypted-data';
        
        // 创建RequestStartEvent
        $event = new RequestStartEvent();
        $event->setPayload($invalidEncryptedData);
        
        // 期望抛出异常
        $this->expectException(NotFoundHttpException::class);
        $this->subscriber->onRequestStart($event);
    }

    /**
     * 测试无需加密解密的情况
     */
    public function testNoEncryption(): void
    {
        // 不设置加密标识
        $this->request->headers->remove(Encryptor::APPID_HEADER);
        
        // 创建测试数据
        $originalData = '{"jsonrpc":"2.0","method":"test.method","params":{},"id":1}';
        
        // 请求事件
        $requestEvent = new RequestStartEvent();
        $requestEvent->setPayload($originalData);
        $this->subscriber->onRequestStart($requestEvent);
        
        // 验证原始数据未改变
        $this->assertEquals($originalData, $requestEvent->getPayload());
        
        // 响应事件
        $responseEvent = new ResponseSendingEvent();
        $responseEvent->setResponseString($originalData);
        $this->subscriber->onResponseSending($responseEvent);
        
        // 验证原始数据未改变
        $this->assertEquals($originalData, $responseEvent->getResponseString());
    }

    /**
     * 测试已解密JSON数据的处理
     */
    public function testPreDecryptedJson(): void
    {
        // 设置加密标识
        $this->request->headers->set(Encryptor::APPID_HEADER, 'test-app');
        
        // 创建已是JSON格式的数据
        $jsonData = '{"jsonrpc":"2.0","method":"test.method","params":{},"id":1}';
        
        // 创建RequestStartEvent
        $event = new RequestStartEvent();
        $event->setPayload($jsonData);
        
        // 执行事件处理
        $this->subscriber->onRequestStart($event);
        
        // 验证数据未改变(因为已经是JSON格式，认为已解密)
        $this->assertEquals($jsonData, $event->getPayload());
    }

    /**
     * 测试无效AppID的情况
     */
    public function testInvalidAppId(): void
    {
        // 设置无效AppID
        $this->request->headers->set(Encryptor::APPID_HEADER, 'invalid-app-id');
        
        // 创建加密数据
        $originalData = '{"jsonrpc":"2.0","method":"test.method","params":{},"id":1}';
        
        // 手动加密数据 (使用错误appId，但我们仍然用test-secret来加密数据)
        $encryptedData = $this->encryptor->encryptData(
            $originalData, 
            'test-secret',
            'test-app'
        );
        
        // 创建RequestStartEvent
        $event = new RequestStartEvent();
        $event->setPayload($encryptedData);
        
        // 期望抛出异常
        $this->expectException(NotFoundHttpException::class);
        $this->subscriber->onRequestStart($event);
    }
} 