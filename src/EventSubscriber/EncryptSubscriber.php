<?php

namespace Tourze\JsonRPCEncryptBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\JsonRPC\Core\Event\RequestStartEvent;
use Tourze\JsonRPC\Core\Event\ResponseSendingEvent;
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class EncryptSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Encryptor $encryptor,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // 先解密，要不很多东西记录都是错的
            RequestStartEvent::class => [
                ['onRequestStart', 9999],
            ],
            // 最后我们才进行加密
            ResponseSendingEvent::class => [
                ['onResponseSending', -9999],
            ],
        ];
    }

    /**
     * 如果传过来的header提示当前需要加密，那我们就对请求过来的数据进行一次解密
     */
    public function onRequestStart(RequestStartEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return;
        }
        if (!$this->encryptor->shouldEncrypt($request)) {
            return;
        }

        $requestString = $event->getPayload();
        // 这里有一种可能，就是数据在上层已经解密好了，那么在这里我们就不用继续处理
        if (json_validate($requestString)) {
            return;
        }

        try {
            $result = $this->encryptor->decryptByRequest($request, $requestString);
            $this->logger->debug('对请求进行解密', [
                'request' => $requestString,
                'result' => $result,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('解密请求时发生异常', [
                'exception' => $exception,
                'payload' => $requestString,
            ]);
            throw new NotFoundHttpException('decrypt exception.');
        }
        if (!$result) {
            throw new NotFoundHttpException('decrypt exception!');
        }

        $event->setPayload($result);
    }

    /**
     * 如果需要加密的话，我们就返回加密后的数据回去
     */
    public function onResponseSending(ResponseSendingEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return;
        }
        if (!$this->encryptor->shouldEncrypt($request)) {
            return;
        }

        $response = $event->getResponseString();
        $encResponse = $this->encryptor->encryptByRequest($request, $response);
        $this->logger->debug('对响应内容进行加密', [
            'response' => $response,
            'encResponse' => $encResponse,
        ]);
        $event->setResponseString($encResponse);
    }
}
