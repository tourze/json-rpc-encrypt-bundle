<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\EventSubscriber;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\JsonRPC\Core\Event\RequestStartEvent;
use Tourze\JsonRPC\Core\Event\ResponseSendingEvent;
use Tourze\JsonRPCEncryptBundle\EventSubscriber\EncryptSubscriber;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class EncryptSubscriberTest extends TestCase
{
    private Encryptor|MockObject $encryptor;
    private RequestStack|MockObject $requestStack;
    private LoggerInterface|MockObject $logger;
    private EncryptSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->encryptor = $this->createMock(Encryptor::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new EncryptSubscriber(
            $this->encryptor,
            $this->requestStack,
            $this->logger
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = EncryptSubscriber::getSubscribedEvents();

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
        $this->requestStack->method('getMainRequest')->willReturn(null);

        $event = $this->createMock(RequestStartEvent::class);
        $event->expects($this->never())->method('getPayload');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithNoEncryption(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(false);

        $event = $this->createMock(RequestStartEvent::class);
        $event->expects($this->never())->method('getPayload');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithAlreadyDecodedJson(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(true);

        $jsonPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        $event = $this->createMock(RequestStartEvent::class);
        $event->method('getPayload')->willReturn($jsonPayload);
        $event->expects($this->never())->method('setPayload');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithEncryptedRequest(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(true);

        $encryptedPayload = 'encrypted-payload';
        $decryptedPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        $this->encryptor->method('decryptByRequest')
            ->with($request, $encryptedPayload)
            ->willReturn($decryptedPayload);

        $event = $this->createMock(RequestStartEvent::class);
        $event->method('getPayload')->willReturn($encryptedPayload);
        $event->expects($this->once())
            ->method('setPayload')
            ->with($decryptedPayload);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('对请求进行解密', $this->anything());

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithDecryptionFailure(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(true);

        $encryptedPayload = 'corrupted-encrypted-payload';

        $this->encryptor->method('decryptByRequest')
            ->with($request, $encryptedPayload)
            ->willReturn(false);

        $event = $this->createMock(RequestStartEvent::class);
        $event->method('getPayload')->willReturn($encryptedPayload);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('对请求进行解密', $this->anything());

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception!');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnRequestStartWithDecryptionException(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(true);

        $encryptedPayload = 'malformed-encrypted-payload';
        $exception = new \Exception('Decryption failed');

        $this->encryptor->method('decryptByRequest')
            ->with($request, $encryptedPayload)
            ->willThrowException($exception);

        $event = $this->createMock(RequestStartEvent::class);
        $event->method('getPayload')->willReturn($encryptedPayload);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('解密请求时发生异常', $this->callback(function ($context) use ($exception) {
                return isset($context['exception']) && $context['exception'] === $exception;
            }));

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception.');

        $this->subscriber->onRequestStart($event);
    }

    public function testOnResponseSendingWithNoRequest(): void
    {
        $this->requestStack->method('getMainRequest')->willReturn(null);

        $event = $this->createMock(ResponseSendingEvent::class);
        $event->expects($this->never())->method('getResponseString');

        $this->subscriber->onResponseSending($event);
    }

    public function testOnResponseSendingWithNoEncryption(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(false);

        $event = $this->createMock(ResponseSendingEvent::class);
        $event->expects($this->never())->method('getResponseString');

        $this->subscriber->onResponseSending($event);
    }

    public function testOnResponseSendingWithEncryption(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getMainRequest')->willReturn($request);

        $this->encryptor->method('shouldEncrypt')
            ->with($request)
            ->willReturn(true);

        $responseString = '{"jsonrpc":"2.0","result":"test-result","id":1}';
        $encryptedResponse = 'encrypted-response';

        $this->encryptor->method('encryptByRequest')
            ->with($request, $responseString)
            ->willReturn($encryptedResponse);

        $event = $this->createMock(ResponseSendingEvent::class);
        $event->method('getResponseString')->willReturn($responseString);
        $event->expects($this->once())
            ->method('setResponseString')
            ->with($encryptedResponse);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('对响应内容进行加密', $this->anything());

        $this->subscriber->onResponseSending($event);
    }
}
