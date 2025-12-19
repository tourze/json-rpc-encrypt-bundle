<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\AccessKeyBundle\Entity\AccessKey;
use Tourze\JsonRPC\Core\Event\RequestStartEvent;
use Tourze\JsonRPC\Core\Event\ResponseSendingEvent;
use Tourze\JsonRPCEncryptBundle\EventSubscriber\EncryptEventSubscriber;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(EncryptEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class EncryptEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    private EncryptEventSubscriber $subscriber;

    private Encryptor $encryptor;

    protected function onSetUp(): void
    {
        $this->subscriber = self::getService(EncryptEventSubscriber::class);
        $this->encryptor = self::getService(Encryptor::class);
    }

    private function createAccessKey(string $appId, string $appSecret): AccessKey
    {
        $accessKey = new AccessKey();
        $accessKey->setTitle('Test API Caller');
        $accessKey->setAppId($appId);
        $accessKey->setAppSecret($appSecret);
        $accessKey->setValid(true);

        $entityManager = self::getEntityManager();
        $entityManager->persist($accessKey);
        $entityManager->flush();

        return $accessKey;
    }

    public function testGetSubscribedEvents(): void
    {
        $events = EncryptEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(RequestStartEvent::class, $events);
        $this->assertArrayHasKey(ResponseSendingEvent::class, $events);

        $this->assertEquals(
            [['onRequestStart', 9999]],
            $events[RequestStartEvent::class]
        );

        $this->assertEquals(
            [['onResponseSending', -9999]],
            $events[ResponseSendingEvent::class]
        );
    }

    public function testOnRequestStartWithNoRequest(): void
    {
        $event = new RequestStartEvent(null, '');

        $this->subscriber->onRequestStart($event);

        $this->assertSame('', $event->getPayload());
    }

    public function testOnRequestStartWithNoEncryption(): void
    {
        $request = new Request();
        $event = new RequestStartEvent($request, '');

        $this->subscriber->onRequestStart($event);

        $this->assertSame('', $event->getPayload());
    }

    public function testOnRequestStartWithAlreadyDecodedJson(): void
    {
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, 'test-app');

        $jsonPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        $event = new RequestStartEvent($request, $jsonPayload);

        $this->subscriber->onRequestStart($event);

        $this->assertSame($jsonPayload, $event->getPayload());
    }

    public function testOnRequestStartWithEncryptedRequest(): void
    {
        $appId = 'test-app-encrypt';
        $appSecret = 'test-secret';
        $this->createAccessKey($appId, $appSecret);

        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);
        $decryptedPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';
        $encryptedPayload = $this->encryptor->encryptByRequest($request, $decryptedPayload);

        $event = new RequestStartEvent($request, $encryptedPayload);

        $this->subscriber->onRequestStart($event);

        $this->assertSame($decryptedPayload, $event->getPayload());
    }

    public function testOnRequestStartWithDecryptionFailure(): void
    {
        $appId = 'test-app-failure';
        $appSecret = 'test-secret';
        $this->createAccessKey($appId, $appSecret);

        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);
        $encryptedPayload = 'corrupted-encrypted-payload';

        $event = new RequestStartEvent($request, $encryptedPayload);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception!');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithDecryptionException(): void
    {
        $appId = 'non-existent-app';
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);
        $encryptedPayload = 'malformed-encrypted-payload';

        $event = new RequestStartEvent($request, $encryptedPayload);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception.');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnResponseSendingWithNoRequest(): void
    {
        $event = new ResponseSendingEvent();
        $event->setRequest(null);

        $this->subscriber->onResponseSending($event);

        // 没有抛出异常表示成功
        $this->assertTrue(true);
    }

    public function testOnResponseSendingWithNoEncryption(): void
    {
        $request = new Request();
        $event = new ResponseSendingEvent();
        $event->setRequest($request);
        $event->setResponseString('response');

        $this->subscriber->onResponseSending($event);

        $this->assertSame('response', $event->getResponseString());
    }

    public function testOnResponseSendingWithEncryption(): void
    {
        $appId = 'test-app-response';
        $appSecret = 'test-secret';
        $this->createAccessKey($appId, $appSecret);

        $responseString = '{"jsonrpc":"2.0","result":"test-result","id":1}';
        $request = new Request();
        $request->headers->set(Encryptor::APPID_HEADER, $appId);
        $expectedEncryptedResponse = $this->encryptor->encryptByRequest($request, $responseString);

        $event = new ResponseSendingEvent();
        $event->setRequest($request);
        $event->setResponseString($responseString);

        $this->subscriber->onResponseSending($event);

        $this->assertSame($expectedEncryptedResponse, $event->getResponseString());
    }
}
