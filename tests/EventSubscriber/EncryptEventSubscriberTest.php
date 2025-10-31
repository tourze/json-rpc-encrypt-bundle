<?php

namespace Tourze\JsonRPCEncryptBundle\Tests\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\AccessKeyBundle\Service\ApiCallerService;
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
    private Encryptor $encryptor;

    private LoggerInterface $logger;

    private EncryptEventSubscriber $subscriber;

    protected function onSetUp(): void
    {
        $this->initializeMocks();
    }

    private function initializeMocks(): void
    {
        // 创建ApiCallerService Mock
        $mockApiCallerService = $this->createMock(ApiCallerService::class);

        // 创建 Encryptor Mock
        $this->encryptor = new class($mockApiCallerService) extends Encryptor {
            /** @var array<string, mixed> */
            private array $returnValues = [];

            /** @var array<string, \Throwable> */
            private array $exceptions = [];

            public function __construct(ApiCallerService $apiCallerService)
            {
                parent::__construct($apiCallerService);
            }

            public function shouldEncrypt(Request $request): bool
            {
                $value = $this->returnValues['shouldEncrypt'] ?? false;
                // @phpstan-ignore phpunit.noAssertFuncCallInTests (类型守卫，非测试断言)
                assert(is_bool($value));

                return $value;
            }

            public function encryptByRequest(Request $request, string $data): string
            {
                if (isset($this->exceptions['encryptByRequest'])) {
                    throw $this->exceptions['encryptByRequest'];
                }

                $value = $this->returnValues['encryptByRequest'] ?? $data;
                // @phpstan-ignore phpunit.noAssertFuncCallInTests (类型守卫，非测试断言)
                assert(is_string($value));

                return $value;
            }

            public function decryptByRequest(Request $request, string $data): string|false
            {
                if (isset($this->exceptions['decryptByRequest'])) {
                    throw $this->exceptions['decryptByRequest'];
                }

                $value = $this->returnValues['decryptByRequest'] ?? $data;
                // @phpstan-ignore phpunit.noAssertFuncCallInTests (类型守卫，非测试断言)
                assert(is_string($value) || false === $value);

                return $value;
            }

            public function setReturnValue(string $method, mixed $value): void
            {
                $this->returnValues[$method] = $value;
            }

            public function setException(string $method, \Throwable $exception): void
            {
                $this->exceptions[$method] = $exception;
            }
        };

        // 创建 Logger Mock
        // @phpstan-ignore-next-line
        $this->logger = new class implements LoggerInterface {
            /** @var array<int, array<string, mixed>> */
            private array $logs = [];

            public function emergency(string|\Stringable $message, array $context = []): void
            {
                $this->log('emergency', $message, $context);
            }

            public function alert(string|\Stringable $message, array $context = []): void
            {
                $this->log('alert', $message, $context);
            }

            public function critical(string|\Stringable $message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }

            public function error(string|\Stringable $message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }

            public function warning(string|\Stringable $message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }

            public function notice(string|\Stringable $message, array $context = []): void
            {
                $this->log('notice', $message, $context);
            }

            public function info(string|\Stringable $message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }

            public function debug(string|\Stringable $message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }

            /** @return array<int, array<string, mixed>> */
            public function getLogs(): array
            {
                return $this->logs;
            }
        };

        // 直接实例化，避免容器冲突
        /** @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass */
        $this->subscriber = new EncryptEventSubscriber($this->encryptor, $this->logger);
    }

    private function createRequestStartEvent(?Request $request = null, ?string $payload = null): RequestStartEvent
    {
        return new class($request, $payload) extends RequestStartEvent {
            private ?Request $request;

            private ?string $payload;

            /** @var array<string, int> */
            private array $calls = [];

            /** @var array<int, string> */
            private array $setPayloadCalls = [];

            public function __construct(?Request $request, ?string $payload)
            {
                $this->request = $request;
                $this->payload = $payload;
                // 故意不调用父类构造函数，避免依赖注入问题
            }

            public function getRequest(): ?Request
            {
                $this->calls['getRequest'] = ($this->calls['getRequest'] ?? 0) + 1;

                return $this->request;
            }

            public function getPayload(): string
            {
                $this->calls['getPayload'] = ($this->calls['getPayload'] ?? 0) + 1;

                return $this->payload ?? '';
            }

            public function setPayload(string $payload): void
            {
                $this->setPayloadCalls[] = $payload;
                $this->payload = $payload;
            }

            public function getCallCount(string $method): int
            {
                return $this->calls[$method] ?? 0;
            }

            /** @return array<int, string> */
            public function getSetPayloadCalls(): array
            {
                return $this->setPayloadCalls;
            }
        };
    }

    private function createResponseSendingEvent(?Request $request = null, ?string $responseString = null): ResponseSendingEvent
    {
        return new class($request, $responseString) extends ResponseSendingEvent {
            private ?Request $request;

            private ?string $responseString;

            /** @var array<string, int> */
            private array $calls = [];

            /** @var array<int, string> */
            private array $setResponseStringCalls = [];

            public function __construct(?Request $request, ?string $responseString)
            {
                $this->request = $request;
                $this->responseString = $responseString;
                // 故意不调用父类构造函数，避免依赖注入问题
            }

            public function getRequest(): ?Request
            {
                $this->calls['getRequest'] = ($this->calls['getRequest'] ?? 0) + 1;

                return $this->request;
            }

            public function getResponseString(): string
            {
                $this->calls['getResponseString'] = ($this->calls['getResponseString'] ?? 0) + 1;

                return $this->responseString ?? '';
            }

            public function setResponseString(string $responseString): void
            {
                $this->setResponseStringCalls[] = $responseString;
                $this->responseString = $responseString;
            }

            public function getCallCount(string $method): int
            {
                return $this->calls[$method] ?? 0;
            }

            /** @return array<int, string> */
            public function getSetResponseStringCalls(): array
            {
                return $this->setResponseStringCalls;
            }
        };
    }

    private function createRequest(): Request
    {
        return new class extends Request {
            public function __construct()
            {
                // 调用父类构造函数，使用默认参数
                parent::__construct();
            }
        };
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
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $event = $this->createRequestStartEvent(null, null);

        $this->subscriber->onRequestStart($event);

        // 验证getPayload()未被调用
        /** @phpstan-ignore-next-line */
        /** @phpstan-ignore-next-line */
        $this->assertEquals(0, $event->getCallCount('getPayload'));
    }

    public function testOnRequestStartWithNoEncryption(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', false);

        $event = $this->createRequestStartEvent($request, null);

        $this->subscriber->onRequestStart($event);

        // 验证getPayload()未被调用
        /** @phpstan-ignore-next-line */
        $this->assertEquals(0, $event->getCallCount('getPayload'));
    }

    public function testOnRequestStartWithAlreadyDecodedJson(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', true);

        $jsonPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        $event = $this->createRequestStartEvent($request, $jsonPayload);

        $this->subscriber->onRequestStart($event);

        // 验证setPayload()未被调用
        /** @phpstan-ignore-next-line */
        $this->assertEmpty($event->getSetPayloadCalls());
    }

    public function testOnRequestStartWithEncryptedRequest(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', true);

        $encryptedPayload = 'encrypted-payload';
        $decryptedPayload = '{"jsonrpc":"2.0","method":"test","params":{},"id":1}';

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('decryptByRequest', $decryptedPayload);

        $event = $this->createRequestStartEvent($request, $encryptedPayload);

        $this->subscriber->onRequestStart($event);

        // 验证setPayload()被调用一次，且参数正确
        /** @phpstan-ignore-next-line */
        $setPayloadCalls = $event->getSetPayloadCalls();
        /** @var array<int, string> $setPayloadCalls */
        $this->assertCount(1, $setPayloadCalls);
        $this->assertEquals($decryptedPayload, $setPayloadCalls[0]);

        // 验证日志记录
        /** @phpstan-ignore-next-line */
        $logs = $this->logger->getLogs();
        /** @var array<int, array<string, mixed>> $logs */
        $this->assertCount(1, $logs);
        $this->assertEquals('debug', $logs[0]['level']);
        $this->assertEquals('对请求进行解密', $logs[0]['message']);
    }

    public function testOnRequestStartWithDecryptionFailure(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', true);

        $encryptedPayload = 'corrupted-encrypted-payload';

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('decryptByRequest', false);

        $event = $this->createRequestStartEvent($request, $encryptedPayload);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception!');

        $this->subscriber->onRequestStart($event);

        // 验证日志记录
        /** @phpstan-ignore-next-line */
        $logs = $this->logger->getLogs();
        /** @var array<int, array<string, mixed>> $logs */
        $this->assertCount(1, $logs);
        $this->assertEquals('debug', $logs[0]['level']);
        $this->assertEquals('对请求进行解密', $logs[0]['message']);
    }

    public function testOnRequestStartWithDecryptionException(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', true);

        $encryptedPayload = 'malformed-encrypted-payload';
        $exception = new \Exception('Decryption failed');

        /** @phpstan-ignore-next-line */
        $this->encryptor->setException('decryptByRequest', $exception);

        $event = $this->createRequestStartEvent($request, $encryptedPayload);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('decrypt exception.');

        $this->subscriber->onRequestStart($event);

        // 验证错误日志记录
        /** @phpstan-ignore-next-line */
        $logs = $this->logger->getLogs();
        /** @var array<int, array<string, mixed>> $logs */
        $errorLogs = array_filter($logs, static fn (array $log): bool => 'error' === $log['level']);
        $this->assertCount(1, $errorLogs);
        /** @var list<array<string, mixed>> $errorLogsList */
        $errorLogsList = array_values($errorLogs);
        $errorLog = $errorLogsList[0];
        $this->assertEquals('解密请求时发生异常', $errorLog['message']);
        /** @var array<string, mixed> $context */
        $context = $errorLog['context'];
        $this->assertArrayHasKey('exception', $context);
        $this->assertSame($exception, $context['exception']);
    }

    public function testOnResponseSendingWithNoRequest(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $event = $this->createResponseSendingEvent(null, null);

        $this->subscriber->onResponseSending($event);

        // 验证getResponseString()未被调用
        /** @phpstan-ignore-next-line */
        $this->assertEquals(0, $event->getCallCount('getResponseString'));
    }

    public function testOnResponseSendingWithNoEncryption(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', false);

        $event = $this->createResponseSendingEvent($request, null);

        $this->subscriber->onResponseSending($event);

        // 验证getResponseString()未被调用
        /** @phpstan-ignore-next-line */
        $this->assertEquals(0, $event->getCallCount('getResponseString'));
    }

    public function testOnResponseSendingWithEncryption(): void
    {
        // 创建匿名类替代Mock，避免PHPStan静态分析错误
        $request = $this->createRequest();

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('shouldEncrypt', true);

        $responseString = '{"jsonrpc":"2.0","result":"test-result","id":1}';
        $encryptedResponse = 'encrypted-response';

        /** @phpstan-ignore-next-line */
        $this->encryptor->setReturnValue('encryptByRequest', $encryptedResponse);

        $event = $this->createResponseSendingEvent($request, $responseString);

        $this->subscriber->onResponseSending($event);

        // 验证setResponseString()被调用一次，且参数正确
        /** @phpstan-ignore-next-line */
        $setResponseStringCalls = $event->getSetResponseStringCalls();
        /** @var array<int, string> $setResponseStringCalls */
        $this->assertCount(1, $setResponseStringCalls);
        $this->assertEquals($encryptedResponse, $setResponseStringCalls[0]);

        // 验证日志记录
        /** @phpstan-ignore-next-line */
        $logs = $this->logger->getLogs();
        /** @var array<int, array<string, mixed>> $logs */
        $this->assertCount(1, $logs);
        $this->assertEquals('debug', $logs[0]['level']);
        $this->assertEquals('对响应内容进行加密', $logs[0]['message']);
    }
}
